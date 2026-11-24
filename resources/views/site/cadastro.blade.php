@extends('layouts.site')
@section('titulo', 'Cadastre sua propriedade — MinhaTropa')
@section('conteudo')
@php
    $limiteGratis = $gratis?->limite_max;
    $tit = old('titularidade', $cpfInicial ? 'cpf' : '');
    $tipo = old('tipo_propriedade', 'haras');
@endphp
<section class="cadastro">
    <div class="cadastro__interno">
        <div class="cadastro__texto">
            <span class="sobretitulo">Comece grátis</span>
            <h1>Cadastre sua propriedade no MinhaTropa</h1>
            <p>Crie sua conta em poucos minutos, configure a propriedade e comece a cadastrar o seu plantel sem custo.</p>
            <ul class="lista-check">
                <li>Configuração da propriedade grátis</li>
                @if ($limiteGratis)<li>Até {{ $limiteGratis }} animais grátis, sem cartão de crédito</li>
                <li>Plantel maior? Mude de faixa quando precisar</li>@endif
                <li>Módulos adicionais para contratar quando quiser</li>
            </ul>
            <p class="cadastro__ja">Já tem conta? <a href="{{ route('entrar') }}">Entrar</a></p>
        </div>
        <div class="contato__form">
            @if (! $disponivel)
                <div class="aviso aviso--erro">O cadastro on-line está sendo ativado. Enquanto isso, fale com a gente em <a href="mailto:atendimento@minhatropa.com.br">atendimento@minhatropa.com.br</a>.</div>
            @else
            @if (session('ok'))<div class="aviso aviso--ok" role="status">{{ session('ok') }}</div>@endif
            @if ($errors->any())<div class="aviso aviso--erro" role="alert">@if ($errors->count() === 1){{ $errors->first() }}@else Confira os campos destacados: <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
