<?php

namespace App\Support;

use App\Models\Animal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Genealogia
{
    public static function disponivel(): bool
    {
        return Esquema::temV019();
    }

    /**
     * Ao salvar genitor não cadastrado com nome + registro, cria ou reutiliza uma referência.
     * Retorna ['pai_referencia_id' => ..., 'mae_referencia_id' => ...].
     */
    public static function processarReferencias(array $dados, int $empresaId, int $usuarioId): array
    {
        if (! self::disponivel()) {
            return [];
        }

        $resultado = [];

        foreach (['pai' => 'macho', 'mae' => 'femea'] as $campo => $sexo) {
            $chave = $campo.'_referencia_id';

            if (! empty($dados[$campo.'_id'])) {
                $resultado[$chave] = null;
                continue;
            }

            $nome = trim($dados[$campo] ?? '');
            $registro = trim($dados[$campo.'_registro'] ?? '');

            if ($nome === '' && $registro === '') {
                $resultado[$chave] = null;
                continue;
            }

            $ref = null;

            if ($registro !== '') {
                $ref = DB::table('animal_referencia')
                    ->where('registro', $registro)
                    ->whereNull('animal_id')
                    ->first();
            }

            if (! $ref && $nome !== '') {
                $q = DB::table('animal_referencia')
                    ->where('nome', $nome)
                    ->where('sexo', $sexo)
                    ->whereNull('animal_id');

                if ($registro !== '') {
                    $q->where('registro', $registro);
                }

                $ref = $q->first();
            }

            if ($ref) {
                if ($registro !== '' && ! $ref->registro) {
                    DB::table('animal_referencia')->where('id', $ref->id)->update(['registro' => $registro]);
                }
                $resultado[$chave] = $ref->id;
            } else {
                $id = DB::table('animal_referencia')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'nome' => $nome ?: 'Sem nome',
                    'registro' => $registro ?: null,
                    'registro_associacao' => $dados[$campo.'_registro_associacao'] ?? null,
                    'especie' => $dados['especie'] ?? 'equino',
                    'sexo' => $sexo,
                    'criado_por_empresa_id' => $empresaId,
                    'criado_por' => $usuarioId,
                ]);
                $resultado[$chave] = $id;
            }
        }

        return $resultado;
    }

    /**
     * Ao cadastrar um animal real com registro: vincula automaticamente as referências pendentes
     * e atualiza pai_id/mae_id dos animais que apontavam para elas.
     */
    public static function vincularSeExistir(Animal $animal): int
    {
        if (! self::disponivel() || ! $animal->registro) {
            return 0;
        }

        $refs = DB::table('animal_referencia')
            ->where('registro', $animal->registro)
            ->whereNull('animal_id')
            ->get();

        $vinculados = 0;

        foreach ($refs as $ref) {
            DB::table('animal_referencia')->where('id', $ref->id)->update([
                'animal_id' => $animal->id,
                'vinculado_em' => now(),
            ]);

            $vinculados += DB::table('animal')
                ->where('pai_referencia_id', $ref->id)
                ->whereNull('pai_id')
                ->update(['pai_id' => $animal->id]);

            $vinculados += DB::table('animal')
                ->where('mae_referencia_id', $ref->id)
                ->whereNull('mae_id')
                ->update(['mae_id' => $animal->id]);
        }

        return $vinculados;
    }

    /**
     * Pré-cadastros que foram vinculados a este animal (outros Haras o referenciaram como genitor).
     */
    public static function referenciasDoAnimal(Animal $animal): Collection
    {
        if (! self::disponivel()) {
            return collect();
        }

        return DB::table('animal_referencia as r')
            ->where('r.animal_id', $animal->id)
            ->leftJoin('empresa as e', 'e.id', '=', 'r.criado_por_empresa_id')
            ->select('r.*', 'e.nome as empresa_nome')
            ->get();
    }

    /**
     * Animais que usam uma referência específica como genitor.
     */
    public static function filhosReferenciados(int $referenciaId): Collection
    {
        if (! self::disponivel()) {
            return collect();
        }

        return DB::table('animal')
            ->where(fn ($q) => $q->where('pai_referencia_id', $referenciaId)->orWhere('mae_referencia_id', $referenciaId))
            ->whereNull('excluido_em')
            ->get();
    }

    /**
     * Buscar referências (pré-cadastros) por nome ou registro — para a tela de busca de genitor.
     */
    public static function buscar(string $q, ?string $sexo = null, int $limite = 10): Collection
    {
        if (! self::disponivel()) {
            return collect();
        }

        $query = DB::table('animal_referencia as r')
            ->whereNull('r.animal_id')
            ->where(fn ($qb) => $qb->where('r.nome', 'like', "%{$q}%")->orWhere('r.registro', 'like', "%{$q}%"))
            ->leftJoin('empresa as e', 'e.id', '=', 'r.criado_por_empresa_id')
            ->select('r.id', 'r.nome', 'r.registro', 'r.registro_associacao', 'e.nome as empresa_nome');

        if ($sexo) {
            $query->where('r.sexo', $sexo);
        }

        return $query->limit($limite)->get();
    }
}
