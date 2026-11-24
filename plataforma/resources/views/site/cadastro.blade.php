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
                <fieldset class="escolha"><legend>Tipo de propriedade *</legend>
                    <label class="escolha__opcao"><input type="radio" name="tipo_propriedade" value="haras" @checked($tipo === 'haras')> <span>Haras</span></label>
                    <label class="escolha__opcao"><input type="radio" name="tipo_propriedade" value="fazenda" @checked($tipo === 'fazenda')> <span>Fazenda</span></label>
                </fieldset>

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
                    <label class="campo"><span id="rotuloNome">Nome {{ $tipo === 'fazenda' ? 'da Fazenda' : 'do Haras' }} *</span><input name="haras" value="{{ old('haras') }}" required maxlength="150" placeholder="Ex.: Fazenda Boa Vista"><small data-so="cnpj" @if ($tit !== 'cnpj') hidden @endif>Nome fantasia: é como a propriedade aparece no sistema.</small></label>
                    {{-- Sufixo: verificação por confronto de dados --}}
                    <div id="blocoSufixo">
                        <h2 class="form-titulo" style="margin-top:1.5rem">Sufixo do Haras <small class="suave">(opcional)</small></h2>
                        <p class="suave-form">O sufixo é o nome registrado na associação (ex.: MINHA TROPA). Para vincular, precisamos confirmar alguns dados.</p>
                        <div class="campo-acao">
                            <label class="campo" style="flex:1"><span>Informe o sufixo do Haras</span><input id="sufixoBusca" value="{{ old('sufixo') }}" maxlength="120" placeholder="Ex.: MINHA TROPA" autocomplete="off"></label>
                            <button type="button" class="botao botao--contorno-escuro" id="btnVerificarSufixo">Verificar sufixo</button>
                        </div>
                        <div id="sufixoResultado" role="status" aria-live="polite" hidden></div>
                        {{-- Campos de confronto (aparecem quando o sufixo é encontrado na base) --}}
                        <div id="sufixoConfronto" hidden style="margin-top:1rem">
                            <p class="suave-form">Para confirmar que o sufixo é seu, informe os dados do proprietário registrado na associação:</p>
                            <div class="campos-2">
                                <label class="campo"><span>Celular do proprietário *</span><input id="sufixoCelular" type="tel" maxlength="30" placeholder="(00) 00000-0000" autocomplete="off"></label>
                                <label class="campo"><span>Nome completo do proprietário *</span><input id="sufixoNome" maxlength="200" placeholder="Conforme registrado na associação" autocomplete="off"></label>
                            </div>
                            <button type="button" class="botao botao--contorno-escuro" id="btnConfirmarSufixo">Confirmar dados</button>
                            <div id="sufixoConfirmacao" role="status" aria-live="polite" hidden style="margin-top:.5rem"></div>
                        </div>
                        {{-- Campos hidden que vão no POST --}}
                        <input type="hidden" name="sufixo" id="campoSufixo" value="{{ old('sufixo') }}">
                        <input type="hidden" name="sufixo_codigo" id="campoSufixoCodigo" value="">
                        <input type="hidden" name="sufixo_associacao_id" id="campoSufixoAssocId" value="">
                    </div>

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
  f.querySelectorAll('[name=tipo_propriedade]').forEach(function (r) { r.addEventListener('change', function () { var n = document.getElementById('rotuloNome'); if (n) n.textContent = 'Nome ' + (r.value === 'fazenda' ? 'da Fazenda' : 'do Haras') + ' *'; }); });
  doc.addEventListener('input', function () { mascarar(); if (titular() === 'cpf') { if (digitos(doc.value) !== consultado) { caixa.hidden = true; liberar(false); consultado = ''; } if (digitos(doc.value).length === 11) consultar(); } });
  botao.addEventListener('click', function () { consultado = ''; consultar(); });
  ajustar();
});

