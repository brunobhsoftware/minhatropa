@extends('layouts.painel')
@section('titulo', 'Controle de estoque')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Controle de estoque</h1><p>Ração, medicamentos, ferramentas e materiais {{ $haras->doRotulo() }}.</p></div>
    <div class="cabecalho__acoes">
        <a href="{{ route('painel.estoque.categorias') }}" class="botao botao--escuro"><x-icone nome="config" :tamanho="18" /> Categorias</a>
        <a href="{{ route('painel.estoque.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Novo item</a>
    </div>
</div>

<div class="resumo-cards">
    <div class="resumo-card"><span class="resumo-card__numero">{{ $totais->total }}</span><span class="resumo-card__rotulo">Itens</span></div>
    <div class="resumo-card{{ $totais->alertas ? ' resumo-card--alerta' : '' }}"><span class="resumo-card__numero">{{ $totais->alertas }}</span><span class="resumo-card__rotulo">Abaixo do mínimo</span></div>
    <div class="resumo-card"><span class="resumo-card__numero">R$ {{ number_format($totais->valor, 2, ',', '.') }}</span><span class="resumo-card__rotulo">Valor estimado</span></div>
</div>

<form class="filtros" method="get">
    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar item…" class="campo">
    @if ($categorias->isNotEmpty())
    <select name="categoria" class="campo">
        <option value="">Todas as categorias</option>
        @foreach ($categorias as $cat)
            <option value="{{ $cat->id }}" @selected(request('categoria') == $cat->id)>{{ $cat->nome }}</option>
        @endforeach
    </select>
    @endif
    <label class="filtro-check"><input type="checkbox" name="alerta" value="1" @checked(request('alerta'))> Só abaixo do mínimo</label>
    <button class="botao botao--escuro botao--pequeno">Filtrar</button>
    @if (request()->hasAny(['busca','categoria','alerta']))
        <a href="{{ route('painel.estoque') }}" class="botao botao--texto botao--pequeno">Limpar</a>
    @endif
</form>

@if ($itens->isEmpty())
    <div class="vazio"><p>Nenhum item encontrado no estoque.</p><a href="{{ route('painel.estoque.novo') }}" class="botao botao--primario botao--pequeno">Cadastrar primeiro item</a></div>
@else
<div class="tabela-rolagem"><table class="tabela">
    <thead><tr><th>Item</th><th>Categoria</th><th class="num">Estoque</th><th class="num">Mínimo</th><th class="num">Custo un.</th><th>Local</th></tr></thead>
    <tbody>
    @foreach ($itens as $item)
        <tr>
            <td><a href="{{ route('painel.estoque.ver', $item) }}"><b>{{ $item->nome }}</b></a>
                @if ($item->abaixoDoMinimo())<br><span class="selo selo--alerta">Abaixo do mínimo</span>@endif</td>
            <td>{{ $item->categoria?->nome ?? '—' }}</td>
            <td class="num">{{ rtrim(rtrim(number_format($item->estoque_atual, 3, ',', '.'), '0'), ',') }} {{ $item->unidade }}</td>
            <td class="num">{{ $item->estoque_minimo !== null ? rtrim(rtrim(number_format($item->estoque_minimo, 3, ',', '.'), '0'), ',').' '.$item->unidade : '—' }}</td>
            <td class="num">{{ $item->custo_unitario ? 'R$ '.number_format($item->custo_unitario, 2, ',', '.') : '—' }}</td>
            <td>{{ $item->localizacao ?? '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
@endsection
