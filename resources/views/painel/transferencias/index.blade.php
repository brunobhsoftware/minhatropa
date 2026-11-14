@extends('layouts.painel')
@section('titulo', 'Transferências')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Transferências</h1><p>Saídas e entradas de animais.</p></div>
    <a href="{{ route('painel.transferencias.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Nova transferência</a>
</div>
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Data</th><th>Animal</th><th>Tipo</th><th></th><th>Origem</th><th>Destino</th><th>Situação</th><th></th></tr></thead>
        <tbody>
        @forelse ($transferencias as $t)
            @php $entrada = $t->empresa_destino_id === $empresa->id; @endphp
            <tr>
                <td>{{ $t->data_transferencia->format('d/m/Y') }}</td>
                <td><b>{{ $t->animal?->nome ?? '—' }}</b></td>
                <td>{{ $t->tipoTexto() }}</td>
                <td style="font-size:1.2em">{!! $entrada ? '⬅ <small>entrada</small>' : '➡ <small>saída</small>' !!}</td>
                <td>{{ $t->origem?->nome ?? '—' }}</td>
                <td>{{ $t->nomeDestino() }}</td>
                <td><span class="selo selo--{{ $t->situacao }}">{{ $t->situacaoTexto() }}</span></td>
                <td><a href="{{ route('painel.transferencias.show', $t) }}" class="botao botao--contorno botao--pequeno">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="vazio"><p>Nenhuma transferência registrada.</p><a href="{{ route('painel.transferencias.novo') }}" class="botao botao--primario botao--pequeno">Registrar a primeira</a></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
