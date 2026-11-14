@extends('layouts.admin')
@section('titulo', $empresa->exists ? 'Editar '.$empresa->nome : 'Novo Haras / Fazenda')
@section('conteudo')
<div class="cabecalho">
    <div><h1>{{ $empresa->exists ? 'Editar '.$empresa->rotulo() : 'Novo Haras / Fazenda' }}</h1>@if ($empresa->exists)<p>{{ $empresa->nome }}</p>@endif</div>
    <a href="{{ $empresa->exists ? route('admin.haras.show', $empresa) : route('admin.haras') }}" class="botao botao--contorno">Cancelar</a>
</div>
@php
    $v = fn ($campo) => old($campo, $empresa->{$campo});
    $tit = old('tipo_documento', $empresa->tipo_documento);
    $titularCpf = old('titular_cpf', $titular?->cpf ? \App\Support\Documento::formatar($titular->cpf) : '');
@endphp
<form method="post" action="{{ $empresa->exists ? route('admin.haras.salvar', $empresa) : route('admin.haras.criar') }}" class="formulario" novalidate>
    @csrf @if ($empresa->exists) @method('put') @endif

    {{-- Abas de navegação --}}
    <nav class="abas-cadastro" id="abasCadastro">
        <button type="button" class="aba-cadastro aba-cadastro--ativa" data-aba="identificacao">Identificação</button>
        <button type="button" class="aba-cadastro" data-aba="criador">Criador / proprietário</button>
        <button type="button" class="aba-cadastro" data-aba="contato">Contato</button>
        <button type="button" class="aba-cadastro" data-aba="endereco">Endereço da propriedade</button>
        @if ($v003)<button type="button" class="aba-cadastro" data-aba="proprietario">Endereço do proprietário</button>@endif
        @if ($v003)<button type="button" class="aba-cadastro" data-aba="criacao">Criação</button>@endif
        @if ($v003)<button type="button" class="aba-cadastro" data-aba="marca">Marca e subsite</button>@endif
        <button type="button" class="aba-cadastro" data-aba="observacoes">Observações</button>
    </nav>

    {{-- 1. Identificação --}}
    <div class="painel-aba" id="aba-identificacao">
        <section class="cartao">
            <h2>Identificação do Haras / Fazenda</h2>
            <div class="grade-2">
                <label class="campo"><span>Tipo de propriedade *</span><select name="tipo_propriedade" required>
                    @foreach (\App\Models\Empresa::TIPOS_PROPRIEDADE as $valor => $texto)<option value="{{ $valor }}" @selected($v('tipo_propriedade') === $valor)>{{ $texto }}</option>@endforeach
                </select></label>
                <label class="campo"><span>Nome do Haras / Fazenda *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="150" placeholder="Ex.: Haras Vista Alegre"></label>
                <div class="campo"><span>Titularidade *</span>
                    <div class="opcoes-situacao">
                        @foreach (\App\Models\Empresa::TITULARIDADES as $valor => $texto)
                        <label class="opcao-cartao"><input type="radio" name="tipo_documento" value="{{ $valor }}" @checked($tit === $valor) required> {{ $texto }}</label>
                        @endforeach
                    </div>
                    <small>O CPF ou CNPJ é a identificação única da propriedade: não existem duas com o mesmo documento.</small>
                </div>
                <label class="campo"><span id="rotuloDocumento">{{ $tit === 'cnpj' ? 'CNPJ *' : ($tit === 'cpf' ? 'CPF do proprietário *' : 'CPF ou CNPJ *') }}</span>
                    <input name="documento" id="documento" value="{{ old('documento', $empresa->documento ? $empresa->documentoFormatado() : '') }}" inputmode="numeric" maxlength="18" required autocomplete="off">
                    <small id="ajudaDocumento">Pessoa Física: o CPF precisa já estar na base de Profissionais (aba "Criador / proprietário").</small></label>
                <label class="campo" data-so="cnpj"><span>Razão social *</span><input name="razao_social" value="{{ $v('razao_social') }}" maxlength="190"></label>
                <label class="campo"><span>Inscrição estadual / produtor rural</span><input name="inscricao_estadual" value="{{ $v('inscricao_estadual') }}" maxlength="30"></label>
                <label class="campo"><span>Área (hectares)</span><input name="area_hectares" value="{{ old('area_hectares', $empresa->area_hectares !== null ? number_format((float) $empresa->area_hectares, 2, ',', '.') : '') }}" inputmode="decimal"></label>
                @if (! $empresa->exists)
                <label class="campo"><span>Situação inicial</span><select name="situacao">
                    @foreach (\App\Models\Empresa::SITUACOES as $valor => $texto)@continue(in_array($valor, ['suspenso', 'encerrado']))<option value="{{ $valor }}" @selected(old('situacao', $empresa->situacao) === $valor)>{{ $texto }}</option>@endforeach
                </select></label>
                @endif
                @if ($planos->isNotEmpty())
                <label class="campo"><span>Plano</span><select name="plano_id"><option value="">Sem plano</option>
                    @foreach ($planos as $p)<option value="{{ $p->id }}" @selected((string) $v('plano_id') === (string) $p->id)>{{ $p->nome }}</option>@endforeach
                </select></label>
                @endif
            </div>
        </section>
    </div>

    {{-- Criador / proprietário: a busca por CPF é o primeiro campo e consulta a base global de Profissionais --}}
    <div class="painel-aba painel-aba--oculto" id="aba-criador">
        <section class="cartao">
            <h2>Criador / proprietário</h2>
            <p class="suave" style="margin-bottom:1rem">Busque pelo CPF. Encontrado na base de Profissionais, os dados são puxados e a pessoa fica vinculada como criador / proprietário titular. Pessoa Física: é o mesmo CPF da propriedade.</p>
            <div class="busca-cpf" id="buscaTitular" data-url="{{ route('admin.haras.consultar-cpf') }}">
                <label class="campo"><span>CPF do criador / proprietário</span><input name="titular_cpf" id="titularCpf" value="{{ $titularCpf }}" inputmode="numeric" data-mascara="cpf" maxlength="14" autocomplete="off"></label>
                <button type="button" class="botao botao--primario" id="btnTitular"><x-icone nome="busca" :tamanho="18" /> Buscar</button>
            </div>
            <div id="resultadoTitular" style="margin-top:14px" aria-live="polite">
                @if ($titular)
                <div class="ficha-profissional"><b>✓ {{ $titular->nome }}</b> · CPF {{ $titular->cpfFormatado() }}<br><small>{{ $titular->especialidadesTexto() }} · {{ $titular->cidadeUf() }} · <a href="{{ route('admin.profissionais.show', $titular) }}" target="_blank" rel="noopener">abrir cadastro</a></small></div>
                @endif
            </div>
        </section>
    </div>

    {{-- 2. Contato --}}
    <div class="painel-aba painel-aba--oculto" id="aba-contato">
        <section class="cartao">
            <h2>Contato do Haras / Fazenda</h2>
            <div class="grade-3">
                <label class="campo"><span>E-mail da propriedade</span><input type="email" name="email" value="{{ $v('email') }}" maxlength="190"></label>
                <label class="campo"><span>Telefone</span><input name="telefone" value="{{ $v('telefone') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>WhatsApp</span><input name="whatsapp" value="{{ $v('whatsapp') }}" data-mascara="telefone" maxlength="30"></label>
            </div>
        </section>
        <section class="cartao">
            <h2>Dados do responsável / proprietário</h2>
            <p class="suave" style="margin-bottom:1rem">Com um criador / proprietário vinculado pelo CPF, o nome vem do cadastro de Profissional; e-mail e telefone em branco também.</p>
            <div class="grade-3">
                <label class="campo"><span>Nome do responsável</span><input name="responsavel_nome" value="{{ $v('responsavel_nome') }}" maxlength="150"></label>
                <label class="campo"><span>E-mail do responsável</span><input type="email" name="responsavel_email" value="{{ $v('responsavel_email') }}" maxlength="190"></label>
                <label class="campo"><span>Telefone do responsável</span><input name="responsavel_telefone" value="{{ $v('responsavel_telefone') }}" data-mascara="telefone" maxlength="30"></label>
                @if ($v003)
                <label class="campo"><span>Telefone residencial</span><input name="telefone_residencial" value="{{ $v('telefone_residencial') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>Telefone celular</span><input name="telefone_celular" value="{{ $v('telefone_celular') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>Telefone comercial</span><input name="telefone_comercial" value="{{ $v('telefone_comercial') }}" data-mascara="telefone" maxlength="30"></label>
                @endif
            </div>
        </section>
    </div>

    {{-- 3. Endereço da propriedade --}}
    <div class="painel-aba painel-aba--oculto" id="aba-endereco">
        <section class="cartao">
            <h2>Endereço da propriedade</h2>
            <div class="grade-3">
                <label class="campo"><span>CEP</span><input name="cep" value="{{ old('cep', $empresa->cep ? substr($empresa->cep, 0, 5).'-'.substr($empresa->cep, 5) : '') }}" inputmode="numeric" data-mascara="cep" data-cep maxlength="9"></label>
                <label class="campo campo--duplo"><span>Logradouro / estrada</span><input name="logradouro" value="{{ $v('logradouro') }}" maxlength="190"></label>
                <label class="campo"><span>Número / km</span><input name="numero" value="{{ $v('numero') }}" maxlength="20"></label>
                <label class="campo"><span>Complemento</span><input name="complemento" value="{{ $v('complemento') }}" maxlength="100"></label>
                <label class="campo"><span>Bairro / zona rural</span><input name="bairro" value="{{ $v('bairro') }}" maxlength="100"></label>
                <x-uf-municipio :uf="$empresa->uf" :municipio-id="$empresa->municipio_id" :cidade="$empresa->cidade" obrigatorio />
            </div>
            @if ($v003)
            <h3 style="margin-top:1.5rem">Coordenadas geográficas</h3>
            <div class="grade-3">
                <label class="campo"><span>Latitude</span><input name="latitude" value="{{ old('latitude', $empresa->latitude) }}" placeholder="Ex.: -19.273122629866" inputmode="decimal"></label>
                <label class="campo"><span>Longitude</span><input name="longitude" value="{{ old('longitude', $empresa->longitude) }}" placeholder="Ex.: -43.251439288235" inputmode="decimal"></label>
                <label class="campo campo--duplo"><span>Como chegar à propriedade</span><textarea name="como_chegar" rows="3" maxlength="400" placeholder="Ex.: A 6 km do município, pela estrada...">{{ $v('como_chegar') }}</textarea></label>
            </div>
            @endif
        </section>
    </div>

    {{-- 4. Endereço do proprietário (correspondência) --}}
    @if ($v003)
    <div class="painel-aba painel-aba--oculto" id="aba-proprietario">
        <section class="cartao">
            <h2>Endereço do proprietário (correspondência)</h2>
            <p class="suave" style="margin-bottom:1rem">Endereço residencial do proprietário/criador, separado da propriedade.</p>
            <div class="grade-3">
                <label class="campo"><span>CEP</span><input name="corresp_cep" value="{{ old('corresp_cep', $empresa->corresp_cep ? substr($empresa->corresp_cep, 0, 5).'-'.substr($empresa->corresp_cep, 5) : '') }}" inputmode="numeric" data-mascara="cep" data-cep maxlength="9"></label>
                <label class="campo campo--duplo"><span>Logradouro</span><input name="corresp_logradouro" value="{{ $v('corresp_logradouro') }}" maxlength="190"></label>
                <label class="campo"><span>Número</span><input name="corresp_numero" value="{{ $v('corresp_numero') }}" maxlength="20"></label>
                <label class="campo"><span>Complemento</span><input name="corresp_complemento" value="{{ $v('corresp_complemento') }}" maxlength="100"></label>
                <label class="campo"><span>Bairro</span><input name="corresp_bairro" value="{{ $v('corresp_bairro') }}" maxlength="100"></label>
                <x-uf-municipio prefixo="corresp_" :uf="$empresa->corresp_uf" :municipio-id="$empresa->corresp_municipio_id" :cidade="$empresa->corresp_cidade" />
            </div>
        </section>
    </div>
    @endif

    {{-- 5. Criação --}}
    @if ($v003)
    <div class="painel-aba painel-aba--oculto" id="aba-criacao">
        <section class="cartao">
            <h2>Raças criadas</h2>
            <p class="suave" style="margin-bottom:1rem">Selecione todas as raças que esta propriedade cria.</p>
            @php $racasSel = old('racas', $racasSelecionadas); @endphp
            <div class="grade-opcoes">
                @foreach (\App\Models\Empresa::RACAS as $raca)
                <label class="opcao-check"><input type="checkbox" name="racas[]" value="{{ $raca }}" @checked(in_array($raca, (array) $racasSel))> {{ $raca }}</label>
                @endforeach
            </div>
        </section>
        <section class="cartao">
            <h2>Preferências de marcha</h2>
            @php $marchasSel = $empresa->marchasArray(); @endphp
            <div class="grade-opcoes">
                @foreach (\App\Models\Empresa::MARCHAS as $valor => $texto)
                <label class="opcao-check"><input type="checkbox" name="marchas[]" value="{{ $valor }}" @checked(in_array($valor, old('marchas', $marchasSel)))> {{ $texto }}</label>
                @endforeach
            </div>
        </section>
        <section class="cartao">
            <h2>Finalidade da criação</h2>
            @php $finalSel = $empresa->finalidadesArray(); @endphp
            <div class="grade-opcoes">
                @foreach (\App\Models\Empresa::FINALIDADES as $valor => $texto)
                <label class="opcao-check"><input type="checkbox" name="finalidades[]" value="{{ $valor }}" @checked(in_array($valor, old('finalidades', $finalSel)))> {{ $texto }}</label>
                @endforeach
            </div>
        </section>
    </div>
    @endif

    {{-- 6. Marca e subsite --}}
    @if ($v003)
    <div class="painel-aba painel-aba--oculto" id="aba-marca">
        <section class="cartao">
            <h2>Marca / logomarca do criador</h2>
            <p class="suave" style="margin-bottom:1rem">Ferro, marca ou logomarca que identifica o criador. A imagem pode ser enviada após salvar o cadastro.</p>
            <label class="campo"><span>Descrição da marca</span><textarea name="marca_descricao" rows="3" maxlength="500" placeholder="Ex.: Ferro em forma de estrela com as iniciais BE">{{ $v('marca_descricao') }}</textarea></label>
            @if ($empresa->exists && $empresa->marca_arquivo)
            <div style="margin:1rem 0"><img src="{{ asset($empresa->marca_arquivo) }}" alt="Marca do criador" style="max-height:120px;border:1px solid #ddd;border-radius:8px;padding:8px"></div>
            @endif
        </section>
        <section class="cartao">
            <h2>Site e subsite do Haras / Fazenda</h2>
            <div class="grade-2">
                <label class="campo"><span>Site próprio (URL)</span><input type="url" name="site_url" value="{{ $v('site_url') }}" maxlength="255" placeholder="https://www.meuharas.com.br"></label>
                <label class="campo"><span>Subsite MinhaTropa</span>
                    <div style="display:flex;align-items:center;gap:4px"><span class="suave" style="white-space:nowrap">minhatropa.com.br/h/</span><input name="subsite_slug" value="{{ $v('subsite_slug') }}" maxlength="60" placeholder="meu-haras" style="flex:1"></div>
                    <small class="suave">Endereço público da propriedade na plataforma. Só letras minúsculas, números e hifens.</small>
                </label>
            </div>
        </section>
    </div>
    @endif

    {{-- 7. Observações --}}
    <div class="painel-aba painel-aba--oculto" id="aba-observacoes">
        <section class="cartao">
            <h2>Observações internas</h2>
            <label class="campo"><textarea name="observacoes" rows="4" maxlength="5000" placeholder="Anotações da equipe (não aparecem para o cliente)">{{ $v('observacoes') }}</textarea></label>
        </section>
    </div>

    <div class="acoes-form"><button class="botao botao--primario">{{ $empresa->exists ? 'Salvar alterações' : 'Cadastrar Haras / Fazenda' }}</button></div>
</form>

@if ($v003 && $empresa->exists)
<section class="cartao" style="margin-top:1.5rem">
    <h2>Enviar marca / logomarca</h2>
    <form method="post" action="{{ route('admin.haras.marca', $empresa) }}" enctype="multipart/form-data" class="formulario">
        @csrf
        <div style="display:flex;align-items:end;gap:1rem;flex-wrap:wrap">
            <label class="campo" style="flex:1;min-width:200px"><span>Imagem da marca (JPG, PNG — máx. 2 MB)</span><input type="file" name="marca_arquivo" accept="image/*" required></label>
            <button class="botao botao--primario botao--pequeno">Enviar</button>
        </div>
    </form>
</section>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const abas = document.querySelectorAll('.aba-cadastro');
    const paineis = document.querySelectorAll('.painel-aba');
    function abrir(nome) {
        abas.forEach(function(a) { a.classList.toggle('aba-cadastro--ativa', a.dataset.aba === nome); });
        paineis.forEach(function(p) { p.classList.toggle('painel-aba--oculto', p.id !== 'aba-' + nome); });
    }
    abas.forEach(function(aba) { aba.addEventListener('click', function() { abrir(aba.dataset.aba); }); });

    // Titularidade: Pessoa Física (CPF) ou Pessoa Jurídica (CNPJ)
    const f = document.querySelector('form.formulario');
    const doc = document.getElementById('documento'), cpfTit = document.getElementById('titularCpf'), caixa = document.getElementById('resultadoTitular');
    const digitos = function(v) { return v.replace(/\D/g, ''); };
    const titularidade = function() { const m = f.querySelector('[name=tipo_documento]:checked'); return m ? m.value : ''; };
    const mascaraCpf = function(v) { return digitos(v).slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'); };
    const mascaraCnpj = function(v) { return digitos(v).slice(0, 14).replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2'); };
    const texto = function(t) { const d = document.createElement('div'); d.textContent = t == null ? '' : t; return d.innerHTML; };
    function ajustar() {
        const t = titularidade();
        document.getElementById('rotuloDocumento').textContent = t === 'cnpj' ? 'CNPJ *' : (t === 'cpf' ? 'CPF do proprietário *' : 'CPF ou CNPJ *');
        document.getElementById('ajudaDocumento').hidden = t !== 'cpf';
        f.querySelectorAll('[data-so]').forEach(function(e) { e.hidden = e.getAttribute('data-so') !== t; });
        doc.value = (t === 'cnpj' || (!t && digitos(doc.value).length > 11)) ? mascaraCnpj(doc.value) : mascaraCpf(doc.value);
        cpfTit.readOnly = t === 'cpf';
        if (t === 'cpf') { cpfTit.value = doc.value; }
    }
    function buscar() {
        const cpf = digitos(cpfTit.value);
        if (cpf.length !== 11) { caixa.innerHTML = '<div class="alerta alerta--erro"><p>Informe os 11 números do CPF.</p></div>'; return; }
        caixa.innerHTML = '<p class="suave">Consultando a base de Profissionais…</p>';
        fetch(document.getElementById('buscaTitular').dataset.url + '?cpf=' + cpf, { headers: { Accept: 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.situacao === 'encontrado') {
                    caixa.innerHTML = '<div class="ficha-profissional" data-titular-ok><b>✓ ' + texto(d.nome) + '</b> · CPF ' + texto(d.cpf) + '<br><small>' + texto(d.funcoes) + ' · ' + texto(d.cidade_uf) +
                        (d.email ? ' · ' + texto(d.email) : '') + (d.telefone ? ' · ' + texto(d.telefone) : '') + ' · <a href="' + texto(d.url) + '" target="_blank" rel="noopener">abrir cadastro</a></small></div>';
                    const p = function(n, v) { const c = f.querySelector('[name=' + n + ']'); if (c && v) c.value = v; };
                    p('responsavel_nome', d.nome);
                    const e = f.querySelector('[name=responsavel_email]'), tel = f.querySelector('[name=responsavel_telefone]');
                    if (e && !e.value && d.email) e.value = d.email;
                    if (tel && !tel.value && d.telefone) tel.value = d.telefone;
                } else if (d.situacao === 'nao_encontrado') {
                    caixa.innerHTML = '<div class="ficha-profissional ficha-profissional--novo" data-titular-novo><b>CPF não encontrado na base de Profissionais.</b> ' + texto(d.mensagem) +
                        '<br><a class="botao botao--primario botao--pequeno" style="margin-top:8px" href="' + texto(d.url) + '" target="_blank" rel="noopener">Cadastrar profissional</a></div>';
                } else {
                    caixa.innerHTML = '<div class="alerta alerta--erro"><p>' + texto(d.mensagem) + '</p></div>';
                }
            })
            .catch(function() { caixa.innerHTML = '<div class="alerta alerta--erro"><p>Não foi possível consultar agora.</p></div>'; });
    }
    f.querySelectorAll('[name=tipo_documento]').forEach(function(r) { r.addEventListener('change', function() { ajustar(); if (titularidade() === 'cpf' && digitos(doc.value).length === 11) buscar(); }); });
    doc.addEventListener('input', function() { ajustar(); if (titularidade() === 'cpf' && digitos(doc.value).length === 11) buscar(); });
    document.getElementById('btnTitular').addEventListener('click', buscar);
    cpfTit.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); buscar(); } });
    ajustar();
    // Erro de validação: abre a aba onde está o primeiro campo com problema
    @if ($errors->hasAny(['nome', 'tipo_propriedade', 'tipo_documento', 'documento', 'razao_social'])) abrir('identificacao'); @elseif ($errors->has('titular_cpf')) abrir('criador'); @elseif ($errors->has('uf') || $errors->has('municipio_id')) abrir('endereco'); @elseif ($errors->has('corresp_uf') || $errors->has('corresp_municipio_id')) abrir('proprietario'); @endif
});
</script>
@endsection
