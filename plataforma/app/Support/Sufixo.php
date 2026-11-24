<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sufixo
{
    public static function disponivel(): bool
    {
        return Esquema::temTabela('sufixo_associacao');
    }

    public static function buscar(string $termo, string $associacao = 'ABCCMM', int $limite = 20): array
    {
        return DB::table('sufixo_associacao')
            ->where('associacao', $associacao)
            ->where(function ($q) use ($termo) {
                $q->where('sufixo', 'like', "%{$termo}%")
                  ->orWhere('codigo', 'like', "%{$termo}%")
                  ->orWhere('proprietario_nome', 'like', "%{$termo}%");
            })
            ->limit($limite)
            ->get()
            ->toArray();
    }

    public static function porCodigo(string $codigo, string $associacao = 'ABCCMM'): ?object
    {
        return DB::table('sufixo_associacao')
            ->where('associacao', $associacao)
            ->where('codigo', $codigo)
            ->first();
    }

    public static function porSufixo(string $sufixo, string $associacao = 'ABCCMM'): ?object
    {
        return DB::table('sufixo_associacao')
            ->where('associacao', $associacao)
            ->where('sufixo', $sufixo)
            ->first();
    }

    public static function sufixoEmUso(string $sufixo, ?int $excluirEmpresaId = null): bool
    {
        $q = DB::table('empresa')->where('sufixo', $sufixo)->whereNull('excluido_em');
        if ($excluirEmpresaId) {
            $q->where('id', '!=', $excluirEmpresaId);
        }

        return $q->exists();
    }

    public static function importarLote(array $linhas, string $associacao = 'ABCCMM'): int
    {
        $n = 0;
        foreach ($linhas as $l) {
            $codigo = trim($l['codigo'] ?? '');
            $sufixo = Texto::maiusculas(trim($l['sufixo'] ?? ''));
            if (! $codigo || ! $sufixo) {
                continue;
            }
            DB::table('sufixo_associacao')->updateOrInsert(
                ['associacao' => $associacao, 'codigo' => $codigo],
                [
                    'sufixo' => $sufixo,
                    'proprietario_nome' => Texto::maiusculas(trim($l['proprietario_nome'] ?? '')),
                    'proprietario_documento' => Documento::digitos($l['proprietario_documento'] ?? ''),
                    'proprietario_celular' => trim($l['proprietario_celular'] ?? ''),
                    'proprietario_telefone' => trim($l['proprietario_telefone'] ?? ''),
                    'uf' => strtoupper(trim($l['uf'] ?? '')),
                    'cidade' => trim($l['cidade'] ?? ''),
                    'cep' => Documento::digitos($l['cep'] ?? ''),
                    'importado_em' => now('UTC'),
                ]
            );
            $n++;
        }

        return $n;
    }
}
