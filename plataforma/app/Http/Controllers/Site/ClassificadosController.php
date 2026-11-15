<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Classificado;
use App\Support\Esquema;
use App\Support\FotosAnimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Classificados do site: animais que os Haras / Fazendas puseram à venda e estão no ar hoje. */
class ClassificadosController extends Controller
{
    public function index(Request $request)
    {
        if (! Esquema::temV017()) {
            return view('site.classificados', ['anuncios' => collect(), 'fotos' => collect(), 'ufs' => collect(), 'paginas' => null]);
        }
        $q = $this->noAr();
        if ($busca = trim((string) $request->query('busca'))) {
            $q->where(fn ($p) => $p->where('animal.nome', 'like', "%{$busca}%")->orWhere('animal.raca', 'like', "%{$busca}%"));
        }
        if (in_array($request->query('sexo'), ['macho', 'femea'], true)) {
            $q->where('animal.sexo', $request->query('sexo'));
        }
        if (preg_match('/^[A-Z]{2}$/', (string) $request->query('uf'))) {
            $q->where('empresa.uf', $request->query('uf'));
        }
        $paginas = $q->orderByDesc('classificado.inicio')->orderByDesc('classificado.id')->paginate(24)->withQueryString();

        return view('site.classificados', [
            'anuncios' => $paginas->getCollection(),
            'paginas' => $paginas,
            'fotos' => FotosAnimal::principais($paginas->getCollection()->pluck('animal_id')),
            'ufs' => $this->noAr()->toBase()->select('empresa.uf')->whereNotNull('empresa.uf')->distinct()->orderBy('empresa.uf')->pluck('uf'),
        ]);
    }

    public function show(string $uuid)
    {
        $anuncio = $this->anuncio($uuid);

        return view('site.classificado', ['anuncio' => $anuncio, 'fotos' => FotosAnimal::doAnimal($anuncio->animal)]);
    }

    /** Foto do animal anunciado: só enquanto o anúncio está no ar. */
    public function foto(string $uuid, int $posicao)
    {
        $anuncio = $this->anuncio($uuid);
        $foto = FotosAnimal::doAnimal($anuncio->animal)->get($posicao);
        $disco = Storage::disk('midia');
        abort_unless($foto && str_starts_with($foto->arquivo, 'animais/'.$anuncio->empresa_id.'/') && $disco->exists($foto->arquivo), 404);

        return response()->file($disco->path($foto->arquivo), ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Anúncios no ar, de animal ativo e de propriedade em funcionamento. */
    private function noAr()
    {
        return Classificado::query()->noAr()->select('classificado.*')
            ->join('animal', 'animal.id', '=', 'classificado.animal_id')
            ->join('empresa', 'empresa.id', '=', 'classificado.empresa_id')
            ->whereNull('animal.excluido_em')->where('animal.situacao', 'ativo')
            ->whereNull('empresa.excluido_em')->whereIn('empresa.situacao', ['teste', 'ativo'])
            ->with(['animal', 'empresa']);
    }

    private function anuncio(string $uuid): Classificado
    {
        abort_unless(Esquema::temV017(), 404);

        return $this->noAr()->where('classificado.uuid', $uuid)->firstOrFail();
    }
}
