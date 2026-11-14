@extends('layouts.painel')
@section('titulo', 'Animais')
@section('conteudo')
@php $v5 = \App\Support\Esquema::temV005(); @endphp
<div class="cabecalho">
    <div><h1>Animais</h1><p>Plantel do Haras.</p></div>
    @if ($podeCadastrar)
    <a href="{{ route('painel.animais.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Cadastrar animal</a>
    @else
    <a href="{{ route('painel.licenca') }}" class="botao botao--escuro">🔒 Contratar para cadastrar mais</a>
    @endif
</div>

@if ($totais && $totais->total > 0)
<div class="grade-3" style="margin-bottom:1.5rem">
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Total</small><br><b style="font-size:1.5rem">{{ $totais->total }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Ativos</small><br><b style="font-size:1.5rem;color:var(--verde)">{{ $totais->ativos ?? 0 }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Machos / Fêmeas</small><br><b style="font-size:1.5rem">{{ $totais->machos ?? 0 }} / {{ $totais->femeas ?? 0 }}</b></div>
</div>
@endif

<section class="cartao">
    <form method="get" action="{{ route('painel.animais') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1rem">
        <input name="busca" value="{{ request('busca') }}" placeholder="Pesquisar por nome, registro, microchip…" style="flex:1;min-width:180px">
        @if ($v5)
        <select name="especie" style="width:auto"><option value="">Todas as espécies</option>@foreach (\App\Models\Animal::ESPECIES as $k => $t)<option value="{{ $k }}" @selected(request('especie') === $k)>{{ $t }}</option>@endforeach</select>
        <select name="situacao" style="width:auto"><option value="">Todas as situações</option>@foreach (\App\Models\Animal::SITUACOES as $k => $t)<option value="{{ $k }}" @selected(request('situacao') === $k)>{{ $t }}</option>@endforeach</select>
        @endif
        <select name="categoria" style="width:auto"><option value="">Todas as categorias</option>@foreach (\App\Models\Animal::CATEGORIAS as $k => $t)<option value="{{ $k }}" @selected(request('categoria') === $k)>{{ $t }}</option>@endforeach</select>
        <button class="botao botao--primario botao--pequeno">Filtrar</button>
    </form>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Nome</th>@if($v5)<th>Espécie</th>@endif<th>Categoria</th><th>Raça</th><th>Pelagem</th><th>Idade</th>@if($v5)<th>Situação</th>@endif</tr></thead>
        <tbody>
        @forelse ($animais as $a)
            <tr>
                <td>@if($v5)<a href="{{ route('painel.animais.show', $a) }}"><b>{{ $a->nome }}</b></a>@else <b>{{ $a->nome }}</b>@endif</td>
                @if($v5)<td>{{ $a->especieTexto() }}</td>@endif
                <td>{{ $a->categoriaTexto() }}</td>
                <td>{{ $a->raca ?: '—' }}</td>
                <td>@if($v5){{ $a->pelagemTexto() }}@else{{ $a->pelagem ?: '—' }}@endif</td>
                <td>{{ $a->data_nascimento ? \Carbon\Carbon::parse($a->data_nascimento)->age . ' anos' : '—' }}</td>
                @if($v5)<td><span class="selo selo--{{ $a->situacao }}">{{ $a->situacaoTexto() }}</span></td>@endif
            </tr>
        @empty
            <tr><td colspan="{{ $v5 ? 7 : 5 }}"><div class="vazio"><p>Nenhum animal cadastrado.</p>@if ($podeCadastrar)<a href="{{ route('painel.animais.novo') }}" class="botao botao--primario botao--pequeno">Cadastrar o primeiro</a>@endif</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
