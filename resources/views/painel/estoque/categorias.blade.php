@extends('layouts.painel')
@section('titulo', 'Categorias de estoque')
@section('conteudo')
<div class="cabecalho"><div><h1>Categorias de estoque</h1><p>Organize os itens por categoria: ração, medicamento, ferramenta, etc.</p></div></div>

<section class="cartao">
    <h2>Nova categoria</h2>
    <form method="post" action="{{ route('painel.estoque.categorias.salvar') }}" class="formulario formulario--inline">
        @csrf
        <input type="text" name="nome" value="{{ old('nome') }}" placeholder="Nome da categoria" class="campo" required maxlength="120">
        <input type="text" name="descricao" value="{{ old('descricao') }}" placeholder="Descrição (opcional)" class="campo" maxlength="500">
        <button class="botao botao--primario botao--pequeno">Criar</button>
    </form>
</section>

@if ($categorias->isEmpty())
    <div class="vazio"><p>Nenhuma categoria cadastrada. Crie acima para organizar os itens do estoque.</p></div>
@else
<div class="tabela-rolagem"><table class="tabela">
    <thead><tr><th>Categoria</th><th>Descrição</th><th class="num">Itens</th><th></th></tr></thead>
    <tbody>
    @foreach ($categorias as $cat)
        <tr>
            <td><b>{{ $cat->nome }}</b></td>
            <td>{{ $cat->descricao ?? '—' }}</td>
            <td class="num">{{ $cat->itens_count }}</td>
            <td class="num">
                @if ($cat->itens_count === 0)
                <form method="post" action="{{ route('painel.estoque.categorias.excluir', $cat) }}" onsubmit="return confirm('Excluir esta categoria?')">@csrf @method('DELETE')
                    <button class="botao botao--perigo botao--pequeno">Excluir</button>
                </form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif

<a href="{{ route('painel.estoque') }}" class="botao botao--texto">&larr; Voltar ao estoque</a>
@endsection
