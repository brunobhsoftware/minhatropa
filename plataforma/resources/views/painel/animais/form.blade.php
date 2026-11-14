@extends('layouts.painel')
@section('titulo', $animal->exists ? $animal->nome : 'Novo animal')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.animais') }}">Animais</a> ›</p><h1>{{ $animal->exists ? $animal->nome : 'Novo animal' }}</h1></div></div>
@php $v = fn ($c) => old($c, $animal->{$c}); @endphp
<form method="post" action="{{ $animal->exists ? route('painel.animais.salvar', $animal) : route('painel.animais.criar') }}" class="formulario">
    @csrf @if ($animal->exists) @method('put') @endif

@if (! \App\Support\Esquema::temV005())
    {{-- Formulário simplificado (sem V005) --}}
    <section class="cartao"><h2>Identificação</h2>
        <div class="grade-3">
            <label class="campo"><span>Nome *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="120"></label>
            <label class="campo"><span>Sexo</span><select name="sexo"><option value=""></option><option value="macho" @selected($v('sexo') === 'macho')>Macho</option><option value="femea" @selected($v('sexo') === 'femea')>Fêmea</option></select></label>
            <label class="campo"><span>Categoria</span><select name="categoria"><option value=""></option>@foreach (\App\Models\Animal::CATEGORIAS as $k => $t)<option value="{{ $k }}" @selected($v('categoria') === $k)>{{ $t }}</option>@endforeach</select></label>
            <label class="campo"><span>Raça</span><input name="raca" value="{{ $v('raca') }}" maxlength="80" placeholder="Ex.: Mangalarga Marchador"></label>
            <label class="campo"><span>Pelagem</span><input name="pelagem" value="{{ $v('pelagem') }}" maxlength="80" placeholder="Ex.: Alazã"></label>
            <label class="campo"><span>Nascimento</span><input type="date" name="data_nascimento" value="{{ old('data_nascimento', $animal->data_nascimento?->format('Y-m-d')) }}"></label>
            <label class="campo"><span>Registro na associação</span><input name="registro" value="{{ $v('registro') }}" maxlength="60"></label>
            <label class="campo"><span>Microchip</span><input name="microchip" value="{{ $v('microchip') }}" maxlength="40"></label>
        </div>
    </section>
    <section class="cartao"><h2>Genealogia</h2>
        <div class="grade-2">
            <label class="campo"><span>Pai</span><input name="pai" value="{{ $v('pai') }}" maxlength="120"></label>
            <label class="campo"><span>Mãe</span><input name="mae" value="{{ $v('mae') }}" maxlength="120"></label>
        </div>
    </section>
    <section class="cartao"><h2>Observações</h2><label class="campo"><textarea name="observacoes" rows="3" maxlength="5000">{{ $v('observacoes') }}</textarea></label></section>
    <div class="acoes-form"><a href="{{ route('painel.animais') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">{{ $animal->exists ? 'Salvar' : 'Cadastrar animal' }}</button></div>
