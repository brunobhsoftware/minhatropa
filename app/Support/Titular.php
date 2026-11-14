<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\Profissional;
use Illuminate\Support\Facades\DB;

/** Define o criador / proprietário titular de uma propriedade (pessoa física da base de Profissionais). */
class Titular
{
    public static function definir(Empresa $empresa, ?Profissional $profissional): void
    {
        if (! $profissional) {
            return;
        }
        DB::transaction(function () use ($empresa, $profissional) {
            DB::table('empresa_profissional')->where('empresa_id', $empresa->id)->where('profissional_id', '!=', $profissional->id)->update(['titular' => 0]);
            $vinculo = DB::table('empresa_profissional')->where('empresa_id', $empresa->id)->where('profissional_id', $profissional->id)->first();
            $funcoes = array_values(array_unique(array_merge(array_filter(explode(',', (string) ($vinculo->funcoes ?? ''))), [Profissional::PROPRIETARIO])));
            $texto = mb_substr(collect($funcoes)->map(fn ($f) => Profissional::ESPECIALIDADES[$f] ?? $f)->implode(', '), 0, 100);
            if ($vinculo) {
                DB::table('empresa_profissional')->where('id', $vinculo->id)->update(['titular' => 1, 'funcoes' => implode(',', $funcoes), 'funcao' => $texto, 'situacao' => 'ativo', 'data_fim' => null, 'atualizado_em' => now()]);
            } else {
                DB::table('empresa_profissional')->insert(['empresa_id' => $empresa->id, 'profissional_id' => $profissional->id, 'funcao' => $texto,
                    'funcoes' => implode(',', $funcoes), 'titular' => 1, 'data_inicio' => now()->toDateString(), 'situacao' => 'ativo', 'criado_em' => now()]);
            }
            $profissional->acrescentarEspecialidade(Profissional::PROPRIETARIO);
            if ($empresa->titular_profissional_id !== $profissional->id) {
                $empresa->forceFill(['titular_profissional_id' => $profissional->id])->save();
            }
        });
    }
}
