@extends('layouts.painel')
@section('titulo', 'Financeiro')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Financeiro</h1><p>Receitas, despesas e fluxo de caixa.</p></div>
    <a href="{{ route('painel.financeiro.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Novo lançamento</a>
</div>

<div class="grade-4" style="margin-bottom:1.5rem">
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Receitas</small><br><b style="font-size:1.3rem;color:#10b981">R$ {{ number_format($totais->receitas, 2, ',', '.') }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Despesas</small><br><b style="font-size:1.3rem;color:#ef4444">R$ {{ number_format($totais->despesas, 2, ',', '.') }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Saldo</small><br><b style="font-size:1.3rem;color:{{ $totais->saldo >= 0 ? '#10b981' : '#ef4444' }}">R$ {{ number_format($totais->saldo, 2, ',', '.') }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Pendentes / Atrasados</small><br><b style="font-size:1.3rem;color:#f59e0b">{{ $totais->pendentes }}</b> <span class="suave">/</span> <b style="font-size:1.3rem;color:#ef4444">{{ $totais->atrasados }}</b></div>
</div>

<section class="cartao">
    <form method="get" action="{{ route('painel.financeiro') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1rem">
        <input name="busca" value="{{ request('busca') }}" placeholder="Pesquisar…" style="flex:1;min-width:150px">
        <select name="tipo" style="width:auto">
            <option value="">Receitas e despesas</option>
            @foreach (\App\Models\LancamentoFinanceiro::TIPOS as $k => $v)
                <option value="{{ $k }}" @selected(request('tipo') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="situacao" style="width:auto">
            <option value="">Todas as situações</option>
            @foreach (\App\Models\LancamentoFinanceiro::SITUACOES as $k => $v)
                <option value="{{ $k }}" @selected(request('situacao') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <input type="date" name="de" value="{{ request('de') }}" style="width:auto" title="De">
        <input type="date" name="ate" value="{{ request('ate') }}" style="width:auto" title="Até">
        <button class="botao botao--primario botao--pequeno">Filtrar</button>
    </form>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Vencimento</th><th>Tipo</th><th>Descrição</th><th>Categoria</th><th>Valor</th><th>Situação</th><th></th></tr></thead>
        <tbody>
        @forelse ($lancamentos as $l)
            <tr>
                <td style="white-space:nowrap">{{ $l->data_vencimento->format('d/m/Y') }}</td>
                <td><span class="selo selo--pequeno" style="background:{{ \App\Models\LancamentoFinanceiro::TIPO_CORES[$l->tipo] ?? '#6b7280' }}22;color:{{ \App\Models\LancamentoFinanceiro::TIPO_CORES[$l->tipo] ?? '#6b7280' }}">{{ \App\Models\LancamentoFinanceiro::TIPOS[$l->tipo] ?? $l->tipo }}</span></td>
                <td><a href="{{ route('painel.financeiro.ver', $l) }}"><b>{{ $l->descricao }}</b></a></td>
                <td>{{ $l->categoria_nome ?? '—' }}</td>
                <td style="white-space:nowrap">R$ {{ number_format($l->valor, 2, ',', '.') }}</td>
                <td><span class="selo selo--pequeno" style="background:{{ \App\Models\LancamentoFinanceiro::SITUACAO_CORES[$l->situacao] ?? '#6b7280' }}22;color:{{ \App\Models\LancamentoFinanceiro::SITUACAO_CORES[$l->situacao] ?? '#6b7280' }}">{{ \App\Models\LancamentoFinanceiro::SITUACOES[$l->situacao] ?? $l->situacao }}</span></td>
                <td style="white-space:nowrap">
                    <a href="{{ route('painel.financeiro.editar', $l) }}" class="botao botao--escuro botao--pequeno"><x-icone nome="editar" :tamanho="14" /></a>
                    <form method="post" action="{{ route('painel.financeiro.excluir', $l) }}" style="display:inline" onsubmit="return confirm('Excluir &quot;{{ $l->descricao }}&quot;?')">@csrf @method('DELETE')
                        <button class="botao botao--escuro botao--pequeno" title="Excluir"><x-icone nome="lixeira" :tamanho="14" /></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="vazio"><p>Nenhum lançamento financeiro.</p>
                <a href="{{ route('painel.financeiro.novo') }}" class="botao botao--primario botao--pequeno">Criar o primeiro</a>
            </div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
