<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AtualizacaoBanco;
use App\Support\Auditoria;
use Illuminate\Http\Request;

/** Atualizações do banco pela Administração: roda os scripts database/atualizacoes/V0xx sem phpMyAdmin. */
class BancoController extends Controller
{
    public function index()
    {
        return view('admin.banco', ['scripts' => AtualizacaoBanco::lista()]);
    }

    public function aplicar(Request $request)
    {
        $arquivo = (string) $request->validate(['arquivo' => ['required', 'string', 'max:120']])['arquivo'];
        try {
            $n = AtualizacaoBanco::aplicar($arquivo);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withErrors(['banco' => 'O banco recusou um comando de '.$arquivo.': '.mb_substr($e->getMessage(), 0, 300)]);
        }
        Auditoria::registrar('plataforma.banco_atualizar', $request->user(), null, ['arquivo' => $arquivo, 'comandos' => $n]);

        return redirect()->route('admin.banco')->with('ok', "{$arquivo} aplicado ({$n} comando(s)).");
    }
}
