<?php

namespace App\Support;

use App\Models\Auditoria as Registro;
use Illuminate\Database\Eloquent\Model;

/** Grava na tabela auditoria quem fez o quê. Senhas e segredos nunca vão para o registro. */
class Auditoria
{
    private const OCULTO = ['senha_hash', 'remember_token'];

    public static function registrar(string $acao, ?Model $registro = null, ?array $antes = null, ?array $depois = null, ?int $empresaId = null): void
    {
        $limpar = fn (?array $dados) => $dados === null ? null : array_diff_key($dados, array_flip(self::OCULTO));

        Registro::create([
            'empresa_id' => $empresaId ?? ($registro?->getTable() === 'empresa' ? $registro->getKey() : ($registro->empresa_id ?? null)),
            'usuario_id' => auth()->id(),
            'acao' => $acao,
            'tabela' => $registro?->getTable(),
            'registro_id' => $registro?->getKey(),
            'antes' => $limpar($antes),
            'depois' => $limpar($depois ?? $registro?->getAttributes()),
            'ip' => request()?->ip(),
        ]);
    }
}
