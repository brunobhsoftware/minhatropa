<?php

namespace App\Support;

/** CPF e CNPJ: limpeza, validação dos dígitos e máscara. */
class Documento
{
    public static function digitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    public static function tipo(?string $valor): ?string
    {
        return match (strlen(self::digitos($valor))) {
            11 => 'cpf',
            14 => 'cnpj',
            default => null,
        };
    }

    public static function valido(?string $valor): bool
    {
        $d = self::digitos($valor);
        if (preg_match('/^(\d)\1+$/', $d)) {
            return false;
        }
        if (strlen($d) === 11) {
            for ($t = 9; $t < 11; $t++) {
                $s = 0;
                for ($i = 0; $i < $t; $i++) {
                    $s += (int) $d[$i] * (($t + 1) - $i);
                }
                if ((int) $d[$t] !== ((10 * $s) % 11) % 10) {
                    return false;
                }
            }

            return true;
        }
        if (strlen($d) === 14) {
            foreach ([12, 13] as $t) {
                $pesos = $t === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
                $s = 0;
                for ($i = 0; $i < $t; $i++) {
                    $s += (int) $d[$i] * $pesos[$i];
                }
                $r = $s % 11;
                if ((int) $d[$t] !== ($r < 2 ? 0 : 11 - $r)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public static function formatar(?string $valor): string
    {
        $d = self::digitos($valor);

        return match (strlen($d)) {
            11 => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $d),
            14 => preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $d),
            default => (string) $valor,
        };
    }
}
