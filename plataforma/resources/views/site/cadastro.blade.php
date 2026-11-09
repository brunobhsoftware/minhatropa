@extends('layouts.site')
@section('titulo', 'Cadastre seu Haras — MinhaTropa')
@section('conteudo')
<section class="cadastro">
    <div class="cadastro__interno">
        <div class="cadastro__texto">
            <span class="sobretitulo">Comece grátis</span>
            <h1>Cadastre seu Haras no MinhaTropa</h1>
            <p>Crie sua conta em poucos minutos, configure o Haras e cadastre o seu primeiro animal sem custo.</p>
            <ul class="lista-check">
                <li>Configuração do Haras grátis</li>
                <li>1º animal grátis, sem cartão de crédito</li>
                <li>A partir do 2º animal, contrate e libere todas as ferramentas</li>
            </ul>
            <p class="cadastro__ja">Já tem conta? <a href="{{ route('entrar') }}">Entrar</a></p>
        </div>
        <div class="contato__form">
            @if (! $disponivel)
                <div class="aviso aviso--erro">O cadastro on-line está sendo ativado. Enquanto isso, fale com a gente em <a href="mailto:atendimento@minhatropa.com.br">atendimento@minhatropa.com.br</a>.</div>
            @else
            @if ($errors->any())<div class="aviso aviso--erro" role="alert">@if ($errors->count() === 1){{ $errors->first() }}@else Confira os campos destacados: <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif</div>@endif
            <form method="post" action="{{ route('cadastro.enviar') }}" novalidate>
                @csrf
                <div class="oculto" aria-hidden="true"><label>Site <input name="site" tabindex="-1" autocomplete="off"></label></div>
                <h2 class="form-titulo">1. Seu Haras</h2>
                <label class="campo"><span>Nome do Haras *</span><input name="haras" value="{{ old('haras') }}" required maxlength="150" placeholder="Ex.: Haras Vista Alegre"></label>
                <div class="campos-2">
                    <label class="campo"><span>Cidade *</span><input name="cidade" value="{{ old('cidade') }}" required maxlength="100"></label>
                    <label class="campo"><span>UF *</span><select name="uf" required><option value=""></option>@foreach ($ufs as $uf)<option @selected(old('uf') === $uf)>{{ $uf }}</option>@endforeach</select></label>
                </div>
                <div class="campos-2">
                    <label class="campo"><span>Quantidade de animais *</span><select name="quantidade_animais" required><option value="">Selecione</option>@foreach (\App\Models\Empresa::QUANTIDADES as $q)<option @selected(old('quantidade_animais') === $q)>{{ $q }}</option>@endforeach</select></label>
                    <label class="campo"><span>CPF ou CNPJ (opcional)</span><input name="documento" value="{{ old('documento') }}" inputmode="numeric" maxlength="18"></label>
                </div>
                <h2 class="form-titulo">2. Seus dados de acesso</h2>
                <label class="campo"><span>Seu nome *</span><input name="nome" value="{{ old('nome') }}" required maxlength="150" autocomplete="name"></label>
                <div class="campos-2">
                    <label class="campo"><span>E-mail *</span><input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email"></label>
                    <label class="campo"><span>WhatsApp *</span><input type="tel" name="telefone" value="{{ old('telefone') }}" required maxlength="30" autocomplete="tel"></label>
                </div>
                <div class="campos-2">
                    <label class="campo"><span>Senha *</span><input type="password" name="senha" required minlength="8" autocomplete="new-password"><small>Mínimo 8 caracteres, com letras e números.</small></label>
                    <label class="campo"><span>Repita a senha *</span><input type="password" name="senha_confirmation" required minlength="8" autocomplete="new-password"></label>
                </div>
                <label class="aceite"><input type="checkbox" name="aceite" value="1" @checked(old('aceite'))> Entendo que o período gratuito inclui a configuração do Haras e o cadastro de 1 animal, e que para cadastrar mais animais preciso contratar o MinhaTropa.</label>
                <button class="botao botao--amarelo botao--grande botao--largo">Criar minha conta grátis <span aria-hidden="true">→</span></button>
            </form>
            @endif
        </div>
    </div>
</section>
@endsection
