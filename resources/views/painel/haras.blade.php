@extends('layouts.painel')
@section('titulo', 'Dados '.$haras->doRotulo())
@section('conteudo')
@php
    $v = fn ($c) => old($c, $haras->{$c});
    $pf = $haras->pessoaFisica();
    $cep = fn ($c) => old($c, $haras->{$c} ? substr($haras->{$c}, 0, 5).'-'.substr($haras->{$c}, 5) : '');
    $temPagina = \App\Support\Esquema::temV008();
@endphp
<div class="cabecalho">
    <div><h1>Dados {{ $haras->doRotulo() }}</h1><p>Cadastro completo da propriedade. Só o nome, o tipo e a UF/município são obrigatórios: o resto você preenche quando quiser.</p></div>
    @if ($temPagina && $haras->paginaUrl())<a href="{{ $haras->paginaUrl() }}" target="_blank" rel="noopener" class="botao botao--contorno">Ver a página ↗</a>@endif
</div>

{{-- Sem validação do navegador: os campos ficam em abas e o servidor abre a aba onde houver erro. --}}
<form method="post" action="{{ route('painel.haras.salvar') }}" class="formulario" enctype="multipart/form-data" novalidate data-abas data-erros="{{ implode(',', $errors->keys()) }}">
    @csrf @method('put')
    <nav class="abas-cadastro" aria-label="Seções do cadastro">
        <button type="button" class="aba-cadastro aba-cadastro--ativa" data-aba="identificacao">Identificação</button>
        <button type="button" class="aba-cadastro" data-aba="contato">Contato</button>
        <button type="button" class="aba-cadastro" data-aba="endereco">Endereço</button>
        <button type="button" class="aba-cadastro" data-aba="correspondencia">Correspondência</button>
        <button type="button" class="aba-cadastro" data-aba="criacao">Criação</button>
        @if ($temPagina)<button type="button" class="aba-cadastro" data-aba="pagina">Página e marca</button>@endif
    </nav>

    <div class="painel-aba" id="aba-identificacao">
        <section class="cartao"><h2>Identificação</h2>
            <input type="hidden" name="tipo_propriedade" value="{{ $haras->tipo_propriedade ?: 'fazenda' }}">
            <div class="grade-2">
                <label class="campo"><span>Nome da fazenda *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="150"></label>
                <div class="campo"><span>Nome do Haras (sufixo)<span id="seloSufixo" style="color:#047857;font-weight:600;margin-left:.4rem;white-space:nowrap" @if (! $haras->sufixo_confirmado) hidden @endif>✓ Confirmado<span id="codigoAssocInline">{{ $haras->sufixo_confirmado && $haras->sufixo_codigo ? ' · '.$haras->sufixo_codigo : '' }}</span></span></span>
                    <div class="campo-com-botao">
                        <input name="sufixo" id="campoSufixoNome" value="{{ $v('sufixo') }}" maxlength="120" placeholder="Ex.: MINHA TROPA" autocomplete="off">
                        <button type="button" class="botao botao--contorno botao--pequeno" id="btnVerificarSufixo" @if ($haras->sufixo_confirmado) hidden @endif>Verificar sufixo</button>
                    </div>
                    <small>O "sobrenome" dos animais na associação.</small>
                    <div id="statusSufixo" style="margin-top:.5rem;font-size:.82rem;@if ($haras->sufixo && ! $haras->sufixo_confirmado && $haras->sufixo_codigo) color:#b45309 @elseif ($haras->sufixo && ! $haras->sufixo_confirmado) color:#6b7280 @else display:none @endif">
                        @if ($haras->sufixo && ! $haras->sufixo_confirmado && $haras->sufixo_codigo)
                            ⏳ Pendente — código {{ $haras->sufixo_codigo }}. Confira nome, cidade e UF.
                        @elseif ($haras->sufixo && ! $haras->sufixo_confirmado)
                            Sufixo informado, não verificado. Clique em "Verificar sufixo".
                        @endif
                    </div>
                    <div id="resultadoSufixo" style="margin-top:.5rem"></div>
                </div>
                <label class="campo"><span>Titularidade</span><input value="{{ \App\Models\Empresa::TITULARIDADES[$haras->tipo_documento] ?? 'Não informada' }}" disabled></label>
                <label class="campo"><span>{{ $pf ? 'CPF do proprietário' : 'CNPJ' }}</span><input value="{{ $haras->documentoFormatado() }}" disabled><small>É a identificação única da propriedade. Para alterar, fale com o atendimento.</small></label>
                <label class="campo"><span>{{ $pf ? 'Proprietário' : 'Razão social' }}</span><input name="razao_social" value="{{ $v('razao_social') }}" maxlength="190" @if ($pf && $titular) readonly @endif></label>
                <label class="campo"><span>Inscrição estadual / produtor rural</span><input name="inscricao_estadual" value="{{ $v('inscricao_estadual') }}" maxlength="30"></label>
                <label class="campo"><span>Área (hectares)</span><input name="area_hectares" value="{{ old('area_hectares', $haras->area_hectares !== null ? number_format((float) $haras->area_hectares, 2, ',', '.') : '') }}" inputmode="decimal"></label>
                <label class="campo"><span>Quantidade de animais</span><select name="quantidade_animais_estimada"><option value=""></option>@foreach (\App\Models\Empresa::QUANTIDADES as $q)<option @selected($v('quantidade_animais_estimada') === $q)>{{ $q }}</option>@endforeach</select></label>
            </div>
            @if ($titular)
            <p class="suave" style="margin-top:12px"><small>Criador / proprietário titular: <b>{{ $titular->nome }}</b> ({{ $titular->cpfFormatado() }}), do cadastro de Profissionais. <a href="{{ route('painel.profissionais') }}">Ver criador e equipe</a></small></p>
            @endif
        </section>
    </div>

    <div class="painel-aba painel-aba--oculto" id="aba-contato">
        <section class="cartao"><h2>Contato {{ $haras->doRotulo() }}</h2>
            <div class="grade-3">
                <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ $v('email') }}" maxlength="190"></label>
                <label class="campo"><span>Telefone</span><input name="telefone" value="{{ $v('telefone') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>WhatsApp</span><input name="whatsapp" value="{{ $v('whatsapp') }}" data-mascara="telefone" maxlength="30"></label>
            </div>
        </section>
        <section class="cartao"><h2>Responsável / proprietário</h2>
            <div class="grade-3">
                <label class="campo"><span>Nome do responsável</span><input name="responsavel_nome" value="{{ $v('responsavel_nome') }}" maxlength="150" @if ($titular) readonly @endif>@if ($titular)<small>Vem do cadastro de Profissional do titular.</small>@endif</label>
                <label class="campo"><span>E-mail do responsável</span><input type="email" name="responsavel_email" value="{{ $v('responsavel_email') }}" maxlength="190"></label>
                <label class="campo"><span>Telefone do responsável</span><input name="responsavel_telefone" value="{{ $v('responsavel_telefone') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>Telefone residencial</span><input name="telefone_residencial" value="{{ $v('telefone_residencial') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>Celular</span><input name="telefone_celular" value="{{ $v('telefone_celular') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>Telefone comercial</span><input name="telefone_comercial" value="{{ $v('telefone_comercial') }}" data-mascara="telefone" maxlength="30"></label>
            </div>
        </section>
    </div>

    <div class="painel-aba painel-aba--oculto" id="aba-endereco">
        <section class="cartao"><h2>Endereço da propriedade</h2>
            <div class="grade-3">
                <label class="campo"><span>CEP</span><input name="cep" value="{{ $cep('cep') }}" data-mascara="cep" data-cep maxlength="9"></label>
                <label class="campo campo--duplo"><span>Logradouro / estrada</span><input name="logradouro" value="{{ $v('logradouro') }}" maxlength="190"></label>
                <label class="campo"><span>Número / km</span><input name="numero" value="{{ $v('numero') }}" maxlength="20"></label>
                <label class="campo"><span>Complemento</span><input name="complemento" value="{{ $v('complemento') }}" maxlength="100"></label>
                <label class="campo"><span>Bairro / zona rural</span><input name="bairro" value="{{ $v('bairro') }}" maxlength="100"></label>
                <x-uf-municipio :uf="$haras->uf" :municipio-id="$haras->municipio_id" :cidade="$haras->cidade" obrigatorio />
            </div>
        </section>
        <section class="cartao"><h2>Localização e como chegar</h2>
            <div class="grade-3">
                <label class="campo"><span>Latitude</span><input name="latitude" value="{{ $v('latitude') !== null ? rtrim(rtrim((string) $v('latitude'), '0'), '.') : '' }}" inputmode="decimal" placeholder="-20.123456"></label>
                <label class="campo"><span>Longitude</span><input name="longitude" value="{{ $v('longitude') !== null ? rtrim(rtrim((string) $v('longitude'), '0'), '.') : '' }}" inputmode="decimal" placeholder="-44.123456"></label>
                <label class="campo campo--duplo" style="grid-column:1/-1"><span>Como chegar</span><textarea name="como_chegar" rows="2" maxlength="400" placeholder="Ex.: Saindo da BR-262, entrar na estrada de terra do km 12 e seguir 3 km.">{{ $v('como_chegar') }}</textarea></label>
            </div>
        </section>
    </div>

    <div class="painel-aba painel-aba--oculto" id="aba-correspondencia">
        <section class="cartao"><h2>Endereço de correspondência do proprietário</h2>
            <p class="suave" style="margin-bottom:1rem">Preencha se for diferente do endereço da propriedade.</p>
            <div class="grade-3">
                <label class="campo"><span>CEP</span><input name="corresp_cep" value="{{ $cep('corresp_cep') }}" data-mascara="cep" maxlength="9"></label>
                <label class="campo campo--duplo"><span>Logradouro</span><input name="corresp_logradouro" value="{{ $v('corresp_logradouro') }}" maxlength="190"></label>
                <label class="campo"><span>Número</span><input name="corresp_numero" value="{{ $v('corresp_numero') }}" maxlength="20"></label>
                <label class="campo"><span>Complemento</span><input name="corresp_complemento" value="{{ $v('corresp_complemento') }}" maxlength="100"></label>
                <label class="campo"><span>Bairro</span><input name="corresp_bairro" value="{{ $v('corresp_bairro') }}" maxlength="100"></label>
                <x-uf-municipio prefixo="corresp_" :uf="$haras->corresp_uf" :municipio-id="$haras->corresp_municipio_id" :cidade="$haras->corresp_cidade" />
            </div>
        </section>
    </div>

    <div class="painel-aba painel-aba--oculto" id="aba-criacao">
        <section class="cartao"><h2>Raças criadas</h2>
            @php $racasSel = (array) old('racas', $racas); @endphp
            <div class="grade-opcoes">
                @foreach (\App\Models\Empresa::RACAS as $raca)<label class="opcao-check"><input type="checkbox" name="racas[]" value="{{ $raca }}" @checked(in_array($raca, $racasSel))> {{ $raca }}</label>@endforeach
            </div>
        </section>
        <section class="cartao"><h2>Preferências de marcha</h2>
            <div class="grade-opcoes">
                @foreach (\App\Models\Empresa::MARCHAS as $valor => $texto)<label class="opcao-check"><input type="checkbox" name="marchas[]" value="{{ $valor }}" @checked(in_array($valor, (array) old('marchas', $haras->marchasArray())))> {{ $texto }}</label>@endforeach
            </div>
        </section>
        <section class="cartao"><h2>Finalidade da criação</h2>
            <div class="grade-opcoes">
                @foreach (\App\Models\Empresa::FINALIDADES as $valor => $texto)<label class="opcao-check"><input type="checkbox" name="finalidades[]" value="{{ $valor }}" @checked(in_array($valor, (array) old('finalidades', $haras->finalidadesArray())))> {{ $texto }}</label>@endforeach
            </div>
        </section>
    </div>

    @if ($temPagina)
    <div class="painel-aba painel-aba--oculto" id="aba-pagina">
        @include('partes.pagina-propriedade', ['empresa' => $haras])
    </div>
    @endif

    <div class="acoes-form"><button class="botao botao--primario">Salvar</button></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var f = document.querySelector('[data-abas]'); if (!f) return;
  var abas = f.querySelectorAll('.aba-cadastro'), paineis = f.querySelectorAll('.painel-aba');
  var abrir = function (nome) {
    abas.forEach(function (a) { a.classList.toggle('aba-cadastro--ativa', a.dataset.aba === nome); });
    paineis.forEach(function (p) { p.classList.toggle('painel-aba--oculto', p.id !== 'aba-' + nome); });
    try { sessionStorage.setItem('mt-aba-dados', nome); } catch (e) {}
  };
  abas.forEach(function (a) { a.addEventListener('click', function () { abrir(a.dataset.aba); }); });
  // Com erro de validação, abre a aba do primeiro campo com problema; senão, volta à última aba usada.
  var erros = (f.dataset.erros || '').split(',').filter(Boolean), alvo = null;
  erros.some(function (nome) { var c = f.querySelector('[name="' + nome + '"], [name="' + nome + '[]"]'); var p = c && c.closest('.painel-aba'); if (p) { alvo = p.id.replace('aba-', ''); return true; } return false; });
  if (!alvo && !erros.length) { try { alvo = sessionStorage.getItem('mt-aba-dados'); } catch (e) {} }
  // Atalho do menu "Página, logomarca e cores": /painel/haras#pagina abre direto a seção
  var daUrl = function () { var h = location.hash.replace('#', ''); if (h && f.querySelector('#aba-' + h)) { abrir(h); return true; } return false; };
  if (!erros.length && daUrl()) alvo = null;
  window.addEventListener('hashchange', daUrl);
  if (alvo && f.querySelector('#aba-' + alvo)) abrir(alvo);
});

