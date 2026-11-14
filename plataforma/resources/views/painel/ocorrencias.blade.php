@extends('layouts.painel')
@section('titulo', 'Ocorrências do plantel')
@section('conteudo')
@php $dono = $papel === 'proprietario'; @endphp
<div class="cabecalho">
    <div><p class="migalha">Ocorrências ›</p><h1>Ocorrências do plantel</h1><p>Os fatos dos animais {{ $haras->doRotulo() }}: nascimentos, cobrições, mortes e transferências.</p></div>
    <a href="{{ route('painel.animais') }}" class="botao botao--contorno"><x-icone nome="cavalo" :tamanho="18" /> Meu Plantel</a>
</div>

@unless ($licenca)
<div class="faixa-gratis">
    <div><b>Nascimentos, cobrições e transferências fazem parte dos planos pagos</b><p>No plano gratuito você registra o plantel e as mortes. As demais ocorrências são liberadas ao mudar de faixa.</p></div>
    @if ($dono)<a href="{{ route('painel.plano') }}" class="botao botao--escuro">Ver plano e módulos</a>@endif
</div>
@endunless

@php
    $cartoes = [
        ['nome' => 'Nascimentos', 'icone' => 'mais', 'texto' => 'Partos e produtos nascidos no plantel.', 'total' => $nascimentos, 'rota' => 'painel.nascimentos', 'novo' => 'painel.nascimentos.novo', 'ver' => $reproducao, 'plano' => true],
        ['nome' => 'Cobrições', 'icone' => 'historico', 'texto' => 'Coberturas, inseminações e diagnóstico de prenhez.', 'total' => $cobricoes, 'rota' => 'painel.cobricoes', 'novo' => 'painel.cobricoes.novo', 'ver' => $reproducao, 'plano' => true],
        ['nome' => 'Transferências', 'icone' => 'site', 'texto' => 'Vendas, doações e mudança de animais entre propriedades.', 'total' => $transferencias, 'rota' => 'painel.transferencias', 'novo' => 'painel.transferencias.novo', 'ver' => $transferir, 'plano' => true],
    ];
@endphp
<div class="ocorrencias">
    @foreach ($cartoes as $c)
        @continue(! $c['ver'])
        @if ($licenca)
            <a class="ocorrencia" href="{{ route($c['rota']) }}" data-ocorrencia="{{ \Illuminate\Support\Str::slug($c['nome']) }}">
                <b><x-icone :nome="$c['icone']" /> {{ $c['nome'] }}</b><span class="ocorrencia__numero">{{ $c['total'] }}</span><span class="suave">{{ $c['texto'] }}</span></a>
        @else
            <div class="ocorrencia ocorrencia--bloqueada" data-ocorrencia="{{ \Illuminate\Support\Str::slug($c['nome']) }}">
                <b><x-icone nome="cadeado" /> {{ $c['nome'] }}</b><span>{{ $c['texto'] }}</span><small>Disponível nos planos pagos.</small></div>
        @endif
    @endforeach
    <a class="ocorrencia" href="{{ route('painel.animais', ['situacao' => 'morto']) }}" data-ocorrencia="mortes">
        <b><x-icone nome="cavalo" /> Mortes</b><span class="ocorrencia__numero">{{ $mortes }}</span><span class="suave">Animais com morte registrada. A morte é lançada na ficha do animal.</span></a>
</div>

<section class="cartao" style="margin-top:1.5rem">
    <div class="cartao__titulo"><h2>Ocorrências {{ $haras->doRotulo() }}</h2><span class="selo selo--teste">Em breve</span></div>
    <p class="suave">Avisos de uma pessoa para outra sobre o que precisa ser feito na propriedade. Esta parte ainda vai ser construída.</p>
</section>
@endsection
