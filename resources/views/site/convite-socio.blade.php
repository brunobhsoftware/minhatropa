@extends('layouts.site')
@section('titulo', 'Convite de sociedade — MinhaTropa')
@section('conteudo')
@php $pct = \App\Support\Sociedade::pct($convite->percentual); @endphp
<section class="cadastro">
    <div class="cadastro__interno">
        <div class="cadastro__texto">
            <span class="sobretitulo">Convite de sociedade</span>
            <h1>Você é sócio(a) de {{ $convite->animal }}</h1>
            <p><b>{{ $convite->propriedade }}</b> lançou você no MinhaTropa como sócio(a) do animal <b>{{ $convite->animal }}</b>, com <b>{{ $pct }}</b>.</p>
            <ul class="lista-check">
                <li>Cadastro simples, em um minuto</li>
                <li>Você acompanha os animais em que tem parte</li>
                <li>Sem custo</li>
            </ul>
        </div>
        <div class="contato__form">
            @if (session('ok'))<div class="aviso aviso--ok" role="status">{{ session('ok') }}</div>@endif
            @if ($errors->any())<div class="aviso aviso--erro" role="alert">@if ($errors->count() === 1){{ $errors->first() }}@else Confira os campos destacados: <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif</div>@endif

            @if ($jaCadastrado)
                <h2 class="form-titulo">{{ $nome }} já tem cadastro</h2>
                <p class="suave-form">{{ $cpf ? 'Entre no painel profissional para ver o animal em sociedade.' : 'Entre no painel do Haras / Fazenda: o animal aparece em Meu Plantel, na lista de animais em sociedade.' }}</p>
                <a class="botao botao--amarelo botao--grande botao--largo" href="{{ route('entrar', ['ambiente' => $cpf ? 'profissional' : 'haras']) }}">Entrar <span aria-hidden="true">→</span></a>
            @elseif (! $cpf)
                <h2 class="form-titulo">Cadastre o seu Haras / Fazenda</h2>
                <p class="suave-form">A sociedade foi lançada no CNPJ <b>{{ \App\Support\Documento::formatar($convite->documento) }}</b> ({{ $nome }}). Cadastre a propriedade com esse CNPJ e o animal aparece em Meu Plantel, na lista de animais em sociedade.</p>
                <a class="botao botao--amarelo botao--grande botao--largo" href="{{ route('cadastro') }}">Cadastrar meu Haras / Fazenda <span aria-hidden="true">→</span></a>
            @else
                <form method="post" action="{{ route('convite.socio.aceitar', request()->route('token')) }}" novalidate>
                    @csrf
                    <div class="oculto" aria-hidden="true"><label>Site <input name="site" tabindex="-1" autocomplete="off"></label></div>
                    <h2 class="form-titulo">Seu cadastro</h2>
                    <div class="campos-2">
                        <label class="campo"><span>CPF</span><input value="{{ \App\Support\Documento::formatar($convite->documento) }}" disabled></label>
                        <label class="campo"><span>Nome completo *</span><input name="nome" value="{{ old('nome', $nome) }}" required maxlength="150" autocomplete="name"></label>
                    </div>
                    <div class="campos-2">
                        <label class="campo"><span>E-mail *</span><input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email">@if ($emailMascarado)<small>Use o e-mail já informado no seu cadastro: {{ $emailMascarado }}</small>@endif</label>
                        <label class="campo"><span>WhatsApp *</span><input type="tel" name="telefone" value="{{ old('telefone') }}" required maxlength="30" autocomplete="tel"></label>
                    </div>
                    <div class="campos-2">
                        <label class="campo"><span>Senha *</span><input type="password" name="senha" required minlength="8" autocomplete="new-password"><small>Mínimo 8 caracteres, com letras e números.</small></label>
                        <label class="campo"><span>Repita a senha *</span><input type="password" name="senha_confirmation" required minlength="8" autocomplete="new-password"></label>
                    </div>
                    <button class="botao botao--amarelo botao--grande botao--largo">Concluir meu cadastro <span aria-hidden="true">→</span></button>
                    <small class="contato__lgpd">Seus dados identificam você no MinhaTropa e nas propriedades em que tiver parte em animais.</small>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
