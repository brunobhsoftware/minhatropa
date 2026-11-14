@extends('layouts.painel')
@section('titulo', 'Início')
@section('conteudo')
@php
    $dono = $papel === 'proprietario';
    $podeAnimais = \App\Support\Permissao::pode($papel, 'animais', true);
    $cheio = $limite !== null && $totalAnimais >= $limite;
@endphp
<div class="cabecalho">
    <div><h1>{{ $haras->nome }}</h1><p>Olá, {{ auth()->user()->primeiroNome() }}! Este é o painel {{ $haras->doRotulo() }}.</p></div>
    @if ($podeAnimais)<a href="{{ route('painel.animais.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Cadastrar animal</a>@endif
</div>

@if ($dono && $limite !== null)
<div class="faixa-gratis">
    <div><b>{{ $faixa?->nome ?? 'Plano gratuito' }} — {{ $totalAnimais }} de {{ $limite }} animais ativos</b>
        <div class="uso-plantel {{ $cheio ? 'uso-plantel--cheio' : '' }}" style="max-width:420px"><span style="width:{{ min(100, $limite ? round($totalAnimais / $limite * 100) : 0) }}%"></span></div>
        <p>@if ($cheio)Você chegou ao limite desta faixa.@else Você ainda pode cadastrar {{ $limite - $totalAnimais }} {{ $limite - $totalAnimais === 1 ? 'animal' : 'animais' }} nesta faixa.@endif
            @if ($proxima) A próxima é a {{ $proxima->nome }} ({{ mb_strtolower($proxima->faixaTexto()) }}).@endif</p></div>
    <a href="{{ route('painel.plano') }}" class="botao botao--escuro">{{ $cheio ? 'Fazer upgrade' : 'Ver plano e módulos' }}</a>
</div>
@endif

<div class="numeros">
    @if (\App\Support\Permissao::pode($papel, 'animais'))<a class="numero" href="{{ route('painel.animais') }}"><x-icone nome="cavalo" /><b>{{ $totalAnimais }}</b><span>Animais ativos no plantel</span></a>@endif
    @if (\App\Support\Permissao::pode($papel, 'propriedade'))<a class="numero" href="{{ route('painel.haras') }}"><x-icone nome="haras" /><b>{{ $haras->cidadeUf() }}</b><span>Dados {{ $haras->doRotulo() }}</span></a>@endif
    @if ($dono)<a class="numero" href="{{ route('painel.plano') }}"><x-icone nome="plano" /><b>{{ $faixa?->nome ?? 'Gratuito' }}</b><span>Plano e módulos</span></a>@endif
</div>

@if ($dono)
<section class="cartao">
    <div class="cartao__titulo"><h2>Primeiros passos</h2></div>
    @php $dadosOk = $haras->cep && $haras->logradouro; @endphp
    <ol class="passos-painel">
        <li class="feito">Cadastrar {{ $haras->tipo_propriedade === 'fazenda' ? 'a Fazenda' : 'o '.$haras->rotulo() }}</li>
        <li class="{{ $dadosOk ? 'feito' : '' }}"><a href="{{ route('painel.haras') }}">Completar endereço e contatos {{ $haras->doRotulo() }}</a></li>
        <li class="{{ $totalAnimais > 0 ? 'feito' : '' }}"><a href="{{ route('painel.animais.novo') }}">Cadastrar o primeiro animal</a></li>
        <li><a href="{{ route('painel.profissionais') }}">Vincular a equipe pelo CPF</a></li>
        <li><a href="{{ route('painel.plano') }}">Conhecer as faixas de plantel e os módulos adicionais</a></li>
    </ol>
</section>
@endif

@if ($ultimos->isNotEmpty() && \App\Support\Permissao::pode($papel, 'animais'))
<section class="cartao">
    <div class="cartao__titulo"><h2>Animais</h2><a href="{{ route('painel.animais') }}">Ver todos</a></div>
    @foreach ($ultimos as $a)
        <a class="linha-lista" href="{{ route('painel.animais.show', $a) }}"><span><b>{{ $a->nome }}</b><small>{{ \App\Models\Animal::CATEGORIAS[$a->categoria] ?? 'Sem categoria' }} · {{ $a->raca ?: 'raça não informada' }}</small></span><small>{{ $a->idade() }}</small></a>
    @endforeach
</section>
@endif
@endsection
