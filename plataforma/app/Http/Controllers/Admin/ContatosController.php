<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contato;
use Illuminate\Http\Request;

/** Pedidos de contato feitos no formulário do site. */
class ContatosController extends Controller
{
    public function index(Request $request)
    {
        $filtro = $request->query('filtro', 'todos');
        $contatos = Contato::when($filtro === 'novos', fn ($q) => $q->whereNull('lido_em'))
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.contatos', compact('contatos', 'filtro'));
    }

    public function lido(Contato $contato)
    {
        $contato->update(['lido_em' => $contato->lido_em ? null : now()]);

        return back()->with('ok', $contato->lido_em ? 'Contato marcado como atendido.' : 'Contato voltou para "novos".');
    }
}
