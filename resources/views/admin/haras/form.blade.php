@extends('layouts.admin')
@section('titulo', $empresa->exists ? 'Editar '.$empresa->nome : 'Novo Haras')
@section('conteudo')
<div class="cabecalho">
    <div><h1>{{ $empresa->exists ? 'Editar Haras' : 'Novo Haras' }}</h1>@if ($empresa->exists)<p>{{ $empresa->nome }}</p>@endif</div>
    <a href="{{ $empresa->exists ? route('admin.haras.show', $empresa) : route('admin.haras') }}" class="botao botao--contorno">Cancelar</a>
</div>
@php $v = fn ($campo) => old($campo, $empresa->{$campo}); @endphp
<form method="post" action="{{ $empresa->exists ? route('admin.haras.salvar', $empresa) : route('admin.haras.criar') }}" class="formulario">
    @csrf @if ($empresa->exists) @method('put') @endif

    {{-- Abas de navegação --}}
    <nav class="abas-cadastro" id="abasCadastro">
        <button type="button" class="aba-cadastro aba-cadastro--ativa" data-aba="identificacao">Identificação</button>
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
            <h2>Identificação do Haras</h2>
            <div class="grade-2">
                <label class="campo"><span>Nome do Haras *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="150" placeholder="Ex.: Haras Vista Alegre"></label>
                @if ($v003)
                <label class="campo"><span>Tipo de propriedade</span><select name="tipo_propriedade"><option value="">Selecione</option>
                    @foreach (\App\Models\Empresa::TIPOS_PROPRIEDADE as $valor => $texto)<option value="{{ $valor }}" @selected($v('tipo_propriedade') === $valor)>{{ $texto }}</option>@endforeach
                </select></label>
                @endif
                <label class="campo"><span>Razão social / nome do proprietário</span><input name="razao_social" value="{{ $v('razao_social') }}" maxlength="190"></label>
                <label class="campo"><span>CPF ou CNPJ</span><input name="documento" value="{{ old('documento', $empresa->documento ? $empresa->documentoFormatado() : '') }}" inputmode="numeric" data-mascara="documento" maxlength="18"></label>
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

    {{-- 2. Contato --}}
    <div class="painel-aba painel-aba--oculto" id="aba-contato">
        <section class="cartao">
            <h2>Contato do Haras</h2>
            <div class="grade-3">
                <label class="campo"><span>E-mail do Haras</span><input type="email" name="email" value="{{ $v('email') }}" maxlength="190"></label>
                <label class="campo"><span>Telefone</span><input name="telefone" value="{{ $v('telefone') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>WhatsApp</span><input name="whatsapp" value="{{ $v('whatsapp') }}" data-mascara="telefone" maxlength="30"></label>
            </div>
        </section>
        <section class="cartao">
            <h2>Dados do responsável / proprietário</h2>
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
                <label class="campo"><span>Cidade</span><input name="cidade" value="{{ $v('cidade') }}" maxlength="100"></label>
                <label class="campo"><span>UF</span><select name="uf"><option value=""></option>@foreach ($ufs as $uf)<option @selected($v('uf') === $uf)>{{ $uf }}</option>@endforeach</select></label>
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
                <label class="campo"><span>Cidade</span><input name="corresp_cidade" value="{{ $v('corresp_cidade') }}" maxlength="100"></label>
                <label class="campo"><span>UF</span><select name="corresp_uf"><option value=""></option>@foreach ($ufs as $uf)<option @selected($v('corresp_uf') === $uf)>{{ $uf }}</option>@endforeach</select></label>
            </div>
        </section>
    </div>
    @endif

    {{-- 5. Criação --}}
    @if ($v003)
    <div class="painel-aba painel-aba--oculto" id="aba-criacao">
        <section class="cartao">
            <h2>Raças criadas</h2>
            <p class="suave" style="margin-bottom:1rem">Selecione todas as raças que este Haras cria.</p>
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
            <h2>Site e subsite do Haras</h2>
            <div class="grade-2">
                <label class="campo"><span>Site próprio (URL)</span><input type="url" name="site_url" value="{{ $v('site_url') }}" maxlength="255" placeholder="https://www.meuharas.com.br"></label>
                <label class="campo"><span>Subsite MinhaTropa</span>
                    <div style="display:flex;align-items:center;gap:4px"><span class="suave" style="white-space:nowrap">minhatropa.com.br/h/</span><input name="subsite_slug" value="{{ $v('subsite_slug') }}" maxlength="60" placeholder="meu-haras" style="flex:1"></div>
                    <small class="suave">Endereço público do Haras na plataforma. Só letras minúsculas, números e hifens.</small>
                </label>
            </div>
        </section>
    </div>
    @endif

    {{-- 7. Observações --}}
    <div class="painel-aba painel-aba--oculto" id="aba-observacoes">
        <section class="cartao">
            <h2>Observações internas</h2>
            <label class="campo"><textarea name="observacoes" rows="4" maxlength="5000" placeholder="Anotações da equipe (não aparecem para o Haras)">{{ $v('observacoes') }}</textarea></label>
        </section>
    </div>

    <div class="acoes-form"><button class="botao botao--primario">{{ $empresa->exists ? 'Salvar alterações' : 'Cadastrar Haras' }}</button></div>
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
    abas.forEach(function(aba) {
        aba.addEventListener('click', function() {
            abas.forEach(function(a) { a.classList.remove('aba-cadastro--ativa'); });
            paineis.forEach(function(p) { p.classList.add('painel-aba--oculto'); });
            aba.classList.add('aba-cadastro--ativa');
            document.getElementById('aba-' + aba.dataset.aba).classList.remove('painel-aba--oculto');
        });
    });
});
</script>
@endsection
