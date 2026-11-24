<?php

namespace App\Support;

/**
 * Padronização de texto compartilhada pelo cadastro e pela importação.
 *
 * maiusculas(): deixa os nomes de animais (nome, nome de registro, apelido) 100% em MAIÚSCULAS, sem regras de
 * capitalização — padrão único em todo o sistema, evitando cadastros sem padronização. Só normaliza os espaços.
 */
class Texto
{
    public static function maiusculas(?string $s): ?string
    {
        if ($s === null) {
            return null;
        }
        $s = preg_replace('/\s+/u', ' ', trim($s));
        if ($s === '') {
            return $s;
        }

        return mb_strtoupper($s, 'UTF-8');
    }
}