</div>@endif
            @include('site.partes.escolha-cadastro', ['atual' => 'propriedade'])
            <form method="post" action="{{ route('cadastro.enviar') }}" novalidate id="formCadastro" data-consulta="{{ route('cadastro.consultar-cpf') }}">
                @csrf
                <div class="oculto" aria-hidden="true"><label>Site <input name="site" tabindex="-1" autocomplete="off"></label></div>

                <h2 class="form-titulo">1. Sua propriedade</h2>
                <input type="hidden" name="tipo_propriedade" value="fazenda">

                <h2 class="form-titulo">2. Titularidade</h2>
                <fieldset class="escolha"><legend>A propriedade está em nome de *</legend>
                    @foreach (\App\Models\Empresa::TITULARIDADES as $valor => $texto)
                    <label class="escolha__opcao"><input type="radio" name="titularidade" value="{{ $valor }}" @checked($tit === $valor)> <span>{{ $texto }}</span></label>
                    @endforeach
                </fieldset>

                <div id="blocoDocumento" @if (! $tit) hidden @endif>
                    <div class="campo-acao">
                        <label class="campo"><span id="rotuloDocumento">{{ $tit === 'cnpj' ? 'CNPJ *' : 'CPF do proprietário *' }}</span>
                            <input name="documento" id="documento" value="{{ old('documento', $cpfInicial) }}" inputmode="numeric" maxlength="18" autocomplete="off" required></label>
                        <button type="button" class="botao botao--contorno-escuro" id="btnConsultar" @if ($tit !== 'cpf') hidden @endif>Verificar CPF</button>
                    </div>
                    <div id="resultadoCpf" class="consulta-cpf" role="status" aria-live="polite" hidden></div>
                </div>

                <div id="blocoRestante" @if (! $tit) hidden @endif>
                    <div data-so="cnpj" @if ($tit !== 'cnpj') hidden @endif>
                        <label class="campo"><span>Razão social *</span><input name="razao_social" value="{{ old('razao_social') }}" maxlength="190"></label>
                    </div>
                    <label class="campo"><span>Nome da propriedade *</span><input name="haras" value="{{ old('haras') }}" required maxlength="150" placeholder="Ex.: Fazenda Boa Vista"><small data-so="cnpj" @if ($tit !== 'cnpj') hidden @endif>Nome fantasia: é como a propriedade aparece no sistema.</small></label>
                    <label class="campo"><span>Sufixo / nome do criatório <small class="suave">(opcional)</small></span><input name="sufixo" value="{{ old('sufixo') }}" maxlength="120" placeholder="Ex.: DA AGROTEXAS"><small>O "sobrenome" dos animais na associação. Pode informar depois.</small></label>
                    <div class="campos-2">
                        <x-uf-municipio obrigatorio />
                    </div>
                    <label class="campo"><span>Quantidade de animais *</span><select name="quantidade_animais" required><option value="">Selecione</option>@foreach (\App\Models\Empresa::QUANTIDADES as $q)<option @selected(old('quantidade_animais') === $q)>{{ $q }}</option>@endforeach</select></label>

                    <h2 class="form-titulo">3. Seus dados de acesso</h2>
                    {{-- O nome é pedido para Pessoa Jurídica e para CPF que ainda não está na base; CPF já cadastrado puxa o nome. --}}
                    <div id="blocoNome" @if ($tit !== 'cnpj' && ! old('nome')) hidden @endif>
                        <label class="campo"><span id="rotuloPessoa">{{ $tit === 'cnpj' ? 'Seu nome (responsável) *' : 'Seu nome completo *' }}</span><input name="nome" value="{{ old('nome') }}" maxlength="150" autocomplete="name"></label>
                    </div>
                    <p class="suave-form" id="avisoPuxado" hidden>Seu nome e seus dados vêm do seu cadastro no MinhaTropa. Confirme o e-mail e crie a senha de acesso.</p>
                    <div class="campos-2">
                        <label class="campo"><span>E-mail *</span><input type="email" name="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email"></label>
                        <label class="campo"><span>WhatsApp *</span><input type="tel" name="telefone" value="{{ old('telefone') }}" required maxlength="30" autocomplete="tel"></label>
                    </div>
                    <div class="campos-2">
                        <label class="campo"><span>Senha *</span><input type="password" name="senha" required minlength="8" autocomplete="new-password"><small>Mínimo 8 caracteres, com letras e números.</small></label>
                        <label class="campo"><span>Repita a senha *</span><input type="password" name="senha_confirmation" required minlength="8" autocomplete="new-password"></label>
                    </div>
                    <label class="aceite"><input type="checkbox" name="aceite" value="1" @checked(old('aceite'))> Entendo que o plano gratuito inclui a configuração da propriedade e o cadastro de {{ $limiteGratis ? 'até '.$limiteGratis.' animais ativos' : 'animais dentro do limite gratuito' }}, e que para um plantel maior preciso mudar de faixa.</label>
                    <button class="botao botao--amarelo botao--grande botao--largo" id="btnCriar">Criar minha conta grátis <span aria-hidden="true">→</span></button>
                </div>
            </form>
            @endif
        </div>
    </div>
