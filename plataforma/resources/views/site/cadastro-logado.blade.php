@extends('layouts.site')
@section('titulo', 'Cadastrar Haras / Fazenda — MinhaTropa')
@section('conteudo')
@php
    $limiteGratis = $gratis?->limite_max;
    $tit = old('titularidade', $cpfLivre ? 'cpf' : 'cnpj');
    $tipo = old('tipo_propriedade', 'haras');
@endphp
<section class="cadastro">
    <div class="cadastro__interno">
        <div class="cadastro__texto">
            <span class="sobretitulo">{{ $profissional ? 'Passo 2 de 2' : 'Nova propriedade' }}</span>
            <h1>Cadastre o seu Haras / Fazenda</h1>
            <p>Você está na conta de <b>{{ $usuario->nome }}</b> ({{ $usuario->email }}). A propriedade entra nesta mesma conta: não precisa de novo e-mail nem de nova senha.</p>
            <ul class="lista-check">
                <li>Configuração do Haras / Fazenda grátis</li>
                @if ($limiteGratis)<li>Até {{ $limiteGratis }} animais grátis, sem cartão de crédito</li>@endif
                <li>Módulos adicionais para contratar quando quiser</li>
            </ul>
            <p class="cadastro__ja"><a href="{{ route('profissional.inicio') }}">← Voltar ao ambiente do profissional</a></p>
        </div>
        <div class="contato__form">
            @if (! $disponivel)
                <div class="aviso aviso--erro">O cadastro on-line está sendo ativado.</div>
            @else
            @if (session('ok'))<div class="aviso aviso--ok" role="status">{{ session('ok') }}</div>@endif
            @if ($errors->any())<div class="aviso aviso--erro" role="alert">@if ($errors->count() === 1){{ $errors->first() }}@else Confira os campos destacados: <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif</div>@endif
            <form method="post" action="{{ route('cadastro.enviar') }}" novalidate id="formCadastroLogado">
                @csrf
                <div class="oculto" aria-hidden="true"><label>Site <input name="site" tabindex="-1" autocomplete="off"></label></div>
                <h2 class="form-titulo">1. Sua propriedade</h2>
                <fieldset class="escolha"><legend>Tipo de propriedade *</legend>
                    @foreach (\App\Models\Empresa::TIPOS_CADASTRO as $valor => $texto)
                    <label class="escolha__opcao"><input type="radio" name="tipo_propriedade" value="{{ $valor }}" @checked($tipo === $valor)> <span>{{ $texto }}</span></label>
                    @endforeach
                </fieldset>
                <h2 class="form-titulo">2. Titularidade</h2>
                <fieldset class="escolha"><legend>A propriedade está em nome de *</legend>
                    <label class="escolha__opcao {{ $cpfLivre ? '' : 'escolha__opcao--desativada' }}"><input type="radio" name="titularidade" value="cpf" @checked($tit === 'cpf') @disabled(! $cpfLivre)> <span>Pessoa Física (CPF)</span></label>
                    <label class="escolha__opcao"><input type="radio" name="titularidade" value="cnpj" @checked($tit === 'cnpj')> <span>Pessoa Jurídica (CNPJ)</span></label>
                </fieldset>
                @if ($profissional && $cpfLivre)
                    <div class="consulta-cpf consulta-cpf--ok" data-so="cpf" @if ($tit !== 'cpf') hidden @endif><b>✓ {{ $profissional->nome }}</b> · CPF {{ $profissional->cpfFormatado() }}<br>Seus dados vêm do seu cadastro de Profissional e você fica como proprietário titular.</div>
                @elseif ($profissional)
                    <p class="suave-form">O seu CPF já identifica um Haras / Fazenda. Cada CPF vale para uma única propriedade; outra propriedade entra pelo CNPJ.</p>
                @else
                    <p class="suave-form">Para cadastrar como Pessoa Física, <a href="{{ route('profissional.dados') }}">conclua o seu cadastro de Profissional</a>.</p>
                @endif
                <div data-so="cnpj" @if ($tit !== 'cnpj') hidden @endif style="display:grid;gap:14px">
                    <label class="campo"><span>CNPJ *</span><input name="documento" id="documento" value="{{ old('documento') }}" inputmode="numeric" maxlength="18" autocomplete="off"></label>
                    <label class="campo"><span>Razão social *</span><input name="razao_social" value="{{ old('razao_social') }}" maxlength="190"></label>
                </div>
                <label class="campo"><span id="rotuloNome">Nome {{ $tipo === 'fazenda' ? 'da Fazenda' : 'do Haras' }} *</span><input name="haras" value="{{ old('haras') }}" required maxlength="150"></label>
                <div class="campos-2"><x-uf-municipio obrigatorio /></div>
                <label class="campo"><span>Quantidade de animais *</span><select name="quantidade_animais" required><option value="">Selecione</option>@foreach (\App\Models\Empresa::QUANTIDADES as $q)<option @selected(old('quantidade_animais') === $q)>{{ $q }}</option>@endforeach</select></label>
                <label class="aceite"><input type="checkbox" name="aceite" value="1" @checked(old('aceite'))> Entendo que o plano gratuito inclui a configuração da propriedade e o cadastro de {{ $limiteGratis ? 'até '.$limiteGratis.' animais ativos' : 'animais dentro do limite gratuito' }}, e que para um plantel maior preciso mudar de faixa.</label>
                <button class="botao botao--amarelo botao--grande botao--largo">Cadastrar meu Haras / Fazenda <span aria-hidden="true">→</span></button>
            </form>
            @endif
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var f = document.getElementById('formCadastroLogado'); if (!f) return;
  var doc = document.getElementById('documento');
  var ajustar = function () { var m = f.querySelector('[name=titularidade]:checked'), t = m ? m.value : ''; f.querySelectorAll('[data-so]').forEach(function (e) { e.hidden = e.getAttribute('data-so') !== t; }); };
  f.querySelectorAll('[name=titularidade]').forEach(function (r) { r.addEventListener('change', ajustar); });
  f.querySelectorAll('[name=tipo_propriedade]').forEach(function (r) { r.addEventListener('change', function () { document.getElementById('rotuloNome').textContent = 'Nome ' + (r.value === 'fazenda' ? 'da Fazenda' : 'do Haras') + ' *'; }); });
  doc.addEventListener('input', function () { doc.value = doc.value.replace(/\D/g, '').slice(0, 14).replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2'); });
  ajustar();
});
</script>
@endsection
