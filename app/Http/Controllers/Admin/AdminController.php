<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Contato;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Support\AtualizacaoBanco;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function inicio()
    {
        $haras = Empresa::naoExcluidas();

        return view('admin.inicio', [
            'totalHaras' => (clone $haras)->count(),
            'porSituacao' => (clone $haras)->selectRaw('situacao, COUNT(*) total')->groupBy('situacao')->pluck('total', 'situacao'),
            'totalUsuarios' => Usuario::where('ativo', 1)->count(),
            'contatosNovos' => Contato::whereNull('lido_em')->count(),
            'bancoPendente' => AtualizacaoBanco::pendentes(),
            'ultimosHaras' => (clone $haras)->latest('criado_em')->limit(5)->get(),
            'ultimosContatos' => Contato::latest('criado_em')->limit(5)->get(),
        ]);
    }

    public function auditoria(Request $request)
    {
        $busca = trim((string) $request->query('busca'));
        $registros = Auditoria::with('usuario')
            ->when($busca !== '', fn ($q) => $q->where(fn ($q) => $q->where('acao', 'like', "%{$busca}%")->orWhere('tabela', 'like', "%{$busca}%")))
            ->orderByDesc('id')->paginate(50)->withQueryString();

        return view('admin.auditoria', compact('registros', 'busca'));
    }
}
