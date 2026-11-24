@extends('layouts.acesso')
@section('titulo', 'Entrar')
@section('titulo_sufixo', 'MinhaTropa')
@section('conteudo')
<h1>Entrar no MinhaTropa</h1>
<p class="suave">Acesse com seu CPF e senha. Você também pode usar o e-mail.</p>
<form method="post" action="{{ route('entrar.enviar') }}" class="formulario">
    @csrf
    <label class="campo"><span>CPF ou e-mail</span><input type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" inputmode="numeric" placeholder="000.000.000-00 ou seu@email.com"></label>
    <label class="campo"><span>Senha</span><input type="password" name="senha" required autocomplete="current-password"></label>
    <button class="botao botao--primario botao--largo">Entrar</button>
</form>
<p class="acesso__alternativa">Ainda não tem conta? <a href="{{ route('cadastro') }}">Cadastre seu Haras / Fazenda grátis</a> ou <a href="{{ route('cadastro.profissional') }}">cadastre-se como Profissional</a>.</p>
<script>
document.querySelector('[name=login]').addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '');
    if (v.length > 0 && !this.value.includes('@')) {
        this.inputMode = 'numeric';
        if (v.length <= 11) this.value = v.replace(/(\d{3})(\d{3})?(\d{3})?(\d{1,2})?/, function (_, a, b, c, d) {
            var r = a; if (b) r += '.' + b; if (c) r += '.' + c; if (d) r += '-' + d; return r;
        });
    } else {
        this.inputMode = 'email';
    }
});
</script>
@endsection