</form>
@else
    <div class="abas-cadastro">
        <button type="button" class="aba ativa" data-aba="identificacao">Identificação</button>
        <button type="button" class="aba" data-aba="registro">Registro e saúde</button>
        <button type="button" class="aba" data-aba="aquisicao">Aquisição</button>
        <button type="button" class="aba" data-aba="genealogia">Genealogia</button>
        <button type="button" class="aba" data-aba="observacoes">Observações</button>
    </div>

    {{-- Identificação --}}
    <div class="painel-aba ativo" id="aba-identificacao">
        <section class="cartao"><h2>Identificação do animal</h2>
            <div class="grade-3">
                <label class="campo"><span>Nome *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="120"></label>
                <label class="campo"><span>Nome de registro</span><input name="nome_registro" value="{{ $v('nome_registro') }}" maxlength="150" placeholder="Nome oficial na associação"></label>
                <label class="campo"><span>Apelido / nome de baia</span><input name="apelido" value="{{ $v('apelido') }}" maxlength="80"></label>
                <label class="campo"><span>Espécie</span>
                    <select name="especie" id="selEspecie">
                        @foreach (\App\Models\Animal::ESPECIES as $k => $t)
                        <option value="{{ $k }}" @selected($v('especie') === $k || (! $v('especie') && $k === 'equino'))>{{ $t }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="campo"><span>Sexo</span>
                    <select name="sexo"><option value=""></option><option value="macho" @selected($v('sexo') === 'macho')>Macho</option><option value="femea" @selected($v('sexo') === 'femea')>Fêmea</option></select>
                </label>
                <label class="campo"><span>Categoria</span>
                    <select name="categoria" id="selCategoria">
                        <option value=""></option>
                        @foreach (\App\Models\Animal::CATEGORIAS as $k => $t)
                        <option value="{{ $k }}" @selected($v('categoria') === $k) data-especie="{{ collect(\App\Models\Animal::CATEGORIAS_POR_ESPECIE)->filter(fn($cats) => isset($cats[$k]))->keys()->implode(',') }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="campo"><span>Raça</span><input name="raca" value="{{ $v('raca') }}" maxlength="80" placeholder="Ex.: Mangalarga Marchador"></label>
                <label class="campo"><span>Pelagem</span>
                    <select name="pelagem"><option value=""></option>
                        @foreach (\App\Models\Animal::PELAGENS as $k => $t)
                        <option value="{{ $k }}" @selected($v('pelagem') === $k)>{{ $t }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="campo"><span>Complemento da pelagem</span><input name="pelagem_complemento" value="{{ $v('pelagem_complemento') }}" maxlength="80" placeholder="Variação ou detalhe"></label>
                <label class="campo"><span>Data de nascimento</span><input type="date" name="data_nascimento" value="{{ old('data_nascimento', $animal->data_nascimento?->format('Y-m-d')) }}"></label>
                <label class="campo"><span>Nº plantel</span><input name="numero_plantel" value="{{ $v('numero_plantel') }}" maxlength="30" placeholder="Número interno"></label>
                <label class="campo"><span>Local / baia</span><input name="local_baia" value="{{ $v('local_baia') }}" maxlength="80"></label>
            </div>
        </section>
    </div>

    {{-- Registro e saúde --}}
    <div class="painel-aba" id="aba-registro">
        <section class="cartao"><h2>Registro e saúde</h2>
            <div class="grade-3">
                <label class="campo"><span>Registro na associação</span><input name="registro" value="{{ $v('registro') }}" maxlength="60"></label>
                <label class="campo"><span>Associação</span>
                    <select name="registro_associacao"><option value=""></option>
                        @foreach (\App\Models\Animal::ASSOCIACOES as $k => $t)
                        <option value="{{ $k }}" @selected($v('registro_associacao') === $k)>{{ $t }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="campo"><span>Microchip</span><input name="microchip" value="{{ $v('microchip') }}" maxlength="40"></label>
                <label class="campo"><span>Peso (kg)</span><input type="number" step="0.1" name="peso_kg" value="{{ $v('peso_kg') }}" min="0" max="9999"></label>
                <label class="campo"><span>Altura na cernelha (cm)</span><input type="number" step="0.1" name="altura_cm" value="{{ $v('altura_cm') }}" min="0" max="999"></label>
            </div>
            <div style="display:flex;gap:2rem;flex-wrap:wrap;margin-top:1rem">
                <label class="opcao-check"><input type="hidden" name="registro_definitivo" value="0"><input type="checkbox" name="registro_definitivo" value="1" @checked(old('registro_definitivo', $animal->registro_definitivo))> Registro definitivo</label>
                <label class="opcao-check"><input type="hidden" name="dna_coletado" value="0"><input type="checkbox" name="dna_coletado" value="1" @checked(old('dna_coletado', $animal->dna_coletado))> DNA coletado</label>
                <label class="opcao-check"><input type="hidden" name="castrado" value="0"><input type="checkbox" name="castrado" value="1" @checked(old('castrado', $animal->castrado))> Castrado</label>
            </div>
        </section>
    </div>

    {{-- Aquisição --}}
    <div class="painel-aba" id="aba-aquisicao">
        <section class="cartao"><h2>Aquisição e situação</h2>
            <div class="grade-3">
                <label class="campo"><span>Data de aquisição</span><input type="date" name="data_aquisicao" value="{{ old('data_aquisicao', $animal->data_aquisicao?->format('Y-m-d')) }}"></label>
                <label class="campo"><span>Forma de aquisição</span>
                    <select name="forma_aquisicao"><option value=""></option>
                        @foreach (\App\Models\Animal::FORMAS_AQUISICAO as $k => $t)
                        <option value="{{ $k }}" @selected($v('forma_aquisicao') === $k)>{{ $t }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="campo"><span>Valor de aquisição (R$)</span><input type="number" step="0.01" name="valor_aquisicao" value="{{ $v('valor_aquisicao') }}" min="0"></label>
                <label class="campo"><span>Situação atual</span>
                    <select name="situacao">
                        @foreach (\App\Models\Animal::SITUACOES as $k => $t)
                        <option value="{{ $k }}" @selected(($v('situacao') ?? 'ativo') === $k)>{{ $t }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="campo"><span>Data da situação</span><input type="date" name="situacao_data" value="{{ old('situacao_data', $animal->situacao_data?->format('Y-m-d')) }}"></label>
                <label class="campo"><span>Motivo / observação</span><input name="situacao_motivo" value="{{ $v('situacao_motivo') }}" maxlength="255"></label>
            </div>
        </section>
    </div>

    {{-- Genealogia --}}
    <div class="painel-aba" id="aba-genealogia">
        <section class="cartao"><h2>Genealogia</h2>
            <p class="suave" style="margin-bottom:1rem">Pesquise o genitor cadastrado na plataforma ou digite o nome manualmente.</p>
            <div class="grade-2">
                <div>
                    <h3>Pai (garanhão)</h3>
                    <div style="display:flex;gap:8px;margin:.5rem 0">
                        <input id="buscaPai" placeholder="Pesquisar pai por nome ou registro…" style="flex:1" autocomplete="off">
                        <button type="button" id="btnBuscarPai" class="botao botao--contorno botao--pequeno">Pesquisar</button>
                    </div>
                    <div id="resultadoPai" style="margin-bottom:.5rem"></div>
                    <input type="hidden" name="pai_id" id="paiId" value="{{ $v('pai_id') }}">
                    <p id="paiSelecionado" class="suave" style="margin-bottom:.5rem">@if ($animal->pai_id && $animal->paiAnimal) Selecionado: <b>{{ $animal->paiAnimal->nome }}</b> <button type="button" onclick="document.getElementById('paiId').value='';this.parentNode.innerHTML=''" class="botao botao--contorno botao--pequeno">Limpar</button> @endif</p>
                    <label class="campo"><span>Nome do pai (se não cadastrado)</span><input name="pai" value="{{ $v('pai') }}" maxlength="120"></label>
                </div>
                <div>
                    <h3>Mãe (égua)</h3>
                    <div style="display:flex;gap:8px;margin:.5rem 0">
                        <input id="buscaMae" placeholder="Pesquisar mãe por nome ou registro…" style="flex:1" autocomplete="off">
                        <button type="button" id="btnBuscarMae" class="botao botao--contorno botao--pequeno">Pesquisar</button>
                    </div>
                    <div id="resultadoMae" style="margin-bottom:.5rem"></div>
                    <input type="hidden" name="mae_id" id="maeId" value="{{ $v('mae_id') }}">
                    <p id="maeSelecionada" class="suave" style="margin-bottom:.5rem">@if ($animal->mae_id && $animal->maeAnimal) Selecionada: <b>{{ $animal->maeAnimal->nome }}</b> <button type="button" onclick="document.getElementById('maeId').value='';this.parentNode.innerHTML=''" class="botao botao--contorno botao--pequeno">Limpar</button> @endif</p>
                    <label class="campo"><span>Nome da mãe (se não cadastrada)</span><input name="mae" value="{{ $v('mae') }}" maxlength="120"></label>
                </div>
            </div>
        </section>
    </div>

    {{-- Observações --}}
    <div class="painel-aba" id="aba-observacoes">
        <section class="cartao"><h2>Observações</h2>
            <label class="campo"><textarea name="observacoes" rows="5" maxlength="5000">{{ $v('observacoes') }}</textarea></label>
        </section>
    </div>

    <div class="acoes-form"><a href="{{ route('painel.animais') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">{{ $animal->exists ? 'Salvar' : 'Cadastrar animal' }}</button></div>
</form>

<script>
// Abas
document.querySelectorAll('.abas-cadastro .aba').forEach(function(b) {
    b.addEventListener('click', function() {
        document.querySelectorAll('.abas-cadastro .aba').forEach(function(x) { x.classList.remove('ativa'); });
        document.querySelectorAll('.painel-aba').forEach(function(x) { x.classList.remove('ativo'); });
        this.classList.add('ativa');
        document.getElementById('aba-' + this.dataset.aba).classList.add('ativo');
    });
});

// Filtrar categorias por espécie
var selEspecie = document.getElementById('selEspecie');
var selCategoria = document.getElementById('selCategoria');
function filtrarCategorias() {
    var esp = selEspecie.value;
    var opts = selCategoria.querySelectorAll('option[data-especie]');
    opts.forEach(function(o) {
        var especies = o.dataset.especie.split(',');
        o.style.display = especies.indexOf(esp) >= 0 ? '' : 'none';
    });
}
selEspecie.addEventListener('change', filtrarCategorias);
filtrarCategorias();

// Busca genitor
function buscarGenitor(sexo, inputId, resultId, hiddenId, selId) {
    var q = document.getElementById(inputId).value.trim();
    if (q.length < 2) return;
    var div = document.getElementById(resultId);
    div.innerHTML = '<p class="suave">Pesquisando…</p>';
    fetch('{{ route("painel.animais.buscar-genitor") }}?q=' + encodeURIComponent(q) + '&sexo=' + sexo)
        .then(function(r) { return r.json(); })
        .then(function(dados) {
            if (!dados.length) { div.innerHTML = '<p class="suave">Nenhum encontrado. Preencha o nome abaixo.</p>'; return; }
            var html = '<div class="tabela-rolagem"><table class="tabela"><thead><tr><th>Nome</th><th>Raça</th><th>Registro</th><th>Haras / Fazenda</th><th></th></tr></thead><tbody>';
            dados.forEach(function(a) {
                html += '<tr><td><b>' + a.nome + '</b></td><td>' + a.raca + '</td><td>' + a.registro + '</td><td>' + a.haras + '</td>';
                html += '<td><button type="button" class="botao botao--primario botao--pequeno" onclick="selecionarGenitor(\'' + hiddenId + '\',\'' + selId + '\',' + a.id + ',\'' + a.nome.replace(/'/g, "\\'") + '\')">Selecionar</button></td></tr>';
            });
            html += '</tbody></table></div>';
            div.innerHTML = html;
        });
}
function selecionarGenitor(hiddenId, selId, id, nome) {
    document.getElementById(hiddenId).value = id;
    document.getElementById(selId).innerHTML = 'Selecionado: <b>' + nome + '</b> <button type="button" onclick="document.getElementById(\'' + hiddenId + '\').value=\'\';this.parentNode.innerHTML=\'\'" class="botao botao--contorno botao--pequeno">Limpar</button>';
}

document.getElementById('btnBuscarPai').addEventListener('click', function() { buscarGenitor('macho', 'buscaPai', 'resultadoPai', 'paiId', 'paiSelecionado'); });
document.getElementById('btnBuscarMae').addEventListener('click', function() { buscarGenitor('femea', 'buscaMae', 'resultadoMae', 'maeId', 'maeSelecionada'); });
document.getElementById('buscaPai').addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnBuscarPai').click(); } });
document.getElementById('buscaMae').addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnBuscarMae').click(); } });
</script>
@endif
@endsection
