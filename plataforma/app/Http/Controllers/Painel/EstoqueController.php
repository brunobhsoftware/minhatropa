<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\CategoriaEstoque;
use App\Models\ItemEstoque;
use App\Models\MovimentacaoEstoque;
use App\Support\Auditoria;
use App\Support\Modulos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EstoqueController extends Controller
{
    private function exigir(Request $request): \App\Models\Empresa
    {
        $empresa = $request->attributes->get('haras');
        Modulos::exigir($empresa, Modulos::NUTRICAO);

        return $empresa;
    }

    public function index(Request $request)
    {
        $empresa = $this->exigir($request);

        $query = ItemEstoque::where('empresa_id', $empresa->id)->whereNull('excluido_em');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('localizacao', 'like', "%{$busca}%");
            });
        }
        if ($cat = $request->input('categoria')) {
            $query->where('categoria_id', $cat);
        }
        if ($request->input('alerta')) {
            $query->whereNotNull('estoque_minimo')->whereColumn('estoque_atual', '<', 'estoque_minimo');
        }

        $itens = $query->orderBy('nome')->get();

        $categorias = CategoriaEstoque::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get();

        $totais = (object) [
            'total' => $itens->count(),
            'alertas' => $itens->filter(fn ($i) => $i->abaixoDoMinimo())->count(),
            'valor' => $itens->sum(fn ($i) => $i->estoque_atual * ($i->custo_unitario ?? 0)),
        ];

        return view('painel.estoque.index', compact('itens', 'categorias', 'totais'));
    }

    public function criar(Request $request)
    {
        $empresa = $this->exigir($request);

        return view('painel.estoque.form', [
            'item' => new ItemEstoque(['unidade' => 'un']),
            'categorias' => CategoriaEstoque::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        $empresa = $this->exigir($request);
        $dados = $this->validar($request, $empresa);

        $item = ItemEstoque::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        Auditoria::registrar('estoque.criar', $item);

        return redirect()->route('painel.estoque')->with('ok', "\"{$item->nome}\" cadastrado no estoque.");
    }

    public function ver(Request $request, ItemEstoque $item)
    {
        $empresa = $this->exigir($request);
        $this->doHaras($empresa, $item);

        $movimentacoes = MovimentacaoEstoque::where('item_id', $item->id)
            ->orderByDesc('data_movimentacao')->orderByDesc('id')->limit(100)->get();

        return view('painel.estoque.show', compact('item', 'movimentacoes'));
    }

    public function editar(Request $request, ItemEstoque $item)
    {
        $empresa = $this->exigir($request);
        $this->doHaras($empresa, $item);

        return view('painel.estoque.form', [
            'item' => $item,
            'categorias' => CategoriaEstoque::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(),
        ]);
    }

    public function atualizar(Request $request, ItemEstoque $item)
    {
        $empresa = $this->exigir($request);
        $this->doHaras($empresa, $item);

        $antes = $item->getAttributes();
        $dados = $this->validar($request, $empresa);
        $item->update($dados);
        Auditoria::registrar('estoque.editar', $item, $antes);

        return redirect()->route('painel.estoque.ver', $item)->with('ok', "Dados de \"{$item->nome}\" salvos.");
    }

    public function movimentar(Request $request, ItemEstoque $item)
    {
        $empresa = $this->exigir($request);
        $this->doHaras($empresa, $item);

        $dados = $request->validate([
            'tipo' => ['required', Rule::in(['entrada', 'saida', 'ajuste'])],
            'quantidade' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'custo_unitario' => ['nullable', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'data_movimentacao' => ['required', 'date', 'before_or_equal:today'],
        ]);

        DB::transaction(function () use ($item, $dados, $empresa, $request) {
            $mov = MovimentacaoEstoque::create($dados + [
                'empresa_id' => $empresa->id,
                'item_id' => $item->id,
                'criado_por' => $request->user()->id,
            ]);

            $delta = match ($dados['tipo']) {
                'entrada' => $dados['quantidade'],
                'saida' => -$dados['quantidade'],
                'ajuste' => $dados['quantidade'] - $item->estoque_atual,
            };

            if ($dados['tipo'] === 'ajuste') {
                $item->update(['estoque_atual' => $dados['quantidade']]);
            } else {
                $item->increment('estoque_atual', $delta);
            }

            if ($dados['custo_unitario'] && $dados['tipo'] === 'entrada') {
                $item->update(['custo_unitario' => $dados['custo_unitario']]);
            }

            Auditoria::registrar('estoque.movimentacao', $item, null, [
                'tipo' => $dados['tipo'], 'quantidade' => $dados['quantidade'],
            ]);
        });

        return redirect()->route('painel.estoque.ver', $item)->with('ok', MovimentacaoEstoque::TIPOS[$dados['tipo']].' registrada.');
    }

    public function excluir(Request $request, ItemEstoque $item)
    {
        $empresa = $this->exigir($request);
        $this->doHaras($empresa, $item);

        $item->update(['excluido_em' => now('UTC')]);
        Auditoria::registrar('estoque.excluir', $item);

        return redirect()->route('painel.estoque')->with('ok', "\"{$item->nome}\" removido do estoque.");
    }

    public function categorias(Request $request)
    {
        $empresa = $this->exigir($request);

        $categorias = CategoriaEstoque::where('empresa_id', $empresa->id)->whereNull('excluido_em')
            ->withCount(['itens' => fn ($q) => $q->whereNull('excluido_em')])->orderBy('nome')->get();

        return view('painel.estoque.categorias', compact('categorias'));
    }

    public function salvarCategoria(Request $request)
    {
        $empresa = $this->exigir($request);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:500'],
        ]);

        CategoriaEstoque::create($dados + ['empresa_id' => $empresa->id]);

        return redirect()->route('painel.estoque.categorias')->with('ok', "Categoria \"{$dados['nome']}\" criada.");
    }

    public function excluirCategoria(Request $request, CategoriaEstoque $categoria)
    {
        $empresa = $this->exigir($request);
        abort_unless($categoria->empresa_id === $empresa->id && ! $categoria->excluido_em, 404);

        $usada = ItemEstoque::where('categoria_id', $categoria->id)->whereNull('excluido_em')->exists();
        if ($usada) {
            return back()->with('erro', 'Essa categoria tem itens vinculados. Mova-os antes de excluir.');
        }

        $categoria->update(['excluido_em' => now('UTC')]);

        return redirect()->route('painel.estoque.categorias')->with('ok', "Categoria \"{$categoria->nome}\" removida.");
    }

    private function doHaras(\App\Models\Empresa $empresa, ItemEstoque $item): void
    {
        abort_unless($item->empresa_id === $empresa->id && ! $item->excluido_em, 404);
    }

    private function validar(Request $request, \App\Models\Empresa $empresa): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:200'],
            'categoria_id' => ['nullable', 'integer', Rule::exists('categoria_estoque', 'id')->where('empresa_id', $empresa->id)],
            'unidade' => ['required', Rule::in(array_keys(ItemEstoque::UNIDADES))],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0'],
            'custo_unitario' => ['nullable', 'numeric', 'min:0'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
