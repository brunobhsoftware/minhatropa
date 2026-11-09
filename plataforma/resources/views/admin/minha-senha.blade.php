@extends('layouts.admin')
@section('titulo', 'Minha senha')
@section('conteudo')
<div class="cabecalho"><div><h1>Minha senha</h1></div></div>
<section class="cartao cartao--estreito">
<form method="post" action="{{ route('admin.minha-senha.salvar') }}" class="formulario">
    @csrf @method('put')
    <label class="campo"><span>Senha atual</span><input type="password" name="senha_atual" required autocomplete="current-password"></label>
    <label class="campo"><span>Nova senha</span><input type="password" name="senha" required minlength="10" autocomplete="new-password"><small>Mínimo de 10 caracteres, com letras e números.</small></label>
    <label class="campo"><span>Repita a nova senha</span><input type="password" name="senha_confirmation" required minlength="10" autocomplete="new-password"></label>
    <button class="botao botao--primario">Alterar senha</button>
</form>
</section>
@endsection
