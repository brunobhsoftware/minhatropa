<?php

namespace App\Support;

use App\Models\Animal;
use Illuminate\Support\Facades\DB;

/**
 * Interligações de um animal na árvore e na reprodução.
 * Regra (Bruno, 24/11/2026): qualquer interligação impede a exclusão do animal —
 * para tirá-lo do plantel usa-se a baixa (óbito, venda, transferência), não a exclusão.
 */
class Vinculos
{
    /** @return string[] motivos legíveis que impedem a exclusão (vazio = pode excluir) */
    public static function impedimentos(Animal $animal): array
    {
        $motivos = [];
        $id = $animal->id;

        $filhos = Animal::whereNull('excluido_em')
            ->where(fn ($q) => $q->where('pai_id', $id)->orWhere('mae_id', $id))->count();
        if ($filhos > 0) {
            $motivos[] = $filhos === 1 ? 'é pai/mãe de 1 animal' : "é pai/mãe de {$filhos} animais";
        }

        if (Esquema::temV005()) {
            $cob = DB::table('cobricao')->whereNull('excluido_em')
                ->where(fn ($q) => $q->where('egua_id', $id)->orWhere('garanhao_id', $id)->orWhere('receptora_id', $id))
                ->count();
            if ($cob > 0) {
                $motivos[] = $cob === 1 ? 'participa de 1 cobrição' : "participa de {$cob} cobrições";
            }

            $nasc = DB::table('nascimento')->whereNull('excluido_em')
                ->where(fn ($q) => $q->where('mae_id', $id)->orWhere('pai_id', $id)->orWhere('animal_id', $id))
                ->count();
            if ($nasc > 0) {
                $motivos[] = $nasc === 1 ? 'tem 1 nascimento' : "tem {$nasc} nascimentos";
            }

            $transf = DB::table('transferencia')->where('animal_id', $id)->count();
            if ($transf > 0) {
                $motivos[] = $transf === 1 ? 'tem 1 transferência' : "tem {$transf} transferências";
            }
        }

        return $motivos;
    }

    public static function podeExcluir(Animal $animal): bool
    {
        return empty(self::impedimentos($animal));
    }
}
