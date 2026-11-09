<?php

namespace App\Support;

use App\Models\Animal;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

/**
 * Regra comercial: o Haras usa grátis até cadastrar o 1º animal (parametro_sistema.animais_gratis).
 * A partir daí precisa contratar a solução; com a licença ativa todas as ferramentas são liberadas.
 */
class Licenca
{
    public static function animaisGratis(): int
    {
        return (int) (DB::table('parametro_sistema')->where('chave', 'animais_gratis')->value('valor') ?? 1);
    }

    public static function ativa(Empresa $empresa): bool
    {
        return (bool) $empresa->licenca_ativa;
    }

    public static function animais(Empresa $empresa): int
    {
        return Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->count();
    }

    public static function podeCadastrarAnimal(Empresa $empresa): bool
    {
        return self::ativa($empresa) || self::animais($empresa) < self::animaisGratis();
    }
}
