@extends('layouts.acesso')
@section('titulo', 'Entrar')
@section('conteudo')
<h1>Entrar na Administração</h1>
<p class="suave">Acesso exclusivo da equipe MinhaTropa.</p>
<form method="post" action="{{ route('admin.entrar.enviar') }}" class="formulario">
    @csrf
    <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
    <label class="campo"><span>Senha</span><input type="password" name="senha" required autocomplete="current-password"></label>
    <button class="botao botao--primario botao--largo">Entrar</button>
</form>
@endsection
