<?php

namespace App\Support;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Página pública de cada Haras / Fazenda (minhatropa.com.br/h/<endereço>): logomarca, capa, cores,
 * apresentação e contatos. Usada pelo painel do proprietário e pela Administração.
 * As imagens ficam no disco "midia" (fora do public_html) e saem pela rota midia.propriedade.
 */
class PaginaPropriedade
{
    public const IMAGENS = ['logo' => 'Logomarca', 'capa' => 'Imagem de capa', 'marca' => 'Marca do criador'];

    public const COR_PRIMARIA = '#101828';

    public const COR_FUNDO = '#FFFFFF';

    /** Regras dos campos da página (todos opcionais). */
    public static function regras(?Empresa $empresa): array
    {
        if (! Esquema::temV008()) {
            return [];
        }
        $imagem = ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

        return [
            'logo' => $imagem, 'capa' => $imagem, 'marca' => $imagem,
            'cor_primaria' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'cor_fundo' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pagina_slogan' => ['nullable', 'string', 'max:150'],
            'pagina_apresentacao' => ['nullable', 'string', 'max:5000'],
            'instagram' => ['nullable', 'string', 'max:100', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'facebook' => ['nullable', 'url:http,https', 'max:255'],
            'subsite_slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9\-]*$/', Rule::unique('empresa', 'subsite_slug')->ignore($empresa?->id)],
        ];
    }

    public static function mensagens(): array
    {
        return [
            'logo.max' => 'A logomarca pode ter no máximo 2 MB.', 'capa.max' => 'A imagem de capa pode ter no máximo 2 MB.', 'marca.max' => 'A imagem da marca pode ter no máximo 2 MB.',
            'logo.mimes' => 'A logomarca precisa ser JPG, PNG ou WebP.', 'capa.mimes' => 'A imagem de capa precisa ser JPG, PNG ou WebP.', 'marca.mimes' => 'A imagem da marca precisa ser JPG, PNG ou WebP.',
            'cor_primaria.regex' => 'Escolha a cor principal no seletor de cores.', 'cor_fundo.regex' => 'Escolha a cor de fundo no seletor de cores.',
            'instagram.regex' => 'Informe só o usuário do Instagram, sem espaços (ex.: @meuharas).',
            'subsite_slug.unique' => 'Esse endereço de página já está em uso por outra propriedade.',
            'subsite_slug.regex' => 'O endereço da página aceita só letras minúsculas, números e hifens.',
        ];
    }

    /**
     * Separa o que vai para as colunas (sem os arquivos) e ajusta o que depende de regra:
     * marcações, @ do Instagram e endereço automático quando a página é ligada sem endereço.
     */
    public static function colunas(Request $request, array $dados, ?Empresa $empresa): array
    {
        unset($dados['logo'], $dados['capa'], $dados['marca']);
        if (! Esquema::temV008()) {
            return $dados;
        }
        $dados['pagina_ativa'] = $request->boolean('pagina_ativa');
        $dados['pagina_mostrar_contato'] = $request->boolean('pagina_mostrar_contato');
        $dados['pagina_mostrar_endereco'] = $request->boolean('pagina_mostrar_endereco');
        if (filled($dados['instagram'] ?? null)) {
            $dados['instagram'] = ltrim($dados['instagram'], '@');
        }
        foreach (['cor_primaria', 'cor_fundo'] as $cor) {
            $dados[$cor] = filled($dados[$cor] ?? null) ? strtoupper($dados[$cor]) : null;
        }
        if ($dados['pagina_ativa'] && blank($dados['subsite_slug'] ?? null)) {
            $dados['subsite_slug'] = self::enderecoLivre($dados['nome'] ?? $empresa?->nome ?? 'propriedade', $empresa?->id);
        }

        return $dados;
    }

    /** Grava as imagens enviadas e remove as marcadas para remoção. Chamar depois de salvar a propriedade. */
    public static function salvarImagens(Request $request, Empresa $empresa): void
    {
        if (! Esquema::temV008()) {
            return;
        }
        $tipos = array_filter(array_keys(self::IMAGENS), fn ($t) => $request->hasFile($t) || $request->boolean('remover_'.$t));
        if (! $tipos) {
            return; // nada de imagem neste envio: nem abre o disco
        }
        $disco = Storage::disk('midia');
        $mudancas = [];
        foreach ($tipos as $tipo) {
            $coluna = $tipo.'_arquivo';
            $atual = $empresa->{$coluna};
            $arquivo = $request->file($tipo);
            if ($arquivo && $arquivo->isValid()) {
                $extensao = match ($arquivo->getMimeType()) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
                $novo = $disco->putFileAs('propriedades/'.$empresa->uuid, $arquivo, $tipo.'-'.Str::lower(Str::random(10)).'.'.$extensao);
                $mudancas[$coluna] = $novo;
            } elseif ($request->boolean('remover_'.$tipo) && $atual) {
                $mudancas[$coluna] = null;
            } else {
                continue;
            }
            // Só apaga arquivo antigo que está no disco de mídia (o campo marca_arquivo é anterior e podia apontar para outro lugar).
            if ($atual && str_starts_with($atual, 'propriedades/') && $disco->exists($atual)) {
                $disco->delete($atual);
            }
        }
        if ($mudancas) {
            $empresa->forceFill($mudancas)->save();
        }
    }

    /** Endereço (slug) ainda não usado, a partir do nome. */
    public static function enderecoLivre(string $nome, ?int $ignorarId = null): string
    {
        $base = mb_substr(Str::slug($nome) ?: 'propriedade', 0, 50);
        $slug = $base;
        for ($i = 2; Empresa::where('subsite_slug', $slug)->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    /** Cor de texto legível (#101828 ou #FFFFFF) sobre a cor informada. */
    public static function corSobre(?string $hex): string
    {
        if (! $hex || ! preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
            return '#FFFFFF';
        }
        [$r, $g, $b] = array_map(fn ($p) => hexdec($p) / 255, str_split(substr($hex, 1), 2));
        $canal = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminancia = 0.2126 * $canal($r) + 0.7152 * $canal($g) + 0.0722 * $canal($b);

        return $luminancia > 0.4 ? '#101828' : '#FFFFFF';
    }
}