// Verificar sufixo na base da associação
(function () {
    var campo = document.getElementById('campoSufixoNome'), botao = document.getElementById('btnVerificarSufixo'), resultado = document.getElementById('resultadoSufixo');
    var statusBloco = document.getElementById('statusSufixo');
    if (!campo || !botao) return;
    var token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name=_token]')?.value || '';

    var codigoInline = document.getElementById('codigoAssocInline');
    var selo = document.getElementById('seloSufixo');
    var atualizarStatus = function (status, codigo) {
        if (!statusBloco) return;
        if (status === 'CONFIRMADO') {
            botao.hidden = true;
            if (codigoInline) codigoInline.textContent = codigo ? ' · ' + codigo : '';
            if (selo) selo.hidden = false;
            statusBloco.style.cssText = 'display:none';
            statusBloco.innerHTML = '';
        } else if (status === 'PENDENTE' || status === 'CONFLITO') {
            botao.hidden = false;
            if (selo) selo.hidden = true;
            statusBloco.style.cssText = 'margin-top:.5rem;font-size:.82rem;color:#b45309';
            statusBloco.innerHTML = '⏳ Pendente — código ' + codigo + '. Confira nome, cidade e UF.';
        } else if (status === 'NAO_CONFIRMADO') {
            botao.hidden = false;
            if (selo) selo.hidden = true;
            statusBloco.style.cssText = 'margin-top:.5rem;font-size:.82rem;color:#6b7280';
            statusBloco.innerHTML = 'Sufixo não localizado na base da associação.';
        } else {
            botao.hidden = false;
            if (selo) selo.hidden = true;
            statusBloco.style.cssText = 'display:none';
            statusBloco.innerHTML = '';
        }
        if (statusBloco.innerHTML) statusBloco.style.display = '';
    };

    var verificar = function () {
        var q = campo.value.trim();
        if (q.length < 2) { resultado.innerHTML = '<p class="suave">Digite o nome do Haras para verificar.</p>'; return; }
        resultado.innerHTML = '<p class="suave">Verificando…</p>';
        botao.disabled = true;
        fetch('{{ route("painel.haras.conferir-sufixo") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ sufixo: q })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            botao.disabled = false;
            if (d.status === 'CONFIRMADO') {
                atualizarStatus('CONFIRMADO', d.codigo);
                resultado.innerHTML = '<small style="color:#047857;font-weight:600">✓ ' + d.mensagem + '</small>';
            } else if (d.status === 'PENDENTE' || d.status === 'CONFLITO') {
                atualizarStatus('PENDENTE', d.codigo);
                resultado.innerHTML = '';
            } else if (d.status === 'NAO_CONFIRMADO') {
                atualizarStatus('NAO_CONFIRMADO', null);
                resultado.innerHTML = '';
            } else if (d.status === 'LIMPO') {
                atualizarStatus('LIMPO', null);
                resultado.innerHTML = '';
            } else {
                resultado.innerHTML = '<p class="suave">' + (d.mensagem || 'Erro ao verificar.') + '</p>';
            }
        })
        .catch(function () { botao.disabled = false; resultado.innerHTML = '<p class="suave" style="color:#b42318">Não foi possível verificar agora.</p>'; });
    };
    botao.addEventListener('click', verificar);
    campo.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); verificar(); } });
})();
</script>
@endsection
