@extends('layouts.painel')
@section('titulo', ($transferencia->animal?->nome ?? 'Transferência') . ' — ' . $transferencia->tipoTexto())
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.transferencias') }}">Transferências</a> ›</p><h1>{{ $transferencia->animal?->nome ?? 'Animal' }} — {{ $transferencia->tipoTexto() }}</h1></div></div>

<div class="grade-2">
    <section class="cartao">
        <h2>Dados da transferência</h2>
        <dl class="dados">
            <div><dt>Tipo</dt><dd>{{ $transferencia->tipoTexto() }}</dd></div>
            <div><dt>Data</dt><dd>{{ $transferencia->data_transferencia->format('d/m/Y') }}</dd></div>
            @if ($transferencia->valor)
            <div><dt>Valor</dt><dd>R$ {{ number_format($transferencia->valor, 2, ',', '.') }}</dd></div>
            @endif
            <div><dt>Situação</dt><dd><span class="selo selo--{{ $transferencia->situacao }}">{{ $transferencia->situacaoTexto() }}</span></dd></div>
            <div><dt>Registrado em</dt><dd>{{ data_local($transferencia->criado_em) }}</dd></div>
        </dl>
    </section>
    <section class="cartao">
        <h2>Animal e Haras</h2>
        <dl class="dados">
            <div><dt>Animal</dt><dd>@if ($transferencia->animal)<a href="{{ route('painel.animais.show', $transferencia->animal) }}"><b>{{ $transferencia->animal->nome }}</b></a>@else — @endif</dd></div>
            <div><dt>Origem</dt><dd>{{ $transferencia->origem?->nome ?? '—' }}</dd></div>
            <div><dt>Destino</dt><dd>{{ $transferencia->nomeDestino() }}</dd></div>
        </dl>
    </section>
</div>

@if ($transferencia->observacoes)
<section class="cartao"><h2>Observações</h2><p class="pre">{{ $transferencia->observacoes }}</p></section>
@endif

@if ($transferencia->motivo_recusa)
<section class="cartao"><h2>Motivo da recusa</h2><p>{{ $transferencia->motivo_recusa }}</p></section>
@endif

@php $empresa = request()->attributes->get('haras'); @endphp
@if ($transferencia->situacao === 'solicitada' && $transferencia->empresa_destino_id === $empresa->id)
<section class="cartao">
    <h2>Responder solicitação</h2>
    <form method="post" action="{{ route('painel.transferencias.responder', $transferencia) }}" class="formulario">
        @csrf @method('put')
        <div style="margin-bottom:1rem">
            <label class="opcao-check"><input type="radio" name="resposta" value="aceita" required> <span class="selo selo--ativo">Aceitar</span></label>
            <label class="opcao-check" style="margin-left:1.5rem"><input type="radio" name="resposta" value="recusada"> <span class="selo selo--morto">Recusar</span></label>
        </div>
        <label class="campo"><span>Motivo da recusa (se recusar)</span><input name="motivo_recusa" maxlength="255"></label>
        <button class="botao botao--primario">Enviar resposta</button>
    </form>
</section>
@endif
@endsection
