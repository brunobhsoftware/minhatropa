@extends('layouts.acesso')
@section('titulo', 'Entrar')
@section('titulo_sufixo', 'MinhaTropa')
@section('conteudo')
<h1>Entrar no seu Haras</h1>
<p class="suave">Acesse com o e-mail e a senha do cadastro.</p>
<form method="post" action="{{ route('entrar.enviar') }}" class="formulario">
    @csrf
    <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
    <label class="campo"><span>Senha</span><input type="password" name="senha" required autocomplete="current-password"></label>
    <button class="botao botao--primario botao--largo">Entrar</button>
</form>
<p class="acesso__alternativa">Ainda não tem conta? <a href="{{ route('cadastro') }}">Cadastre seu Haras grátis</a></p>
@endsection
