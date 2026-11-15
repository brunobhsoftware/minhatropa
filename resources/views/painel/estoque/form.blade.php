@extends('layouts.painel')
@section('titulo', $item->exists ? 'Editar '.$item->nome : 'Novo item de estoque')
@section('conteudo')
<div class="cabecalho"><div><h1>{{ $item->exists ? 'Editar item' : 'Novo item de estoque' }}</h1></div></div>

<form method="post" action="{{ $item->exists ? route('painel.estoque.atualizar', $item) : route('painel.estoque.salvar') }}" class="formulario cartao">
    @csrf
    @if ($item->exists) @method('PUT') @endif

    <div class="campo-grupo">
        <div class="campo-linha">
            <label class="campo-label">Nome do item *</label>
            <input type="text" name="nome" value="{{ old('nome', $item->nome) }}" class="campo" required maxlength="200" autofocus>
        </div>
        <div class="campo-linha campo-linha--curto">
            <label class="campo-label">Unidade *</label>
            <select name="unidade" class="campo" required>
                @foreach (\App\Models\ItemEstoque::UNIDADES as $sigla => $texto)
                    <option value="{{ $sigla }}" @selected(old('unidade', $item->unidade) === $sigla)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="campo-grupo">
        <div class="campo-linha">
            <label class="campo-label">Categoria</label>
            <select name="categoria_id" class="campo">
                <option value="">Sem categoria</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->id }}" @selected(old('categoria_id', $item->categoria_id) == $cat->id)>{{ $cat->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="campo-linha campo-linha--curto">
            <label class="campo-label">Estoque mínimo</label>
            <input type="number" name="estoque_minimo" value="{{ old('estoque_minimo', $item->estoque_minimo) }}" class="campo" step="any" min="0">
        </div>
    </div>

    <div class="campo-grupo">
        <div class="campo-linha campo-linha--curto">
            <label class="campo-label">Custo unitário (R$)</label>
            <input type="number" name="custo_unitario" value="{{ old('custo_unitario', $item->custo_unitario) }}" class="campo" step="0.01" min="0">
        </div>
        <div class="campo-linha">
            <label class="campo-label">Localização</label>
            <input type="text" name="localizacao" value="{{ old('localizacao', $item->localizacao) }}" class="campo" maxlength="120" placeholder="Galpão, baia, prateleira…">
        </div>
    </div>

    <div class="campo-linha">
        <label class="campo-label">Observações</label>
        <textarea name="observacoes" class="campo" rows="3" maxlength="2000">{{ old('observacoes', $item->observacoes) }}</textarea>
    </div>

    <div class="form-acoes">
        <a href="{{ $item->exists ? route('painel.estoque.ver', $item) : route('painel.estoque') }}" class="botao botao--escuro">Cancelar</a>
        <button class="botao botao--primario">{{ $item->exists ? 'Salvar alterações' : 'Cadastrar item' }}</button>
    </div>
</form>
@endsection
