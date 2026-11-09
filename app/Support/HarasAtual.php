<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\Usuario;

/** Qual Haras a pessoa logada está usando agora (sessão). Quem tem acesso a vários escolhe na troca. */
class HarasAtual
{
    public const CHAVE = 'haras_id';

    /** @return \Illuminate\Support\Collection<int, Empresa> */
    public static function disponiveis(Usuario $usuario)
    {
        return $usuario->empresas()->wherePivot('status', 'ativo')->whereNull('empresa.excluido_em')
            ->whereIn('empresa.situacao', ['ativo', 'teste'])->orderBy('nome')->get();
    }

    public static function obter(Usuario $usuario): ?Empresa
    {
        $id = session(self::CHAVE);
        $lista = self::disponiveis($usuario);
        $empresa = $id ? $lista->firstWhere('id', $id) : null;
        if (! $empresa && $lista->count() === 1) {
            $empresa = $lista->first();
            session([self::CHAVE => $empresa->id]);
        }

        return $empresa;
    }
}
