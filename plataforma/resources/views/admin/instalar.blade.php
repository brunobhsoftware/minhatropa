@extends('layouts.acesso')
@section('titulo', 'Instalação')
@section('largura', 'acesso__caixa--larga')
@section('conteudo')
<h1>Instalação do MinhaTropa</h1>
<p class="suave">Este passo é feito uma única vez: cria as tabelas no banco <b>{{ $banco }}</b> e o seu acesso de administrador. Depois disso, esta página deixa de existir e todas as mudanças de banco passam a ser feitas por <b>Administração → Atualizações do banco</b>.</p>

@if ($erroConexao)
    <div class="alerta alerta--erro"><p>{{ $erroConexao }}</p></div>
@else
<form method="post" action="{{ route('admin.instalar.executar') }}" class="formulario">
    @csrf
    <fieldset>
        <legend>1. Confirme que você é o dono da instalação</legend>
        <label class="campo"><span>Senha do banco de dados</span>
            <input type="password" name="senha_banco" required autocomplete="off">
            <small>A mesma senha do usuário do banco que está no arquivo <code>.env.minhatropa</code> do servidor.</small></label>
    </fieldset>
    <fieldset>
        <legend>2. Seu acesso de administrador</legend>
        <label class="campo"><span>Nome</span><input type="text" name="nome" value="{{ old('nome') }}" required maxlength="150"></label>
        <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
        <div class="grade-2">
            <label class="campo"><span>Senha</span><input type="password" name="senha" required minlength="10" autocomplete="new-password"><small>Mínimo de 10 caracteres, com letras e números.</small></label>
            <label class="campo"><span>Repita a senha</span><input type="password" name="senha_confirmation" required minlength="10" autocomplete="new-password"></label>
        </div>
    </fieldset>
    <button class="botao botao--primario botao--largo">Instalar e entrar</button>
</form>
@endif
@endsection