// Verificação do sufixo por confronto de dados
(function () {
  var busca = document.getElementById('sufixoBusca'), btn = document.getElementById('btnVerificarSufixo');
  var resultado = document.getElementById('sufixoResultado'), confronto = document.getElementById('sufixoConfronto');
  var celular = document.getElementById('sufixoCelular'), nome = document.getElementById('sufixoNome');
  var btnConfirmar = document.getElementById('btnConfirmarSufixo'), confirmacao = document.getElementById('sufixoConfirmacao');
  var campoSufixo = document.getElementById('campoSufixo'), campoCodigo = document.getElementById('campoSufixoCodigo'), campoAssocId = document.getElementById('campoSufixoAssocId');
  if (!busca || !btn) return;
  var token = function () { var t = document.querySelector('[name=_token]'); return t ? t.value : ''; };
  var txt = function (t) { var d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; };
  var enviar = function (dados) {
    return fetch('{{ route("cadastro.verificar-sufixo") }}', {
      method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token() },
      body: JSON.stringify(dados)
    }).then(function (r) { if (r.status === 429) throw new Error('muitas'); return r.json(); });
  };
  var limpar = function () {
    campoSufixo.value = ''; campoCodigo.value = ''; campoAssocId.value = '';
    confronto.hidden = true; confirmacao.hidden = true; celular.value = ''; nome.value = '';
  };

  btn.addEventListener('click', function () {
    var q = busca.value.trim();
    if (q.length < 2) { resultado.innerHTML = '<p class="suave">Informe o sufixo do Haras.</p>'; resultado.hidden = false; return; }
    limpar();
    resultado.innerHTML = '<p class="suave">Verificando…</p>'; resultado.hidden = false;
    enviar({ sufixo: q })
      .then(function (d) {
        if (d.situacao === 'encontrado') {
          resultado.innerHTML = '<p class="consulta-cpf consulta-cpf--ok"><b>✓ Sufixo encontrado na base da associação.</b><br>' + txt(d.mensagem) + '</p>';
          resultado.hidden = false;
          confronto.hidden = false;
        } else if (d.situacao === 'nao_encontrado') {
          resultado.innerHTML = '<p class="consulta-cpf consulta-cpf--espera">' + txt(d.mensagem) + ' Você pode informar o sufixo mesmo assim — quando a base for atualizada ele será vinculado.</p>';
          resultado.hidden = false;
          campoSufixo.value = q.toUpperCase();
        } else if (d.situacao === 'em_uso') {
          resultado.innerHTML = '<p class="consulta-cpf consulta-cpf--erro"><b>' + txt(d.mensagem) + '</b></p>';
          resultado.hidden = false;
        } else {
          resultado.innerHTML = '<p class="suave">' + txt(d.mensagem) + '</p>'; resultado.hidden = false;
        }
      })
      .catch(function (e) { resultado.innerHTML = '<p class="consulta-cpf consulta-cpf--erro">' + (e.message === 'muitas' ? 'Muitas tentativas. Aguarde um minuto.' : 'Não foi possível verificar agora.') + '</p>'; resultado.hidden = false; });
  });

  btnConfirmar.addEventListener('click', function () {
    var q = busca.value.trim(), c = celular.value.trim(), n = nome.value.trim();
    if (!c || !n) { confirmacao.innerHTML = '<p class="consulta-cpf consulta-cpf--erro">Preencha o celular e o nome do proprietário.</p>'; confirmacao.hidden = false; return; }
    confirmacao.innerHTML = '<p class="suave">Conferindo…</p>'; confirmacao.hidden = false;
    enviar({ sufixo: q, celular: c, nome: n })
      .then(function (d) {
        if (d.situacao === 'confirmado') {
          campoSufixo.value = d.sufixo; campoCodigo.value = d.codigo; campoAssocId.value = d.id;
          busca.value = d.sufixo;
          confronto.hidden = true;
          resultado.innerHTML = '<p class="consulta-cpf consulta-cpf--ok"><b>✓ ' + txt(d.sufixo) + '</b> (código ' + txt(d.codigo) + ') — dados conferem! Sufixo vinculado.</p>';
          confirmacao.hidden = true;
        } else {
          confirmacao.innerHTML = '<p class="consulta-cpf consulta-cpf--erro">' + txt(d.mensagem) + '</p>'; confirmacao.hidden = false;
        }
      })
      .catch(function () { confirmacao.innerHTML = '<p class="consulta-cpf consulta-cpf--erro">Não foi possível verificar agora.</p>'; confirmacao.hidden = false; });
  });

  busca.addEventListener('input', function () { limpar(); resultado.hidden = true; });
})();
</script>
@endif
@endsection
