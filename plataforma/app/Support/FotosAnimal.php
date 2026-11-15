<?php

namespace App\Support;

use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fotos do animal: até 3 por animal, sempre no mesmo formato (retrato 3:4, 900 x 1200, JPEG compactado).
 * O arquivo fica no disco "midia" (fora do public_html); o banco guarda só o caminho (tabela animal_foto).
 * O navegador já recorta e compacta antes de enviar (public/js/fotos-animal.js); aqui a foto é refeita de novo,
 * para o formato valer mesmo se o envio vier sem esse tratamento.
 */
class FotosAnimal
{
    public const MAXIMO = 3;

    public const LARGURA = 900;

    public const ALTURA = 1200;

    public const QUALIDADE = 80;

    public static function disponivel(): bool
    {
        return Esquema::temV016();
    }

    public static function regras(): array
    {
        if (! self::disponivel()) {
            return [];
        }

        return [
            'fotos' => ['nullable', 'array', 'max:'.self::MAXIMO],
            'fotos.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'remover_foto' => ['nullable', 'array'],
        ];
    }

    public static function mensagens(): array
    {
        return [
            'fotos.*.mimes' => 'A foto precisa ser JPG, PNG ou WebP.',
            'fotos.*.max' => 'A foto é grande demais para enviar. Tente outra imagem.',
            'fotos.*.uploaded' => 'A foto não chegou ao servidor (arquivo grande demais). Tente outra imagem.',
            'fotos.*.file' => 'Escolha um arquivo de imagem.',
        ];
    }

    /** Fotos do animal, pela posição (1 a 3). */
    public static function doAnimal(?Animal $animal): Collection
    {
        if (! $animal?->exists || ! self::disponivel()) {
            return collect();
        }

        return DB::table('animal_foto')->where('animal_id', $animal->id)->orderBy('posicao')->get()->keyBy('posicao');
    }

    /** Foto principal (a primeira que existir) de vários animais de uma vez: [animal_id => linha]. */
    public static function principais(iterable $animalIds): Collection
    {
        $ids = collect($animalIds)->filter()->unique()->values();
        if ($ids->isEmpty() || ! self::disponivel()) {
            return collect();
        }

        return DB::table('animal_foto')->whereIn('animal_id', $ids)->orderBy('posicao')->get()->unique('animal_id')->keyBy('animal_id');
    }

    /** Grava as fotos enviadas e tira as marcadas para remoção. Devolve as posições que mudaram. */
    public static function salvar(Request $request, Animal $animal, ?int $usuarioId): array
    {
        if (! self::disponivel()) {
            return [];
        }
        $enviadas = (array) $request->file('fotos', []);
        $remover = array_keys(array_filter((array) $request->input('remover_foto', [])));
        if (! $enviadas && ! $remover) {
            return []; // nada de foto neste envio: nem abre o disco
        }
        $disco = Storage::disk('midia');
        $atuais = self::doAnimal($animal);
        $mudou = [];
        for ($posicao = 1; $posicao <= self::MAXIMO; $posicao++) {
            $arquivo = $enviadas[$posicao] ?? null;
            $atual = $atuais->get($posicao);
            if ($arquivo instanceof UploadedFile && $arquivo->isValid()) {
                $jpeg = self::tratar($arquivo, $posicao);
                $caminho = 'animais/'.$animal->empresa_id.'/'.$animal->id.'/foto-'.$posicao.'-'.Str::lower(Str::random(10)).'.jpg';
                $disco->put($caminho, $jpeg);
                DB::table('animal_foto')->updateOrInsert(
                    ['animal_id' => $animal->id, 'posicao' => $posicao],
                    ['empresa_id' => $animal->empresa_id, 'arquivo' => $caminho, 'largura' => self::LARGURA, 'altura' => self::ALTURA,
                        'bytes' => strlen($jpeg), 'criado_por' => $usuarioId, 'criado_em' => now('UTC')],
                );
            } elseif (in_array($posicao, $remover, false) && $atual) {
                DB::table('animal_foto')->where('id', $atual->id)->delete();
            } else {
                continue;
            }
            if ($atual && str_starts_with($atual->arquivo, 'animais/') && $disco->exists($atual->arquivo)) {
                $disco->delete($atual->arquivo);
            }
            $mudou[] = $posicao;
        }

        return $mudou;
    }

    /**
     * Refaz a imagem no formato único: corrige a rotação da câmera, recorta o centro em retrato 3:4,
     * reduz para 900 x 1200 e grava em JPEG compactado. Devolve o conteúdo do arquivo.
     */
    public static function tratar(UploadedFile $arquivo, int $posicao = 1): string
    {
        $erro = fn (string $m) => ValidationException::withMessages(['fotos.'.$posicao => $m]);
        $info = @getimagesize($arquivo->getRealPath());
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw $erro('Não foi possível ler essa foto. Envie uma imagem JPG, PNG ou WebP.');
        }
        [$largura, $altura] = $info;
        if (! function_exists('imagecreatetruecolor')) {
            // Servidor sem tratamento de imagem: só aceita o que o navegador já entregou no formato certo.
            if ($info[2] === IMAGETYPE_JPEG && $largura === self::LARGURA && $altura === self::ALTURA) {
                return (string) file_get_contents($arquivo->getRealPath());
            }
            throw $erro('Não foi possível ajustar essa foto. Tente enviar de novo pelo navegador atualizado.');
        }
        if ($largura * $altura > 50_000_000) {
            throw $erro('Essa foto é grande demais para tratar. Envie uma imagem menor.');
        }
        $origem = @imagecreatefromstring((string) file_get_contents($arquivo->getRealPath()));
        if (! $origem) {
            throw $erro('Não foi possível ler essa foto. Envie uma imagem JPG, PNG ou WebP.');
        }
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $giro = match ((int) (@exif_read_data($arquivo->getRealPath())['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
            if ($giro && ($girada = imagerotate($origem, $giro, 0))) {
                $origem = $girada;
                [$largura, $altura] = [imagesx($origem), imagesy($origem)];
            }
        }
        // Recorte central na proporção 3:4 (retrato).
        $proporcao = self::LARGURA / self::ALTURA;
        if ($largura / $altura > $proporcao) {
            $cl = (int) round($altura * $proporcao);
            $ca = $altura;
        } else {
            $cl = $largura;
            $ca = (int) round($largura / $proporcao);
        }
        $x = (int) (($largura - $cl) / 2);
        $y = (int) (($altura - $ca) / 2);
        $destino = imagecreatetruecolor(self::LARGURA, self::ALTURA);
        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255)); // fundo branco para PNG transparente
        imagecopyresampled($destino, $origem, 0, 0, $x, $y, self::LARGURA, self::ALTURA, $cl, $ca);
        ob_start();
        imagejpeg($destino, null, self::QUALIDADE);

        return (string) ob_get_clean();
    }
}
