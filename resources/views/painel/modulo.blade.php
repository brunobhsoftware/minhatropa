@extends('layouts.painel')
@section('titulo', $modulo->nome)
@section('conteudo')
<div class="cabecalho"><div><p class="migalha">Módulos adicionais ›</p><h1>{{ $modulo->nome }}</h1></div></div>

@if ($ativo)
<section class="cartao cartao--destaque">
    <div class="modulo-heroi">
        <div class="modulo-heroi__icone"><x-icone :nome="$modulo->icone ?: 'check'" :tamanho="32" /></div>
        <div style="flex:1;min-width:240px">
            <h2>Módulo ativo <span class="selo selo--ativo">Liberado</span></h2>
            <p>{{ $modulo->resumo }}</p>
            <p class="suave">Este módulo já está liberado para {{ $haras->nome }}. As telas dele estão em preparação e aparecem aqui assim que ficarem prontas, sem nenhuma ação sua.</p>
        </div>
    </div>
    <ul class="modulo-beneficios">@foreach ($modulo->beneficiosLista() as $b)<li>{{ $b }}</li>@endforeach</ul>
</section>
@else
<section class="cartao cartao--destaque" data-modulo-bloqueado="{{ $modulo->codigo }}">
    <div class="modulo-heroi">
        <div class="modulo-heroi__icone"><x-icone nome="cadeado" :tamanho="32" /></div>
        <div style="flex:1;min-width:240px">
            <h2>{{ $modulo->nome }} <span class="selo selo--encerrado">Módulo adicional</span></h2>
            <p>{{ $modulo->resumo }}</p>
        </div>
    </div>
    <h3 style="margin-top:18px">O que este módulo traz</h3>
    <ul class="modulo-beneficios">@foreach ($modulo->beneficiosLista() as $b)<li>{{ $b }}</li>@endforeach</ul>
    <p><b>Valor:</b> {{ $modulo->valorTexto() }} <span class="suave">· contratado à parte, em qualquer faixa de plantel.</span></p>

    @if ($solicitado)
        <div class="alerta alerta--aviso"><p>Ativação pedida. Nossa equipe entra em contato para liberar o módulo.</p></div>
    @elseif ($podeSolicitar)
        <form method="post" action="{{ route('painel.modulos.solicitar', $modulo) }}" class="acoes-licenca">
            @csrf
            <button class="botao botao--primario">Solicitar ativação</button>
            <a class="botao botao--contorno" href="mailto:atendimento@minhatropa.com.br?subject={{ rawurlencode('Módulo '.$modulo->nome.' — '.$haras->nome) }}">Tirar dúvidas</a>
        </form>
        <p class="suave"><small>Ainda sem pagamento on-line: ao solicitar, nossa equipe combina a contratação e libera o módulo.</small></p>
    @else
        <div class="alerta alerta--aviso"><p>Este módulo ainda não foi contratado para {{ $haras->nome }}. Peça ao proprietário para solicitar a ativação.</p></div>
    @endif
</section>
@endif
@endsection
