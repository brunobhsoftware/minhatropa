@extends('layouts.acesso')
@section('titulo', 'Entrar')
@section('titulo_sufixo', 'MinhaTropa')
@section('conteudo')
@php $ambiente = old('ambiente', request('ambiente') === 'profissional' ? 'profissional' : 'haras'); @endphp
<h1>Entrar no MinhaTropa</h1>
<p class="suave">Escolha onde quer entrar e acesse com o e-mail e a senha do cadastro.</p>
<form method="post" action="{{ route('entrar.enviar') }}" class="formulario">
    @csrf
    <fieldset class="escolha-ambiente"><legend class="oculto-visual">Onde você quer entrar?</legend>
        <label><input type="radio" name="ambiente" value="haras" @checked($ambiente === 'haras')><span><b>Haras / Fazenda</b><small>Painel da propriedade</small></span></label>
        <label><input type="radio" name="ambiente" value="profissional" @checked($ambiente === 'profissional')><span><b>Profissional</b><small>Meu ambiente de profissional</small></span></label>
    </fieldset>
    <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
    <label class="campo"><span>Senha</span><input type="password" name="senha" required autocomplete="current-password"></label>
    <button class="botao botao--primario botao--largo">Entrar</button>
</form>
<p class="acesso__alternativa">Ainda não tem conta? <a href="{{ route('cadastro') }}">Cadastre seu Haras / Fazenda grátis</a> ou <a href="{{ route('cadastro.profissional') }}">cadastre-se como Profissional</a>.</p>
@endsection
