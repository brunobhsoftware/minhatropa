@extends('layouts.painel')
@section('titulo', 'Cobrições')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Cobrições</h1><p>Registros de cobrições e reprodução.</p></div>
    <a href="{{ route('painel.cobricoes.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Nova cobrição</a>
</div>

<section class="cartao">
    <form class="filtros" method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1rem">
        <input name="busca" value="{{ request('busca') }}" placeholder="Buscar por égua…" style="flex:1;min-width:180px">
        <select name="resultado" onchange="this.form.submit()">
            <option value="">Todos os resultados</option>
            @foreach (\App\Models\Cobricao::RESULTADOS as $k => $t)
                <option value="{{ $k }}" @selected(request('resultado') === $k)>{{ $t }}</option>
            @endforeach
        </select>
        <button class="botao botao--contorno botao--pequeno">Filtrar</button>
    </form>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Égua</th><th>Garanhão</th><th>Tipo</th><th>Data</th><th>Resultado</th><th></th></tr></thead>
        <tbody>
        @forelse ($cobricoes as $c)
            <tr>
                <td><b>{{ $c->egua?->nome ?? '—' }}</b></td>
                <td>{{ $c->garanhao?->nome ?? $c->garanhao_nome ?? '—' }}</td>
                <td>{{ $c->tipoTexto() }}</td>
                <td>{{ $c->data_cobricao->format('d/m/Y') }}</td>
                <td><span class="selo selo--{{ $c->resultado }}">{{ $c->resultadoTexto() }}</span></td>
                <td><a href="{{ route('painel.cobricoes.show', $c) }}" class="botao botao--contorno botao--pequeno">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="vazio"><p>Nenhuma cobrição registrada.</p><a href="{{ route('painel.cobricoes.novo') }}" class="botao botao--primario botao--pequeno">Registrar a primeira</a></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
