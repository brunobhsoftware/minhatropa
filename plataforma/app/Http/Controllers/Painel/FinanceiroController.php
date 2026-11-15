<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\CategoriaFinanceira;
use App\Models\LancamentoFinanceiro;
use App\Support\Auditoria;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceiroController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $query = LancamentoFinanceiro::where('lancamento_financeiro.empresa_id', $empresa->id)
            ->whereNull('lancamento_financeiro.excluido_em')
            ->leftJoin('categoria_financeira', 'categoria_financeira.id', '=', 'lancamento_financeiro.categoria_financeira_id')
            ->leftJoin('animal', 'animal.id', '=', 'lancamento_financeiro.animal_id')
            ->select('lancamento_financeiro.*', 'categoria_financeira.nome as categoria_nome', 'animal.nome as animal_nome');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('lancamento_financeiro.descricao', 'like', "%{$busca}%")
                  ->orWhere('categoria_financeira.nome', 'like', "%{$busca}%")
                  ->orWhere('animal.nome', 'like', "%{$busca}%");
            });
        }
        if ($tipo = $request->input('tipo')) {
            $query->where('lancamento_financeiro.tipo', $tipo);
        }
        if ($situacao = $request->input('situacao')) {
            $query->where('lancamento_financeiro.situacao', $situacao);
        }
        if ($de = $request->input('de')) {
            $query->where('lancamento_financeiro.data_vencimento', '>=', $de);
        }
        if ($ate = $request->input('ate')) {
            $query->where('lancamento_financeiro.data_vencimento', '<=', $ate);
        }

        $lancamentos = $query->orderByDesc('lancamento_financeiro.data_vencimento')
            ->orderByDesc('lancamento_financeiro.id')->limit(200)->get();

        $totais = (object) [
            'receitas' => $lancamentos->where('tipo', 'receita')->sum('valor'),
            'despesas' => $lancamentos->where('tipo', 'despesa')->sum('valor'),
            'pendentes' => $lancamentos->where('situacao', 'pendente')->count(),
            'atrasados' => $lancamentos->where('situacao', 'atrasado')->count(),
        ];
        $totais->saldo = $totais->receitas - $totais->despesas;

        return view('painel.financeiro.index', compact('lancamentos', 'totais'));
    }

    public function criar(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        return view('painel.financeiro.form', [
            'lancamento' => new LancamentoFinanceiro(['tipo' => 'despesa', 'situacao' => 'pendente', 'data_vencimento' => now()->toDateString()]),
            'categorias' => $this->categorias($empresa->id),
            'animais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function salvar(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $dados = $this->validar($request);
        $lancamento = LancamentoFinanceiro::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        Auditoria::registrar('financeiro.criar', $lancamento);

        return redirect()->route('painel.financeiro')->with('ok', "Lançamento \"{$lancamento->descricao}\" criado.");
    }

    public function ver(Request $request, LancamentoFinanceiro $lancamento)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($lancamento->empresa_id === $empresa->id && !$lancamento->excluido_em, 404);

        $lancamento->load('categoria', 'animal:id,nome', 'criadoPor:id,nome');

        return view('painel.financeiro.show', compact('lancamento'));
    }

    public function editar(Request $request, LancamentoFinanceiro $lancamento)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($lancamento->empresa_id === $empresa->id && !$lancamento->excluido_em, 404);

        return view('painel.financeiro.form', [
            'lancamento' => $lancamento,
            'categorias' => $this->categorias($empresa->id),
            'animais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function atualizar(Request $request, LancamentoFinanceiro $lancamento)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($lancamento->empresa_id === $empresa->id && !$lancamento->excluido_em, 404);

        $antes = $lancamento->getAttributes();
        $lancamento->update($this->validar($request));
        Auditoria::registrar('financeiro.editar', $lancamento, $antes);

        return redirect()->route('painel.financeiro')->with('ok', "Lançamento \"{$lancamento->descricao}\" atualizado.");
    }

    public function excluir(Request $request, LancamentoFinanceiro $lancamento)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($lancamento->empresa_id === $empresa->id && !$lancamento->excluido_em, 404);

        $lancamento->update(['excluido_em' => now()]);
        Auditoria::registrar('financeiro.excluir', $lancamento);

        return redirect()->route('painel.financeiro')->with('ok', "Lançamento \"{$lancamento->descricao}\" excluído.");
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'tipo'                    => ['required', Rule::in(array_keys(LancamentoFinanceiro::TIPOS))],
            'categoria_financeira_id' => ['nullable', 'integer', 'exists:categoria_financeira,id'],
            'descricao'               => ['required', 'string', 'max:255'],
            'valor'                   => ['required', 'numeric', 'min:0.01'],
            'data_vencimento'         => ['required', 'date'],
            'data_pagamento'          => ['nullable', 'date'],
            'situacao'                => ['required', Rule::in(array_keys(LancamentoFinanceiro::SITUACOES))],
            'forma_pagamento'         => ['nullable', Rule::in(array_keys(LancamentoFinanceiro::FORMAS_PAGAMENTO))],
            'animal_id'               => ['nullable', 'integer', 'exists:animal,id'],
            'documento'               => ['nullable', 'string', 'max:100'],
            'observacoes'             => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function categorias(int $empresaId)
    {
        return CategoriaFinanceira::where(function ($q) use ($empresaId) {
            $q->whereNull('empresa_id')->orWhere('empresa_id', $empresaId);
        })->orderBy('nome')->get();
    }
}
