@extends('layouts.painel')
@section('titulo', $ferramenta)
@section('conteudo')
<div class="cabecalho"><div><p class="migalha">Plano e módulos ›</p><h1>{{ $ferramenta }}</h1></div></div>

<section class="cartao cartao--destaque" data-indisponivel="{{ \Illuminate\Support\Str::slug($ferramenta) }}">
    <div class="modulo-heroi">
        <div class="modulo-heroi__icone"><x-icone nome="cadeado" :tamanho="32" /></div>
        <div style="flex:1;min-width:240px">
            <h2>{{ $ferramenta }} ainda não está disponível para {{ $haras->nome }}</h2>
            <p>{{ $ferramenta }} faz parte dos planos pagos. {{ $haras->nome }} está no <b>{{ $faixa?->nome ?? 'plano gratuito' }}</b>, que inclui o cadastro do plantel e as Ocorrências do plantel.</p>
        </div>
    </div>
    @if ($papel === 'proprietario')
        <p>Para liberar, mude a faixa do plano na página <b>Plano e módulos</b>@if ($proxima) (a próxima faixa é <b>{{ $proxima->nome }}</b>)@endif.</p>
        <div class="acoes-licenca"><a href="{{ route('painel.plano') }}" class="botao botao--primario">Ver plano e módulos</a> <a href="{{ route('painel') }}" class="botao botao--contorno">Voltar ao início</a></div>
    @else
        <p>A contratação é feita pelo proprietário {{ $haras->doRotulo() }}, na página Plano e módulos do painel. Fale com ele para liberar.</p>
        <div class="acoes-licenca"><a href="{{ route('painel') }}" class="botao botao--contorno">Voltar ao início</a></div>
    @endif
</section>
@endsection
