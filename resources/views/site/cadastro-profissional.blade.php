@extends('layouts.site')
@section('titulo', 'Cadastro de Profissional — MinhaTropa')
@section('conteudo')
<section class="cadastro">
    <div class="cadastro__interno">
        <div class="cadastro__texto">
            <span class="sobretitulo">{{ $continuarPropriedade ? 'Passo 1 de 2' : 'Cadastro rápido' }}</span>
            <h1>{{ $continuarPropriedade ? 'Seu cadastro de Profissional' : 'Cadastre-se como Profissional' }}</h1>
            <p>No MinhaTropa toda pessoa física tem um cadastro único, pelo CPF. É por ele que você é identificado como proprietário, veterinário, gerente, treinador ou qualquer outra função, em uma ou em várias propriedades.</p>
            <ul class="lista-check">
                <li>Um cadastro só, usado em todo o sistema</li>
                <li>Você pode acumular mais de uma função</li>
                <li>Seu próprio ambiente: seus dados e as propriedades em que atua</li>
                @if ($continuarPropriedade)<li>Depois dele, o cadastro do Haras / Fazenda puxa seus dados pelo CPF</li>@else<li>Qualquer Haras / Fazenda encontra você pelo CPF para vincular à equipe</li>@endif
            </ul>
            <p class="cadastro__ja">Já tem conta? <a href="{{ route('entrar', ['ambiente' => 'profissional']) }}">Entrar como profissional</a></p>
        </div>
        <div class="contato__form">
            @if (! $disponivel)
                <div class="aviso aviso--erro">O cadastro on-line está sendo ativado. Enquanto isso, fale com a gente em <a href="mailto:atendimento@minhatropa.com.br">atendimento@minhatropa.com.br</a>.</div>
            @else
            @if ($errors->any())<div class="aviso aviso--erro" role="alert">@if ($errors->count() === 1){{ $errors->first() }}@else Confira os campos destacados: <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
                @if (session('entrar_profissional'))<p style="margin:10px 0 0"><a class="botao botao--amarelo" href="{{ route('entrar', ['ambiente' => 'profissional']) }}">Entrar como profissional</a></p>@endif</div>@endif
            @unless ($continuarPropriedade)@include('site.partes.escolha-cadastro', ['atual' => 'profissional'])@endunless
            <form method="post" action="{{ route('cadastro.profissional.enviar') }}" novalidate>
                @csrf
                <div class="oculto" aria-hidden="true"><label>Site <input name="site" tabindex="-1" autocomplete="off"></label></div>
                @if ($continuarPropriedade || old('continuar') === 'propriedade')<input type="hidden" name="continuar" value="propriedade">@endif
                <h2 class="form-titulo">Dados pessoais</h2>
                <div class="campos-2">
                    <label class="campo"><span>CPF *</span><input name="cpf" value="{{ old('cpf', $cpfInicial) }}" required inputmode="numeric" maxlength="14" data-mascara="cpf" autocomplete="off"></label>
                    <label class="campo"><span>Data de nascimento</span><input type="date" name="data_nascimento" value="{{ old('data_nascimento') }}"></label>
                </div>
                <label class="campo"><span>Nome completo *</span><input name="nome" value="{{ old('nome') }}" required maxlength="150" autocomplete="name"></label>
                <div class="campos-2">
                    <label class="campo"><span>E-mail *</span><input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email"></label>
                    <label class="campo"><span>Celular / WhatsApp *</span><input type="tel" name="telefone" value="{{ old('telefone') }}" required maxlength="30" autocomplete="tel"></label>
                </div>
                <div class="campos-2">
                    <x-uf-municipio obrigatorio />
                </div>
                <h2 class="form-titulo">Suas funções</h2>
                @php $travada = $continuarPropriedade || old('continuar') === 'propriedade'; @endphp
                <p class="suave-form">Marque todas as que se aplicam.@if ($travada) "Proprietário de Haras/Fazenda" já vem marcada porque você vai cadastrar uma propriedade.@endif</p>
                <div class="funcoes">
                    @foreach (\App\Models\Profissional::ESPECIALIDADES as $valor => $texto)
                    <label class="funcoes__opcao"><input type="checkbox" name="especialidades[]" value="{{ $valor }}" @checked(in_array($valor, (array) old('especialidades', $travada ? ['proprietario'] : []))) @if ($travada && $valor === 'proprietario') onclick="return false" @endif> {{ $texto }}</label>
                    @endforeach
                </div>
                <label class="campo"><span>CRMV (se for veterinário)</span><input name="crmv" value="{{ old('crmv') }}" maxlength="30"></label>
                <h2 class="form-titulo">Sua senha de acesso</h2>
                <div class="campos-2">
                    <label class="campo"><span>Senha *</span><input type="password" name="senha" required minlength="8" autocomplete="new-password"><small>Mínimo 8 caracteres, com letras e números.</small></label>
                    <label class="campo"><span>Repita a senha *</span><input type="password" name="senha_confirmation" required minlength="8" autocomplete="new-password"></label>
                </div>
                <button class="botao botao--amarelo botao--grande botao--largo">{{ $continuarPropriedade ? 'Concluir e continuar' : 'Criar meu cadastro' }} <span aria-hidden="true">→</span></button>
                <small class="contato__lgpd">Seus dados são usados para identificar você no MinhaTropa e nas propriedades às quais for vinculado. Se o seu CPF já foi cadastrado por um Haras / Fazenda, use o mesmo e-mail para ativar o acesso.</small>
            </form>
            @endif
        </div>
    </div>
</section>
@endsection
