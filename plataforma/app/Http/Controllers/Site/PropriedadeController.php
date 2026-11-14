<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Support\Esquema;
use App\Support\PaginaPropriedade;
use Illuminate\Support\Facades\Storage;

/** Página pública de cada Haras / Fazenda (/h/<endereço>) e as imagens dela (logomarca, capa, marca). */
class PropriedadeController extends Controller
{
    public function pagina(string $slug)
    {
        abort_unless(Esquema::temV008(), 404);
        $empresa = Empresa::naoExcluidas()->where('subsite_slug', $slug)->where('pagina_ativa', 1)->whereIn('situacao', ['teste', 'ativo'])->firstOrFail();
        $primaria = $empresa->cor_primaria ?: PaginaPropriedade::COR_PRIMARIA;
        $fundo = $empresa->cor_fundo ?: PaginaPropriedade::COR_FUNDO;

        return view('site.propriedade', [
            'p' => $empresa,
            'racas' => $empresa->racas(),
            'cores' => [
                'primaria' => $primaria, 'sobre_primaria' => PaginaPropriedade::corSobre($primaria),
                'fundo' => $fundo, 'texto' => PaginaPropriedade::corSobre($fundo),
            ],
        ]);
    }

    /** Entrega uma imagem da propriedade guardada fora do public_html. */
    public function imagem(string $uuid, string $tipo)
    {
        abort_unless(Esquema::temV008() && isset(PaginaPropriedade::IMAGENS[$tipo]), 404);
        $empresa = Empresa::naoExcluidas()->where('uuid', $uuid)->firstOrFail();
        $arquivo = $empresa->{$tipo.'_arquivo'};
        $disco = Storage::disk('midia');
        abort_unless($arquivo && str_starts_with($arquivo, 'propriedades/'.$empresa->uuid.'/') && $disco->exists($arquivo), 404);

        return response()->file($disco->path($arquivo), ['Cache-Control' => 'public, max-age=604800', 'X-Content-Type-Options' => 'nosniff']);
    }
}
