<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\ModuloSistema;
use Illuminate\Support\Facades\DB;

/**
 * Módulos adicionais (add-ons / feature flags) por propriedade.
 * modulo_sistema lista o que existe; empresa_modulo guarda o que cada Haras / Fazenda pediu ou tem ativo.
 * Os módulos são independentes da faixa de plantel.
 */
class Modulos
{
    public const EQUIPE = 'equipe';

    public const SAUDE = 'saude';

    public const MANEJO = 'manejo';

    public const NUTRICAO = 'nutricao';

    private static $todos = null;

    private static array $porEmpresa = [];

    /** Módulos oferecidos (ativos na plataforma), na ordem do menu. */
    public static function todos()
    {
        if (! Esquema::temV006()) {
            return collect();
        }

        return self::$todos ??= ModuloSistema::where('ativo', 1)->orderBy('ordem')->get();
    }

    /** @return array<string, string> código do módulo => situação (solicitado, ativo, desativado) */
    public static function daEmpresa(Empresa $empresa): array
    {
        if (! Esquema::temV006()) {
            return [];
        }

        return self::$porEmpresa[$empresa->id] ??= DB::table('empresa_modulo as em')
            ->join('modulo_sistema as m', 'm.id', '=', 'em.modulo_id')
            ->where('em.empresa_id', $empresa->id)->pluck('em.situacao', 'm.codigo')->all();
    }

    public static function ativo(Empresa $empresa, string $codigo): bool
    {
        return (self::daEmpresa($empresa)[$codigo] ?? null) === 'ativo';
    }

    public static function solicitado(Empresa $empresa, string $codigo): bool
    {
        return (self::daEmpresa($empresa)[$codigo] ?? null) === 'solicitado';
    }

    public static function solicitar(Empresa $empresa, ModuloSistema $modulo, ?int $usuarioId): void
    {
        if (self::ativo($empresa, $modulo->codigo)) {
            return;
        }
        DB::table('empresa_modulo')->updateOrInsert(['empresa_id' => $empresa->id, 'modulo_id' => $modulo->id],
            ['situacao' => 'solicitado', 'solicitado_em' => now('UTC'), 'solicitado_por' => $usuarioId]);
        unset(self::$porEmpresa[$empresa->id]);
    }

    public static function definir(Empresa $empresa, ModuloSistema $modulo, bool $ativo, ?int $usuarioId): void
    {
        DB::table('empresa_modulo')->updateOrInsert(['empresa_id' => $empresa->id, 'modulo_id' => $modulo->id], $ativo
            ? ['situacao' => 'ativo', 'ativado_em' => now('UTC'), 'ativado_por' => $usuarioId]
            : ['situacao' => 'desativado']);
        unset(self::$porEmpresa[$empresa->id]);
    }
}
