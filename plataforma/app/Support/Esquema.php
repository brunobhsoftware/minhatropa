<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/** Confere se os scripts de banco mais novos já foram aplicados em produção (telas avisam em vez de dar erro). */
class Esquema
{
    private static array $cache = [];

    /** V002: autocadastro do Haras, licença e animais. */
    public static function temV002(): bool
    {
        try {
            return self::$cache['v002'] ??= Schema::hasTable('animal') && Schema::hasColumn('empresa', 'licenca_ativa');
        } catch (\Throwable) {
            return false;
        }
    }

    /** V003: cadastro completo do Haras. */
    public static function temV003(): bool
    {
        try {
            return self::$cache['v003'] ??= Schema::hasTable('empresa_raca') && Schema::hasColumn('empresa', 'tipo_propriedade');
        } catch (\Throwable) {
            return false;
        }
    }

    /** V004: profissionais da plataforma. */
    public static function temV004(): bool
    {
        try {
            return self::$cache['v004'] ??= Schema::hasTable('profissional') && Schema::hasTable('empresa_profissional');
        } catch (\Throwable) {
            return false;
        }
    }

    /** V005: animais expandido, cobrições, nascimentos, transferências. */
    public static function temV005(): bool
    {
        try {
            return self::$cache['v005'] ??= Schema::hasTable('cobricao') && Schema::hasColumn('animal', 'especie');
        } catch (\Throwable) {
            return false;
        }
    }

    /** V006: CPF/CNPJ único, faixas de plantel, módulos e tabelas de estado/município. */
    public static function temV006(): bool
    {
        try {
            return self::$cache['v006'] ??= Schema::hasTable('plano_plantel') && Schema::hasTable('empresa_modulo') && Schema::hasColumn('empresa', 'municipio_id');
        } catch (\Throwable) {
            return false;
        }
    }

    /** V007: estados e municípios do IBGE carregados. */
    public static function temV007(): bool
    {
        try {
            return self::$cache['v007'] ??= self::temV006() && \Illuminate\Support\Facades\DB::table('municipio')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /** V008: página pública da propriedade (logomarca, capa, cores). Opcional: sem ele, só essa parte fica escondida. */
    public static function temV008(): bool
    {
        try {
            return self::$cache['v008'] ??= Schema::hasColumn('empresa', 'pagina_ativa');
        } catch (\Throwable) {
            return false;
        }
    }

    /** O banco tem tudo o que as telas atuais usam (até o V007)? */
    public static function atual(): bool
    {
        return self::temV002() && self::temV003() && self::temV004() && self::temV005() && self::temV007();
    }
}
