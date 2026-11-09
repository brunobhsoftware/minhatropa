@extends('layouts.admin')
@section('titulo', 'Administradores')
@section('conteudo')
<div class="cabecalho"><div><h1>Administradores</h1><p>Quem tem acesso a esta Administração (equipe da plataforma).</p></div></div>

<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Último acesso</th><th>Situação</th><th class="num"></th></tr></thead>
        <tbody>
        @foreach ($administradores as $u)
            <tr>
                <td><b>{{ $u->nome }}</b>@if ($u->is(auth()->user())) <small class="suave">(você)</small>@endif</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->ultimo_login_em ? data_local($u->ultimo_login_em) : 'Nunca' }}</td>
                <td><span class="selo selo--{{ $u->ativo ? 'ativo' : 'encerrado' }}">{{ $u->ativo ? 'Ativo' : 'Desativado' }}</span></td>
                <td class="num"><button type="button" class="botao botao--contorno botao--pequeno" data-alternar="#editar-{{ $u->id }}">Editar</button></td>
            </tr>
            <tr id="editar-{{ $u->id }}" hidden class="linha-edicao"><td colspan="5">
                <div class="grade-2">
                <form method="post" action="{{ route('admin.usuarios.salvar', $u) }}" class="formulario">
                    @csrf @method('put')
                    <label class="campo"><span>Nome</span><input name="nome" value="{{ $u->nome }}" required></label>
                    <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ $u->email }}" required></label>
                    <label class="campo"><span>Telefone</span><input name="telefone" value="{{ $u->telefone }}"></label>
                    <label class="opcao"><input type="checkbox" name="ativo" value="1" @checked($u->ativo)> Acesso ativo</label>
                    <button class="botao botao--primario botao--pequeno">Salvar</button>
                </form>
                <form method="post" action="{{ route('admin.usuarios.senha', $u) }}" class="formulario" data-confirmar="Definir uma nova senha para {{ $u->nome }}?">
                    @csrf @method('put')
                    <label class="campo"><span>Nova senha provisória</span><input type="text" name="senha" minlength="10" required autocomplete="off"><small>Mínimo de 10 caracteres, com letras e números.</small></label>
                    <button class="botao botao--contorno botao--pequeno">Definir senha</button>
                </form>
                </div>
            </td></tr>
        @endforeach
        </tbody>
    </table></div>
</section>

<section class="cartao">
    <h2>Novo administrador</h2>
    <form method="post" action="{{ route('admin.usuarios.criar') }}" class="formulario grade-3">
        @csrf
        <label class="campo"><span>Nome</span><input name="nome" value="{{ old('nome') }}" required></label>
        <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" required></label>
        <label class="campo"><span>Senha provisória</span><input type="text" name="senha" minlength="10" required autocomplete="off"></label>
        <div><button class="botao botao--primario"><x-icone nome="mais" :tamanho="16" /> Adicionar</button></div>
    </form>
</section>
@endsection
