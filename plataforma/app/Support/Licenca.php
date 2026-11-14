<?php

namespace App\Support;

use App\Models\Animal;
use App\Models\Empresa;
use App\Models\PlanoPlantel;
use Illuminate\Support\Facades\DB;

/**
 * Plano base por tamanho de plantel (freemium escalonado).
 * A faixa do Haras / Fazenda é definida pela quantidade de animais ATIVOS no plantel:
 * a primeira faixa (até 5 animais) é gratuita e as demais exigem upgrade. As faixas ficam na tabela
 * plano_plantel (limite_min, limite_max, valor_mensal, ativo): criar ou mudar uma faixa não exige alterar o código.
 * A faixa em vigor de cada propriedade fica em assinatura_empresa; sem registro, vale a gratuita.
 */
class Licenca
{
    /** Animais nestas situações saíram do plantel e não contam para a faixa. */
    public const FORA_DO_PLANTEL = ['vendido', 'transferido', 'morto'];

    private static $faixas = null;

    /** Reserva usada só se as faixas de plantel ainda não existirem no banco (antes do V006). */
    public static function animaisGratis(): int
    {
        return (int) (DB::table('parametro_sistema')->where('chave', 'animais_gratis')->value('valor') ?? 5);
    }

    /** Faixas ativas, da menor para a maior. */
    public static function faixas()
    {
        if (! Esquema::temV006()) {
            return collect();
        }

        return self::$faixas ??= PlanoPlantel::where('ativo', 1)->orderBy('limite_min')->orderBy('ordem')->get();
    }

    public static function esquecerFaixas(): void
    {
        self::$faixas = null;
    }

    /** Animais ativos no plantel da propriedade (o que conta para a faixa). */
    public static function animais(Empresa $empresa): int
    {
        $q = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em');
        if (Esquema::temV005()) {
            $q->whereNotIn('situacao', self::FORA_DO_PLANTEL);
        }

        return $q->count();
    }

    public static function assinatura(Empresa $empresa): ?object
    {
        return Esquema::temV006() ? DB::table('assinatura_empresa')->where('empresa_id', $empresa->id)->first() : null;
    }

    /** Faixa em vigor: a contratada ou, sem assinatura, a gratuita (a primeira faixa ativa). */
    public static function faixaAtual(Empresa $empresa): ?PlanoPlantel
    {
        $assinatura = self::assinatura($empresa);
        if ($assinatura) {
            return PlanoPlantel::find($assinatura->plano_plantel_id);
        }

        return self::faixas()->first();
    }

    /** Upgrade pedido pelo proprietário e ainda não liberado. */
    public static function faixaSolicitada(Empresa $empresa): ?PlanoPlantel
    {
        $assinatura = self::assinatura($empresa);

        return $assinatura?->plano_solicitado_id ? PlanoPlantel::find($assinatura->plano_solicitado_id) : null;
    }

    /** Quantos animais ativos a faixa em vigor permite. NULL = ilimitado. */
    public static function limite(Empresa $empresa): ?int
    {
        $faixa = self::faixaAtual($empresa);
        if (! $faixa) {
            return $empresa->licenca_ativa ? null : self::animaisGratis();
        }

        return $faixa->limite_max;
    }

    /** A propriedade está numa faixa paga (libera também as ferramentas do plano contratado). */
    public static function ativa(Empresa $empresa): bool
    {
        return (bool) $empresa->licenca_ativa;
    }

    public static function podeCadastrarAnimal(Empresa $empresa): bool
    {
        $limite = self::limite($empresa);

        return $limite === null || self::animais($empresa) < $limite;
    }

    /** A próxima faixa acima da atual (a que o aviso de limite oferece). */
    public static function proximaFaixa(Empresa $empresa): ?PlanoPlantel
    {
        $atual = self::faixaAtual($empresa);
        if (! $atual || $atual->ilimitado()) {
            return null;
        }

        return self::faixas()->first(fn ($f) => $f->limite_min > $atual->limite_min && ($f->limite_max === null || $f->limite_max > $atual->limite_max));
    }

    /** Proprietário pede o upgrade (sem cobrança on-line por enquanto: a equipe libera). */
    public static function solicitar(Empresa $empresa, PlanoPlantel $faixa, ?int $usuarioId): void
    {
        $atual = self::faixaAtual($empresa);
        DB::table('assinatura_empresa')->updateOrInsert(['empresa_id' => $empresa->id], [
            'plano_plantel_id' => $atual?->id ?? $faixa->id,
            'plano_solicitado_id' => $faixa->id,
            'solicitado_em' => now('UTC'),
            'solicitado_por' => $usuarioId,
        ]);
    }

    /** Administração define a faixa em vigor. Faixa paga liga empresa.licenca_ativa (ferramentas do plano). */
    public static function definirFaixa(Empresa $empresa, PlanoPlantel $faixa, ?int $usuarioId): void
    {
        DB::table('assinatura_empresa')->updateOrInsert(['empresa_id' => $empresa->id], [
            'plano_plantel_id' => $faixa->id,
            'plano_solicitado_id' => null,
            'solicitado_em' => null,
            'solicitado_por' => null,
            'iniciada_em' => now('UTC'),
            'liberada_por' => $usuarioId,
        ]);
        $paga = ! $faixa->gratuito();
        $empresa->update([
            'licenca_ativa' => $paga,
            'licenca_liberada_em' => $paga ? now() : null,
            'licenca_liberada_por' => $paga ? $usuarioId : null,
        ] + ($paga && $empresa->situacao === 'teste' ? ['situacao' => 'ativo'] : []));
    }

    public static function exigir(Empresa $empresa): void
    {
        abort_unless(self::ativa($empresa), 403, 'Esta ferramenta faz parte dos planos pagos. Faça o upgrade da faixa de plantel para acessar.');
    }
}
