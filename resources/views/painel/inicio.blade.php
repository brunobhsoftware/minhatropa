@extends('layouts.painel')
@section('titulo', 'Início')
@section('conteudo')
<div class="cabecalho">
    <div><h1>{{ $haras->nome }}</h1><p>Olá, {{ auth()->user()->primeiroNome() }}! Este é o painel do seu Haras.</p></div>
    <a href="{{ route('painel.animais.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Cadastrar animal</a>
</div>

@unless ($licenca)
<div class="faixa-gratis">
    <div><b>Período gratuito</b>
        <p>Você pode configurar o Haras e cadastrar {{ $gratis }} animal sem custo ({{ min($totalAnimais, $gratis) }} de {{ $gratis }} usado). A partir do {{ $gratis + 1 }}º animal, contrate o MinhaTropa para liberar o cadastro completo do plantel e todas as ferramentas.</p></div>
    <a href="{{ route('painel.licenca') }}" class="botao botao--escuro">Quero contratar</a>
</div>
@endunless

<div class="numeros">
    <a class="numero" href="{{ route('painel.animais') }}"><x-icone nome="cavalo" /><b>{{ $totalAnimais }}</b><span>Animais cadastrados</span></a>
    <a class="numero" href="{{ route('painel.haras') }}"><x-icone nome="haras" /><b>{{ $haras->cidadeUf() }}</b><span>Dados do Haras</span></a>
    <a class="numero" href="{{ route('painel.licenca') }}"><x-icone nome="chave" /><b>{{ $licenca ? 'Ativa' : 'Grátis' }}</b><span>Licença</span></a>
</div>

<section class="cartao">
    <div class="cartao__titulo"><h2>Primeiros passos</h2></div>
    @php $dadosOk = $haras->cep && $haras->logradouro; @endphp
    <ol class="passos-painel">
        <li class="feito">Cadastrar o Haras</li>
        <li class="{{ $dadosOk ? 'feito' : '' }}"><a href="{{ route('painel.haras') }}">Completar endereço e contatos do Haras</a></li>
        <li class="{{ $totalAnimais > 0 ? 'feito' : '' }}"><a href="{{ route('painel.animais.novo') }}">Cadastrar o primeiro animal (grátis)</a></li>
        <li class="{{ $licenca ? 'feito' : '' }}"><a href="{{ route('painel.licenca') }}">Contratar o MinhaTropa e liberar todas as ferramentas</a></li>
    </ol>
</section>

@if ($ultimos->isNotEmpty())
<section class="cartao">
    <div class="cartao__titulo"><h2>Animais</h2><a href="{{ route('painel.animais') }}">Ver todos</a></div>
    @foreach ($ultimos as $a)
        <a class="linha-lista" href="{{ route('painel.animais.editar', $a) }}"><span><b>{{ $a->nome }}</b><small>{{ \App\Models\Animal::CATEGORIAS[$a->categoria] ?? 'Sem categoria' }} · {{ $a->raca ?: 'raça não informada' }}</small></span><small>{{ $a->idade() }}</small></a>
    @endforeach
</section>
@endif
@endsection
