@extends('layouts.painel')
@section('titulo', 'Rotinas')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Rotinas</h1><p>Rotinas programadas do Haras.</p></div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('painel.agenda') }}" class="botao botao--escuro botao--pequeno"><x-icone nome="calendario" :tamanho="16" /> Agenda</a>
        <a href="{{ route('painel.rotinas.nova') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Nova rotina</a>
    </div>
</div>

<section class="cartao">
    <form method="get" action="{{ route('painel.rotinas') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1rem">
        <input name="busca" value="{{ request('busca') }}" placeholder="Pesquisar por título…" style="flex:1;min-width:180px">
        <select name="tipo" style="width:auto">
            <option value="">Todos os tipos</option>
            @foreach ($tipos as $t)
                <option value="{{ $t->id }}" @selected(request('tipo') == $t->id)>{{ $t->icone }} {{ $t->nome }}</option>
            @endforeach
        </select>
        <select name="frequencia" style="width:auto">
            <option value="">Todas as frequências</option>
            @foreach (\App\Models\Rotina::FREQUENCIAS as $k => $v)
                <option value="{{ $k }}" @selected(request('frequencia') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="ativa" style="width:auto">
            <option value="">Todas</option>
            <option value="1" @selected(request('ativa') === '1')>Ativas</option>
            <option value="0" @selected(request('ativa') === '0')>Inativas</option>
        </select>
        <button class="botao botao--primario botao--pequeno">Filtrar</button>
    </form>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Título</th><th>Tipo</th><th>Animal</th><th>Frequência</th><th>Prioridade</th><th>Ativa</th><th></th></tr></thead>
        <tbody>
        @forelse ($rotinas as $r)
            <tr>
                <td><b>{{ $r->titulo }}</b></td>
                <td>@if($r->tipoRotina){{ $r->tipoRotina->icone }} {{ $r->tipoRotina->nome }}@else —@endif</td>
                <td>{{ $r->animal?->nome ?? 'Todo o Haras' }}</td>
                <td>{{ $r->frequenciaTexto() }}</td>
                <td><span class="selo selo--{{ $r->prioridade }} selo--pequeno">{{ \App\Models\Rotina::PRIORIDADES[$r->prioridade] }}</span></td>
                <td>@if($r->ativa)<span style="color:var(--verde)">Sim</span>@else<span class="suave">Não</span>@endif</td>
                <td style="white-space:nowrap">
                    <a href="{{ route('painel.rotinas.editar', $r) }}" class="botao botao--escuro botao--pequeno"><x-icone nome="editar" :tamanho="14" /></a>
                    <form method="post" action="{{ route('painel.rotinas.excluir', $r) }}" style="display:inline" onsubmit="return confirm('Excluir a rotina &quot;{{ $r->titulo }}&quot;?')">@csrf @method('DELETE')
                        <button class="botao botao--escuro botao--pequeno" title="Excluir"><x-icone nome="lixeira" :tamanho="14" /></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="vazio"><p>Nenhuma rotina cadastrada.</p>
                <a href="{{ route('painel.rotinas.nova') }}" class="botao botao--primario botao--pequeno">Criar a primeira</a>
            </div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
