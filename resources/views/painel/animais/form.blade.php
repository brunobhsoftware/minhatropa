@extends('layouts.painel')
@section('titulo', $animal->exists ? $animal->nome : 'Novo animal')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.animais') }}">Meu Plantel</a> ›</p><h1>{{ $animal->exists ? $animal->nome : 'Novo animal' }}</h1>@if ($animal->exists && $animal->data_nascimento)<p class="suave">Idade: <b>{{ $animal->idade() }}</b></p>@endif</div></div>
@php
    $v = fn ($c) => old($c, $animal->{$c});
    // Raça: lista pronta. As raças criadas pela propriedade (cadastro do Haras / Fazenda) vêm primeiro; "Outra" libera a digitação.
    $racasHaras = $haras->racas();
    $racasTodas = \App\Models\Empresa::RACAS;
    // Animal novo em propriedade que cria uma única raça já vem com ela escolhida.
    $racaAtual = (string) old('raca', $animal->exists ? $animal->raca : (count($racasHaras) === 1 ? $racasHaras[0] : ''));
    $racaDaLista = $racaAtual === '' || in_array($racaAtual, $racasTodas, true);
@endphp
<form method="post" action="{{ $animal->exists ? route('painel.animais.salvar', $animal) : route('painel.animais.criar') }}" class="formulario" enctype="multipart/form-data">
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
    @if (! $animal->exists)
    <section class="cartao" style="margin-bottom:1.5rem">
        <h2>Cadastro rápido</h2>
        <p class="suave" style="margin-bottom:.5rem">Cole os dados do animal (da ficha da associação) no campo abaixo. O sistema lê e preenche os campos automaticamente.</p>
        <div style="display:flex;gap:8px;align-items:flex-start">
            <textarea id="txtCadastroRapido" rows="4" style="flex:1;font-size:13px" placeholder="Cole aqui a string completa dos dados do animal (formato ABCCMM ou similar)…"></textarea>
            <button type="button" class="botao botao--primario" id="btnCadastroRapido">Ler e preencher</button>
        </div>
        <small id="avisoCadastroRapido" class="suave" aria-live="polite"></small>
    </section>
    @endif

    <div class="abas-cadastro">
        <button type="button" class="aba-cadastro aba-cadastro--ativa" data-aba="identificacao">Identificação</button>
        <button type="button" class="aba-cadastro" data-aba="registro">Registro</button>
        <button type="button" class="aba-cadastro" data-aba="trilha">Trilha da vida</button>
        <button type="button" class="aba-cadastro" data-aba="aquisicao">Aquisição</button>
        <button type="button" class="aba-cadastro" data-aba="genealogia">Genealogia</button>
        @if (\App\Support\Sociedade::disponivel())<button type="button" class="aba-cadastro" data-aba="sociedade" id="botaoSociedade" @if (old('sociedade', $socios->isNotEmpty() ? 'socios' : 'propria') !== 'socios') hidden @endif>Sociedade</button>@endif
        @if (\App\Support\FotosAnimal::disponivel())<button type="button" class="aba-cadastro" data-aba="fotos">Fotos</button>@endif
        <button type="button" class="aba-cadastro" data-aba="observacoes">Observações</button>
    </div>

    {{-- Identificação --}}
    <div class="painel-aba" id="aba-identificacao">
        <section class="cartao"><h2>Identificação do animal</h2>
            <div class="grade-3">
                <label class="campo"><span>Nome *</span><input name="nome" id="campoNome" value="{{ $v('nome') }}" required maxlength="120" autocomplete="off"></label>
                <label class="campo"><span>Nome de registro</span><input name="nome_registro" id="campoRegistro" value="{{ $v('nome_registro') }}" maxlength="150" placeholder="Nome oficial na associação"><small>Vem igual ao nome; altere se for diferente.</small></label>
                <label class="campo"><span>Apelido / nome de baia</span><input name="apelido" id="campoApelido" value="{{ $v('apelido') }}" maxlength="80"><small>Vem com o primeiro nome; altere se quiser.</small></label>
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
                <div class="campo"><span>Raça</span>
                    <select id="selRaca" aria-label="Raça">
                        <option value=""></option>
                        @if ($racasHaras)
                        <optgroup label="Raças {{ $haras->doRotulo() }}">
                            @foreach ($racasHaras as $r)<option value="{{ $r }}" @selected($racaAtual === $r)>{{ $r }}</option>@endforeach
                        </optgroup>
                        @endif
                        <optgroup label="{{ $racasHaras ? 'Outras raças' : 'Raças' }}">
                            @foreach (array_diff($racasTodas, $racasHaras) as $r)<option value="{{ $r }}" @selected($racaAtual === $r)>{{ $r }}</option>@endforeach
                        </optgroup>
                        <option value="__outra" @selected(! $racaDaLista)>Outra (digitar)</option>
                    </select>
                    <input name="raca" id="campoRaca" value="{{ $racaAtual }}" maxlength="80" placeholder="Digite a raça" aria-label="Outra raça" style="margin-top:6px" @if ($racaDaLista) hidden @endif>
                </div>
                <label class="campo"><span>Pelagem</span>
                    <select name="pelagem" id="selPelagem"><option value=""></option>
                        @foreach (\App\Models\Animal::PELAGENS as $k => $t)
                        <option value="{{ $k }}" @selected($v('pelagem') === $k)>{{ $t }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="campo"><span id="rotuloPelagemComp">Complemento da pelagem</span><input name="pelagem_complemento" id="campoPelagemComp" value="{{ $v('pelagem_complemento') }}" maxlength="80" placeholder="Variação ou detalhe"></label>
                <div class="campo"><span>Data de nascimento</span>
                    <input type="date" name="data_nascimento" id="campoNascimento" value="{{ old('data_nascimento', $animal->data_nascimento?->format('Y-m-d')) }}">
                    <small class="suave" id="idadeCalculada">@if ($animal->data_nascimento) Idade: {{ $animal->idade() }} @endif</small>
                </div>
                <div class="campo"><span>Nº plantel</span>
                    <div class="campo-com-botao">
                        <input name="numero_plantel" id="campoNumeroPlantel" value="{{ $v('numero_plantel') }}" maxlength="30" placeholder="Digite ou busque o próximo" aria-label="Nº plantel">
                        <button type="button" class="botao botao--contorno botao--pequeno" id="btnProximoNumero" data-url="{{ route('painel.animais.proximo-numero') }}" title="Buscar o próximo número da sequência">Próximo nº</button>
                    </div>
                    <small class="suave" id="avisoNumeroPlantel" aria-live="polite"></small>
                </div>
                <label class="campo"><span>Local / baia</span><input name="local_baia" value="{{ $v('local_baia') }}" maxlength="80"></label>
            </div>
        </section>
    </div>

    {{-- Registro e saúde --}}
    <div class="painel-aba painel-aba--oculto" id="aba-registro">
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
                <label class="campo"><span>ID do animal na associação</span><input name="id_associacao" value="{{ $v('id_associacao') }}" maxlength="30" placeholder="Ex.: 1083311"></label>
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

    {{-- Trilha da vida: onde nasceu, quem criou, quem é dono --}}
    <div class="painel-aba painel-aba--oculto" id="aba-trilha">
        <section class="cartao"><h2>Trilha da vida</h2>
            <p class="suave" style="margin-bottom:1rem">Histórico de onde o animal nasceu, quem criou e quem é o proprietário atual. Esses dados vêm automaticamente no cadastro rápido.</p>
            <h3>Local de nascimento</h3>
            <div class="grade-3">
                <label class="campo campo--duplo"><span>Local / propriedade de nascimento</span><input name="local_nascimento" value="{{ $v('local_nascimento') }}" maxlength="200" placeholder="Ex.: Fazenda Santa Lúcia"></label>
            </div>
            <h3 style="margin-top:1.5rem">Criador</h3>
            <div class="grade-3">
                <label class="campo"><span>Nome do criador</span><input name="criador_nome" value="{{ $v('criador_nome') }}" maxlength="150"></label>
                <label class="campo"><span>Cidade</span><input name="criador_cidade" value="{{ $v('criador_cidade') }}" maxlength="100"></label>
                <label class="campo"><span>UF</span><input name="criador_uf" value="{{ $v('criador_uf') }}" maxlength="2" style="max-width:80px"></label>
            </div>
            <h3 style="margin-top:1.5rem">Proprietário atual</h3>
            <div class="grade-3">
                <label class="campo"><span>Nome do proprietário</span><input name="proprietario_nome" value="{{ $v('proprietario_nome') }}" maxlength="150"></label>
                <label class="campo"><span>Cidade</span><input name="proprietario_cidade" value="{{ $v('proprietario_cidade') }}" maxlength="100"></label>
                <label class="campo"><span>UF</span><input name="proprietario_uf" value="{{ $v('proprietario_uf') }}" maxlength="2" style="max-width:80px"></label>
            </div>
        </section>
    </div>

    {{-- Aquisição --}}
    <div class="painel-aba painel-aba--oculto" id="aba-aquisicao">
        <section class="cartao"><h2>Aquisição e situação</h2>
            <div class="grade-3">
                @if (\App\Support\Sociedade::disponivel())
                @php $comSocios = old('sociedade', $socios->isNotEmpty() ? 'socios' : 'propria') === 'socios'; @endphp
                <label class="campo"><span>Percentual {{ $haras->doRotulo() }}</span>
                    <select name="sociedade" id="selSociedade">
                        <option value="propria" @selected(! $comSocios)>100%</option>
                        <option value="socios" @selected($comSocios)>Personalizado</option>
                    </select>
                    <small class="suave" id="atalhoSociedade" @if (! $comSocios) hidden @endif>O animal tem sócios. <a href="#sociedade" id="linkSociedade">Detalhar na seção Sociedade</a></small>
                </label>
                @endif
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
    <div class="painel-aba painel-aba--oculto" id="aba-genealogia">
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
                    <label class="campo"><span>Nome do pai (se não cadastrado)</span><input name="pai" id="campoPaiNome" value="{{ $v('pai') }}" maxlength="120"></label>
                    <label class="campo" style="margin-top:.5rem"><span>Registro do pai</span><input name="pai_registro" value="{{ $v('pai_registro') }}" maxlength="60"></label>
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
                    <label class="campo"><span>Nome da mãe (se não cadastrada)</span><input name="mae" id="campoPaiMae" value="{{ $v('mae') }}" maxlength="120"></label>
                    <label class="campo" style="margin-top:.5rem"><span>Registro da mãe</span><input name="mae_registro" value="{{ $v('mae_registro') }}" maxlength="60"></label>
                </div>
            </div>
        </section>
    </div>

    {{-- Sociedade: 100% da propriedade ou com sócios (CPF = pessoa; CNPJ = outro Haras / Fazenda) --}}
    @if (\App\Support\Sociedade::disponivel())
    @php
        $linhasSocios = old('socios') !== null
            ? collect(old('socios'))->map(fn ($l) => ['documento' => $l['documento'] ?? '', 'nome' => $l['nome'] ?? '', 'percentual' => $l['percentual'] ?? ''])->values()
            : $socios->map(fn ($s) => ['documento' => \App\Support\Documento::formatar($s->documento), 'nome' => $s->nome_atual, 'percentual' => rtrim(rtrim(number_format((float) $s->percentual, 2, ',', ''), '0'), ',')])->values();
        $comSocios = old('sociedade', $socios->isNotEmpty() ? 'socios' : 'propria') === 'socios';
    @endphp
    <div class="painel-aba painel-aba--oculto" id="aba-sociedade">
        <section class="cartao"><h2>Sociedade</h2>
            <p class="suave" style="margin-bottom:1rem">Informe os sócios do animal e a parte de cada um. Sócio pessoa entra pelo CPF; sócio Haras / Fazenda, pelo CNPJ. Para voltar a 100% {{ $haras->doRotulo() }}, mude o percentual na seção Aquisição.</p>
            <div id="blocoSocios" data-busca="{{ route('painel.animais.buscar-socio') }}">
                <div id="listaSocios" class="socios">
                    @foreach ($linhasSocios->isEmpty() ? [['documento' => '', 'nome' => '', 'percentual' => '']] : $linhasSocios as $i => $l)
                    <div class="socio" data-socio>
                        <label class="campo"><span>CPF ou CNPJ do sócio</span><input name="socios[{{ $i }}][documento]" value="{{ $l['documento'] }}" data-socio-doc inputmode="numeric" maxlength="18" autocomplete="off" placeholder="Digite e o sistema procura"></label>
                        <label class="campo"><span>Nome</span><input name="socios[{{ $i }}][nome]" value="{{ $l['nome'] }}" data-socio-nome maxlength="150" placeholder="Aparece ao achar o cadastro"></label>
                        <label class="campo socio__pct"><span>Parte (%)</span><input name="socios[{{ $i }}][percentual]" value="{{ $l['percentual'] }}" data-socio-pct inputmode="decimal" maxlength="6" placeholder="0"></label>
                        <button type="button" class="botao botao--contorno botao--pequeno socio__tirar" data-socio-tirar aria-label="Tirar este sócio">Tirar</button>
                        <small class="socio__aviso" data-socio-aviso></small>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="botao botao--contorno botao--pequeno" id="btnAddSocio"><x-icone nome="mais" :tamanho="16" /> Adicionar sócio</button>
                <div class="socios-resumo" id="resumoSocios" aria-live="polite">
                    <span>Sócios: <b data-total-socios>0%</b></span>
                    <span>{{ $haras->nome }}: <b data-total-proprio>100%</b></span>
                </div>
                <p class="suave"><small>A parte {{ $haras->doRotulo() }} é o que sobra dos sócios. Sócio sem cadastro no MinhaTropa recebe um link de convite, que aparece na ficha do animal depois de salvar.</small></p>
            </div>
        </section>
    </div>
    @endif

    {{-- Fotos: até 3, sempre em retrato; o navegador recorta e compacta antes de enviar --}}
    @if (\App\Support\FotosAnimal::disponivel())
    <div class="painel-aba painel-aba--oculto" id="aba-fotos">
        <section class="cartao"><h2>Fotos</h2>
            <p class="suave" style="margin-bottom:1rem">Até {{ \App\Support\FotosAnimal::MAXIMO }} fotos por animal, sempre em retrato (em pé). Pode enviar a foto do celular do jeito que estiver: o sistema recorta o centro e compacta sozinho. A primeira é a foto principal.</p>
            <div class="fotos-animal" id="fotosAnimal" data-largura="{{ \App\Support\FotosAnimal::LARGURA }}" data-altura="{{ \App\Support\FotosAnimal::ALTURA }}">
                @for ($n = 1; $n <= \App\Support\FotosAnimal::MAXIMO; $n++)
                @php $foto = $fotos->get($n); @endphp
                <div class="foto-animal" data-foto="{{ $n }}">
                    <div class="foto-animal__quadro">
                        <img data-foto-previa alt="Foto {{ $n }} de {{ $animal->nome ?: 'animal' }}" @if ($foto) src="{{ route('painel.animais.foto', [$animal, $n]) }}?v={{ $foto->id }}-{{ $foto->bytes }}" @else hidden @endif>
                        <span data-foto-vazio @if ($foto) hidden @endif>{{ $n === 1 ? 'Foto principal' : 'Foto '.$n }}</span>
                    </div>
                    <label class="botao botao--contorno botao--pequeno foto-animal__escolher">{{ $foto ? 'Trocar foto' : 'Escolher foto' }}
                        <input type="file" name="fotos[{{ $n }}]" accept="image/jpeg,image/png,image/webp" data-foto-arquivo hidden>
                    </label>
                    @if ($foto)<label class="foto-animal__remover"><input type="checkbox" name="remover_foto[{{ $n }}]" value="1" data-foto-remover> Remover</label>@endif
                    <small class="suave" data-foto-aviso aria-live="polite"></small>
                    @error('fotos.'.$n)<small style="color:#b42318">{{ $message }}</small>@enderror
                </div>
                @endfor
            </div>
        </section>
    </div>
    @endif

    {{-- Observações --}}
    <div class="painel-aba painel-aba--oculto" id="aba-observacoes">
        <section class="cartao"><h2>Observações</h2>
            <label class="campo"><textarea name="observacoes" rows="5" maxlength="5000">{{ $v('observacoes') }}</textarea></label>
        </section>
    </div>

    <div class="acoes-form"><a href="{{ route('painel.animais') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">{{ $animal->exists ? 'Salvar' : 'Cadastrar animal' }}</button></div>
</form>

<script src="{{ asset('js/fotos-animal.js') }}?v=1"></script>
<script>
// Nº plantel: o botão busca o próximo número da sequência da propriedade; digitar à mão continua valendo.
(function () {
    var botao = document.getElementById('btnProximoNumero'), campo = document.getElementById('campoNumeroPlantel'), aviso = document.getElementById('avisoNumeroPlantel');
    if (!botao || !campo) return;
    botao.addEventListener('click', function () {
        botao.disabled = true; aviso.textContent = 'Buscando…';
        fetch(botao.dataset.url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
            .then(function (d) { campo.value = d.numero; aviso.textContent = d.ultimo ? 'Último usado: ' + d.ultimo + '.' : 'Primeiro número da sequência.'; })
            .catch(function () { aviso.textContent = 'Não foi possível buscar agora. Digite o número.'; })
            .finally(function () { botao.disabled = false; });
    });
})();

// Seções do cadastro: uma por vez. Com erro de validação, abre a seção do primeiro campo com problema.
(function () {
    var botoes = document.querySelectorAll('.abas-cadastro .aba-cadastro'), paineis = document.querySelectorAll('.painel-aba');
    var abrir = function (nome) {
        botoes.forEach(function (b) { b.classList.toggle('aba-cadastro--ativa', b.dataset.aba === nome); });
        paineis.forEach(function (p) { p.classList.toggle('painel-aba--oculto', p.id !== 'aba-' + nome); });
    };
    botoes.forEach(function (b) { b.addEventListener('click', function () { abrir(b.dataset.aba); }); });
    var erros = @json($errors->keys());
    if (!erros.length && location.hash && document.getElementById('aba-' + location.hash.slice(1))) { var alvo = location.hash.slice(1), bs = document.getElementById('botaoSociedade'); abrir(alvo === 'sociedade' && bs && bs.hidden ? 'aquisicao' : alvo); }
    if (erros.some(function (n) { return n === 'sociedade' || n.indexOf('socios.') === 0; })) { abrir('sociedade'); return; }
    if (erros.some(function (n) { return n.indexOf('fotos') === 0; }) && document.getElementById('aba-fotos')) { abrir('fotos'); return; }
    erros.some(function (nome) { var c = document.querySelector('.formulario [name="' + nome + '"]'); var p = c && c.closest('.painel-aba'); if (p) { abrir(p.id.replace('aba-', '')); return true; } return false; });
})();

// Sociedade: linhas de sócios com busca pelo CPF/CNPJ e soma dos percentuais
(function () {
    var bloco = document.getElementById('blocoSocios'); if (!bloco) return;
    var lista = document.getElementById('listaSocios');
    var digitos = function (v) { return v.replace(/\D/g, ''); };
    var mascara = function (v) {
        v = digitos(v).slice(0, 14);
        if (v.length > 11) return v.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
        return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    };
    var numero = function (v) { var n = parseFloat(String(v).replace(',', '.')); return isNaN(n) ? 0 : n; };
    var texto = function (n) { return (Math.round(n * 100) / 100).toString().replace('.', ',') + '%'; };
    var somar = function () {
        var total = 0; lista.querySelectorAll('[data-socio-pct]').forEach(function (i) { total += numero(i.value); });
        bloco.querySelector('[data-total-socios]').textContent = texto(total);
        bloco.querySelector('[data-total-proprio]').textContent = texto(Math.max(0, 100 - total));
        document.getElementById('resumoSocios').classList.toggle('socios-resumo--erro', total > 100.001);
    };
    var buscar = function (linha) {
        var doc = linha.querySelector('[data-socio-doc]'), nome = linha.querySelector('[data-socio-nome]'), aviso = linha.querySelector('[data-socio-aviso]');
        var d = digitos(doc.value);
        if (d.length !== 11 && d.length !== 14) { aviso.textContent = ''; aviso.className = 'socio__aviso'; nome.readOnly = false; return; }
        if (linha.dataset.buscado === d) return;
        aviso.textContent = 'Procurando…'; aviso.className = 'socio__aviso';
        fetch(bloco.dataset.busca + '?documento=' + d, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (r) {
            if (digitos(doc.value) !== d) return;
            linha.dataset.buscado = d;
            if (r.situacao === 'encontrado') {
                nome.value = r.nome; nome.readOnly = true;
                aviso.textContent = '✓ ' + (r.tipo === 'propriedade' ? 'Haras / Fazenda' : 'Profissional') + ' com cadastro no MinhaTropa' + (r.detalhe ? ' · ' + r.detalhe : '');
                aviso.className = 'socio__aviso socio__aviso--ok';
            } else if (r.situacao === 'novo') {
                nome.readOnly = false;
                aviso.textContent = (r.tipo === 'propriedade' ? 'CNPJ' : 'CPF') + ' ainda sem cadastro: informe o nome. Depois de salvar, a ficha do animal traz o link de convite para enviar.';
                aviso.className = 'socio__aviso socio__aviso--novo';
                if (!nome.value) nome.focus();
            } else {
                nome.readOnly = false; aviso.textContent = r.mensagem || 'Documento inválido.'; aviso.className = 'socio__aviso socio__aviso--erro';
            }
        }).catch(function () { aviso.textContent = 'Não foi possível procurar agora.'; aviso.className = 'socio__aviso socio__aviso--erro'; });
    };
    var ligar = function (linha) {
        var doc = linha.querySelector('[data-socio-doc]');
        doc.addEventListener('input', function () { doc.value = mascara(doc.value); linha.dataset.buscado = ''; if (digitos(doc.value).length === 14) buscar(linha); });
        doc.addEventListener('blur', function () { buscar(linha); });
        linha.querySelector('[data-socio-pct]').addEventListener('input', somar);
        linha.querySelector('[data-socio-tirar]').addEventListener('click', function () {
            if (lista.querySelectorAll('[data-socio]').length > 1) { linha.remove(); } else { linha.querySelectorAll('input').forEach(function (i) { i.value = ''; i.readOnly = false; }); linha.querySelector('[data-socio-aviso]').textContent = ''; linha.dataset.buscado = ''; }
            somar();
        });
        if (digitos(doc.value).length >= 11) buscar(linha);
    };
    lista.querySelectorAll('[data-socio]').forEach(ligar);
    document.getElementById('btnAddSocio').addEventListener('click', function () {
        var modelo = lista.querySelector('[data-socio]'), nova = modelo.cloneNode(true), n = Date.now();
        nova.dataset.buscado = '';
        nova.querySelectorAll('input').forEach(function (i) { i.value = ''; i.readOnly = false; i.name = i.name.replace(/socios\[[^\]]+\]/, 'socios[' + n + ']'); });
        nova.querySelector('[data-socio-aviso]').textContent = ''; nova.querySelector('[data-socio-aviso]').className = 'socio__aviso';
        lista.appendChild(nova); ligar(nova); nova.querySelector('[data-socio-doc]').focus();
    });
    // Percentual (seção Aquisição): 100% esconde a seção Sociedade; Personalizado libera para lançar os sócios.
    var sel = document.getElementById('selSociedade'), botao = document.getElementById('botaoSociedade'), atalho = document.getElementById('atalhoSociedade');
    if (sel && botao) {
        var ajustar = function () { var com = sel.value === 'socios'; botao.hidden = !com; if (atalho) atalho.hidden = !com; };
        sel.addEventListener('change', ajustar); ajustar();
        document.getElementById('linkSociedade').addEventListener('click', function (e) { e.preventDefault(); botao.click(); });
    }
    somar();
})();

// Raça: lista pronta; "Outra (digitar)" mostra o campo de texto. O valor enviado é sempre o do campo "raca".
(function () {
    var sel = document.getElementById('selRaca'), campo = document.getElementById('campoRaca');
    if (!sel) return;
    sel.addEventListener('change', function () {
        var outra = sel.value === '__outra';
        campo.hidden = !outra;
        campo.value = outra ? '' : sel.value;
        if (outra) campo.focus();
    });
})();

// Pelagem "Outra": o campo ao lado passa a pedir qual é
(function () {
    var sel = document.getElementById('selPelagem'), rot = document.getElementById('rotuloPelagemComp'), campo = document.getElementById('campoPelagemComp');
    if (!sel) return;
    var ajustar = function (focar) {
        var outra = sel.value === 'outra';
        rot.textContent = outra ? 'Qual pelagem?' : 'Complemento da pelagem';
        campo.placeholder = outra ? 'Digite a pelagem' : 'Variação ou detalhe';
        if (outra && focar) campo.focus();
    };
    sel.addEventListener('change', function () { ajustar(true); });
    ajustar(false);
})();

// Nome: o nome de registro acompanha o nome e o apelido recebe o primeiro nome, enquanto a pessoa não mexer neles
(function () {
    var nome = document.getElementById('campoNome'), reg = document.getElementById('campoRegistro'), ape = document.getElementById('campoApelido');
    if (!nome || !reg || !ape) return;
    var primeiro = function (t) { return t.trim().split(/\s+/)[0] || ''; };
    // Só preenche sozinho o campo que está vazio ou que ainda repete o nome (não mexe no que foi digitado à mão)
    var autoReg = reg.value === '' || reg.value === nome.value.trim();
    var autoApe = ape.value === '' || ape.value === primeiro(nome.value);
    reg.addEventListener('input', function () { autoReg = false; });
    ape.addEventListener('input', function () { autoApe = false; });
    var copiar = function () {
        if (autoReg) reg.value = nome.value.trim();
        if (autoApe) ape.value = primeiro(nome.value);
    };
    nome.addEventListener('input', copiar);
    nome.addEventListener('change', copiar);
})();

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

// Cálculo de idade em tempo real
(function () {
    var campo = document.getElementById('campoNascimento'), label = document.getElementById('idadeCalculada');
    if (!campo || !label) return;
    function calcular() {
        if (!campo.value) { label.textContent = ''; return; }
        var d = new Date(campo.value + 'T00:00:00'), hoje = new Date(), diff = hoje - d;
        if (isNaN(d.getTime()) || diff < 0) { label.textContent = ''; return; }
        var anos = hoje.getFullYear() - d.getFullYear(), meses = hoje.getMonth() - d.getMonth(), dias = hoje.getDate() - d.getDate();
        if (dias < 0) { meses--; }
        if (meses < 0) { anos--; meses += 12; }
        var partes = [];
        if (anos > 0) partes.push(anos + ' ano' + (anos > 1 ? 's' : ''));
        if (meses > 0) partes.push(meses + (meses === 1 ? ' mês' : ' meses'));
        if (anos === 0 && dias > 0) partes.push(dias + ' dia' + (dias > 1 ? 's' : ''));
        label.textContent = partes.length ? 'Idade: ' + partes.join(' e ') : 'Recém-nascido';
    }
    campo.addEventListener('change', calcular);
    campo.addEventListener('input', calcular);
})();

// Cadastro rápido: cola a string e preenche os campos
(function () {
    var btn = document.getElementById('btnCadastroRapido'), txt = document.getElementById('txtCadastroRapido'), aviso = document.getElementById('avisoCadastroRapido');
    if (!btn || !txt) return;
    btn.addEventListener('click', function () {
        var texto = txt.value.trim();
        if (!texto) { aviso.textContent = 'Cole os dados do animal no campo.'; return; }
        aviso.textContent = 'Lendo dados…'; btn.disabled = true;
        fetch('{{ route("painel.animais.cadastro-rapido") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('[name=_token]').value },
            body: JSON.stringify({ texto: texto })
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            var a = d.animal || {}, pai = d.pai || {}, mae = d.mae || {}, cr = d.criador || {}, pr = d.proprietario || {};
            var set = function (name, val) { if (!val) return; var el = document.querySelector('[name="' + name + '"]'); if (el && !el.value) el.value = val; };
            // Animal
            if (a.sexo) { var sel = document.querySelector('[name=sexo]'); if (sel) sel.value = a.sexo; }
            if (a.data_nascimento) { var dc = document.getElementById('campoNascimento'); if (dc) { dc.value = a.data_nascimento; dc.dispatchEvent(new Event('change')); } }
            set('id_associacao', a.id_associacao);
            if (a.pelagem) { var sp = document.getElementById('selPelagem'); if (sp) { sp.value = a.pelagem; sp.dispatchEvent(new Event('change')); } }
            // Pai
            set('pai', pai.nome);
            set('pai_registro', pai.registro);
            // Mãe
            set('mae', mae.nome);
            set('mae_registro', mae.registro);
            // Trilha da vida (criador e proprietário)
            set('criador_nome', cr.nome);
            set('criador_cidade', cr.cidade);
            set('criador_uf', cr.uf);
            if (cr.propriedade) set('local_nascimento', cr.propriedade);
            set('proprietario_nome', pr.nome);
            set('proprietario_cidade', pr.cidade);
            set('proprietario_uf', pr.uf);
            // Idade
            var idadeTexto = a.idade_texto || '';
            aviso.textContent = 'Dados preenchidos!' + (idadeTexto ? ' Idade informada: ' + idadeTexto : '') + ' Confira e ajuste o que precisar.';
            aviso.style.color = '#067647';
        })
        .catch(function () { aviso.textContent = 'Não foi possível interpretar os dados. Confira o formato.'; aviso.style.color = '#b42318'; })
        .finally(function () { btn.disabled = false; });
    });
})();
</script>
@endif
@endsection
