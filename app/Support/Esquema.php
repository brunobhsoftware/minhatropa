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
}