</section>
@if ($disponivel)
<script>
// Fluxo do cadastro: titularidade (PF/PJ) → documento → consulta do CPF na base de Profissionais.
document.addEventListener('DOMContentLoaded', function () {
  var f = document.getElementById('formCadastro'); if (!f) return;
  var doc = document.getElementById('documento'), botao = document.getElementById('btnConsultar'), caixa = document.getElementById('resultadoCpf');
  var digitos = function (v) { return v.replace(/\D/g, ''); };
  var titular = function () { var m = f.querySelector('[name=titularidade]:checked'); return m ? m.value : ''; };
  var mascarar = function () {
    var v = digitos(doc.value);
    if (titular() === 'cnpj') {
      v = v.slice(0, 14).replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
    } else {
      v = v.slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    doc.value = v;
  };
  var mostrar = function (tipo, html) { caixa.className = 'consulta-cpf consulta-cpf--' + tipo; caixa.innerHTML = html; caixa.hidden = false; };
  var texto = function (t) { var d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; };
  var liberar = function (sim) { document.getElementById('blocoRestante').hidden = !sim; };
  // nome: 'pedir' mostra o campo (Pessoa Jurídica ou CPF novo); 'puxado' esconde, porque vem do cadastro existente
  var pessoa = function (modo) {
    document.getElementById('blocoNome').hidden = modo !== 'pedir';
    document.getElementById('avisoPuxado').hidden = modo !== 'puxado';
    document.getElementById('rotuloPessoa').textContent = titular() === 'cnpj' ? 'Seu nome (responsável) *' : 'Seu nome completo *';
  };
  var consultado = '';

  function ajustar() {
    var t = titular();
    document.getElementById('blocoDocumento').hidden = !t;
    document.getElementById('rotuloDocumento').textContent = t === 'cnpj' ? 'CNPJ *' : 'CPF do proprietário *';
    botao.hidden = t !== 'cpf';
    f.querySelectorAll('[data-so]').forEach(function (e) { e.hidden = e.getAttribute('data-so') !== t; });
    caixa.hidden = true; consultado = '';
    mascarar();
    // Pessoa Jurídica segue direto; Pessoa Física só depois de o CPF ser encontrado na base de Profissionais.
    liberar(t === 'cnpj'); pessoa(t === 'cnpj' ? 'pedir' : '');
    if (t === 'cpf' && digitos(doc.value).length === 11) consultar();
  }
  function consultar() {
    var cpf = digitos(doc.value);
    if (cpf.length !== 11) { mostrar('erro', 'Informe os 11 números do CPF.'); liberar(false); return; }
    if (cpf === consultado) return;
    mostrar('espera', 'Consultando o CPF na base de Profissionais…');
    fetch(f.getAttribute('data-consulta'), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': f.querySelector('[name=_token]').value }, body: JSON.stringify({ cpf: cpf }) })
      .then(function (r) { if (r.status === 429) throw new Error('muitas'); return r.json(); })
      .then(function (d) {
        if (digitos(doc.value) !== cpf || titular() !== 'cpf') return;
        consultado = cpf;
        if (d.situacao === 'encontrado') {
          mostrar('ok', '<b>✓ ' + texto(d.nome) + '</b><br>' + texto(d.mensagem) +
            '<dl><div><dt>Funções</dt><dd>' + texto(d.funcoes) + '</dd></div><div><dt>Cidade</dt><dd>' + texto(d.cidade_uf) + '</dd></div>' +
            (d.email ? '<div><dt>E-mail</dt><dd>' + texto(d.email) + '</dd></div>' : '') + '</dl>');
          var e = f.querySelector('[name=email]'), tel = f.querySelector('[name=telefone]');
          if (d.email_completo && !e.value) e.value = d.email_completo;
          if (d.telefone && !tel.value) tel.value = d.telefone;
          pessoa('puxado'); liberar(true);
        } else if (d.situacao === 'tem_conta') {
          mostrar('bloqueio', '<b>Esse CPF já tem conta</b><br>' + texto(d.mensagem) + '<br><a class="botao botao--amarelo" href="' + texto(d.url) + '">Entrar como profissional</a>');
          liberar(false);
        } else if (d.situacao === 'novo') {
          // CPF ainda não cadastrado: segue direto. O formulário pede o nome e o envio cria a pessoa e a propriedade, já vinculadas.
          caixa.hidden = true; pessoa('pedir'); liberar(true);
        } else {
          mostrar('erro', texto(d.mensagem)); liberar(false);
        }
      })
      .catch(function (e) { consultado = ''; mostrar('erro', e.message === 'muitas' ? 'Muitas consultas seguidas. Aguarde um minuto e tente de novo.' : 'Não foi possível consultar agora. Tente de novo.'); });
  }

  f.querySelectorAll('[name=titularidade]').forEach(function (r) { r.addEventListener('change', function () { doc.value = ''; ajustar(); doc.focus(); }); });
  // Tipo de propriedade fixo como fazenda — sem troca dinâmica
  doc.addEventListener('input', function () { mascarar(); if (titular() === 'cpf') { if (digitos(doc.value) !== consultado) { caixa.hidden = true; liberar(false); consultado = ''; } if (digitos(doc.value).length === 11) consultar(); } });
  botao.addEventListener('click', function () { consultado = ''; consultar(); });
  ajustar();
});
</script>
@endif
@endsection
