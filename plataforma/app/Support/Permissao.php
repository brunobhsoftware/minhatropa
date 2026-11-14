<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\Usuario;

/**
 * Controle de acesso por função (RBAC) dentro de um Haras / Fazenda.
 * O papel vem de empresa_usuario.papel. O proprietário pode tudo; a equipe com acesso externo
 * trabalha dentro do que o papel permite e nunca vê a área de assinatura (plano, módulos, cobrança).
 */
class Permissao
{
    public const LER = 'ler';

    public const ESCREVER = 'escrever';

    /** Áreas da propriedade. */
    public const AREAS = ['animais', 'reproducao', 'transferencias', 'equipe', 'propriedade', 'assinatura', 'saude', 'manejo', 'nutricao'];

    /** papel => área => nível. Área ausente = sem acesso. */
    private const MATRIZ = [
        'gerente' => ['animais' => self::ESCREVER, 'reproducao' => self::ESCREVER, 'transferencias' => self::ESCREVER, 'equipe' => self::ESCREVER,
            'propriedade' => self::ESCREVER, 'saude' => self::ESCREVER, 'manejo' => self::ESCREVER, 'nutricao' => self::ESCREVER],
        'veterinario' => ['animais' => self::LER, 'reproducao' => self::ESCREVER, 'saude' => self::ESCREVER, 'manejo' => self::LER],
        'treinador' => ['animais' => self::LER, 'manejo' => self::ESCREVER],
        'tratador' => ['animais' => self::LER, 'manejo' => self::ESCREVER, 'nutricao' => self::ESCREVER],
        'financeiro' => ['animais' => self::LER, 'propriedade' => self::LER],
        'consulta' => ['animais' => self::LER, 'reproducao' => self::LER, 'transferencias' => self::LER, 'equipe' => self::LER,
            'propriedade' => self::LER, 'saude' => self::LER, 'manejo' => self::LER, 'nutricao' => self::LER],
    ];

    /** Papéis que o proprietário pode dar a um profissional com acesso externo. */
    public const PAPEIS_EQUIPE = [
        'gerente' => 'Gerente (administra a propriedade)',
        'veterinario' => 'Veterinário (saúde e reprodução)',
        'treinador' => 'Treinador (manejo)',
        'tratador' => 'Tratador / peão (manejo e alimentação)',
        'consulta' => 'Só consulta',
    ];

    /** Papel da pessoa nesta propriedade. A equipe da plataforma entra como proprietário (suporte). */
    public static function papel(Usuario $usuario, Empresa $empresa): string
    {
        $papel = $empresa->pivot->papel ?? $usuario->empresas()->where('empresa.id', $empresa->id)->first()?->pivot->papel;

        return $papel ?: ($usuario->admin_plataforma ? 'proprietario' : 'consulta');
    }

    public static function pode(string $papel, string $area, bool $escrita = false): bool
    {
        if ($papel === 'proprietario') {
            return true;
        }
        $nivel = self::MATRIZ[$papel][$area] ?? null;

        return $nivel !== null && (! $escrita || $nivel === self::ESCREVER);
    }
}
