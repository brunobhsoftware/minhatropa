@extends('layouts.painel')
@section('titulo', $animal->nome)
@section('conteudo')
<div class="cabecalho">
    <div>
        <p class="migalha"><a href="{{ route('painel.animais') }}">Meu Plantel</a> ›</p>
        <h1>{{ $animal->nome }} <span class="selo selo--{{ $animal->situacao }}">{{ $animal->situacaoTexto() }}</span></h1>
        <p>{{ $animal->especieTexto() }} · {{ $animal->categoriaTexto() }} · {{ $animal->raca ?: 'Sem raça' }} · {{ $animal->pelagemTexto() }} · {{ $animal->idade() }}</p>
    </div>
    @include('painel.partes.menu-animal', ['animal' => $animal, 'papel' => $papel, 'modo' => 'ficha'])
</div>

<div class="abas-cadastro">
    <button type="button" class="aba-cadastro aba-cadastro--ativa" data-aba="identificacao">Identificação</button>
    <button type="button" class="aba-cadastro" data-aba="registro">Registro</button>
    <button type="button" class="aba-cadastro" data-aba="trilha">Trilha da vida</button>
    <button type="button" class="aba-cadastro" data-aba="aquisicao">Aquisição</button>
    <button type="button" class="aba-cadastro" data-aba="genealogia">Genealogia</button>
    @if (\App\Support\FotosAnimal::disponivel())<button type="button" class="aba-cadastro" data-aba="fotos">Fotos</button>@endif
    @if (\App\Support\Sociedade::disponivel())<button type="button" class="aba-cadastro" data-aba="sociedade">Sociedade</button>@endif
    <button type="button" class="aba-cadastro" data-aba="observacoes">Observações</button>
    @if ($animal->situacao === 'morto' || $animal->situacao === 'ativo')<button type="button" class="aba-cadastro" data-aba="obito">Óbito</button>@endif
</div>

{{-- Identificação --}}
<div class="painel-aba" id="aba-identificacao">
    <section class="cartao">
        <h2>Identificação</h2>
        <dl class="dados">
            <div><dt>Nome</dt><dd style="text-transform:uppercase">{{ $animal->nome }}</dd></div>
            <div><dt>Nome de registro</dt><dd style="text-transform:uppercase">{{ $animal->nome_registro ?: '—' }}</dd></div>
            <div><dt>Apelido</dt><dd style="text-transform:uppercase">{{ $animal->apelido ?: '—' }}</dd></div>
            <div><dt>Espécie</dt><dd>{{ $animal->especieTexto() }}</dd></div>
            <div><dt>Sexo</dt><dd>{{ $animal->sexo === 'macho' ? 'Macho' : ($animal->sexo === 'femea' ? 'Fêmea' : '—') }}</dd></div>
            <div><dt>Categoria</dt><dd>{{ $animal->categoriaTexto() }}</dd></div>
            <div><dt>Raça</dt><dd>{{ $animal->raca ?: '—' }}</dd></div>
            <div><dt>Pelagem</dt><dd>{{ $animal->pelagemTexto() }}</dd></div>
            <div><dt>Nascimento</dt><dd>{{ $animal->data_nascimento ? $animal->data_nascimento->format('d/m/Y') : '—' }}@if ($animal->data_nascimento) ({{ $animal->idade() }})@endif</dd></div>
            <div><dt>Nº plantel</dt><dd>{{ $animal->numero_plantel ?: '—' }}</dd></div>
            <div><dt>Local / baia</dt><dd>{{ $animal->local_baia ?: '—' }}</dd></div>
        </dl>
    </section>
</div>

{{-- Registro --}}
<div class="painel-aba painel-aba--oculto" id="aba-registro">
    <section class="cartao">
        <h2>Registro</h2>
        <dl class="dados">
            <div><dt>Registro</dt><dd>{{ $animal->registro ?: '—' }}</dd></div>
            <div><dt>Associação</dt><dd>{{ isset(\App\Models\Animal::ASSOCIACOES[$animal->registro_associacao]) ? \App\Models\Animal::ASSOCIACOES[$animal->registro_associacao] : '—' }}</dd></div>
            <div><dt>ID na associação</dt><dd>{{ $animal->id_associacao ?: '—' }}</dd></div>
            <div><dt>Status do registro</dt><dd>{{ $animal->registroStatusTexto() }}</dd></div>
            <div><dt>Microchip</dt><dd>{{ $animal->microchip ?: '—' }}</dd></div>
            <div><dt>DNA coletado</dt><dd>{{ $animal->dna_coletado ? 'Sim' : 'Não' }}</dd></div>
            <div><dt>Peso</dt><dd>{{ $animal->peso_kg ? $animal->peso_kg . ' kg' : '—' }}</dd></div>
            <div><dt>Altura</dt><dd>{{ $animal->altura_cm ? $animal->altura_cm . ' cm' : '—' }}</dd></div>
            <div><dt>Castrado</dt><dd>{{ $animal->castrado ? 'Sim' : 'Não' }}</dd></div>
        </dl>
    </section>
</div>

{{-- Trilha da vida --}}
<div class="painel-aba painel-aba--oculto" id="aba-trilha">
    <section class="cartao">
        <h2>Trilha da vida</h2>
        <div class="grade-3">
            <div>
                <h3 style="font-size:.95rem;margin-bottom:.5rem">Local de nascimento</h3>
                <dl class="dados">
                    <div><dt>Local / propriedade</dt><dd style="text-transform:uppercase">{{ $animal->local_nascimento ?: '—' }}</dd></div>
                </dl>
            </div>
            <div>
                <h3 style="font-size:.95rem;margin-bottom:.5rem">Criador</h3>
                <dl class="dados">
                    <div><dt>Nome</dt><dd style="text-transform:uppercase">{{ $animal->criador_nome ?: '—' }}</dd></div>
                    <div><dt>Cidade/UF</dt><dd style="text-transform:uppercase">{{ $animal->criador_cidade ?: '—' }}{{ $animal->criador_uf ? '/'.$animal->criador_uf : '' }}</dd></div>
                </dl>
            </div>
            <div>
                <h3 style="font-size:.95rem;margin-bottom:.5rem">Proprietário atual</h3>
                <dl class="dados">
                    <div><dt>Nome</dt><dd style="text-transform:uppercase">{{ $animal->proprietario_nome ?: '—' }}</dd></div>
                    <div><dt>Cidade/UF</dt><dd style="text-transform:uppercase">{{ $animal->proprietario_cidade ?: '—' }}{{ $animal->proprietario_uf ? '/'.$animal->proprietario_uf : '' }}</dd></div>
                </dl>
            </div>
        </div>
    </section>
</div>

{{-- Aquisição --}}
<div class="painel-aba painel-aba--oculto" id="aba-aquisicao">
    <section class="cartao">
        <h2>Aquisição</h2>
        <dl class="dados">
            <div><dt>Data de aquisição</dt><dd>{{ $animal->data_aquisicao ? $animal->data_aquisicao->format('d/m/Y') : '—' }}</dd></div>
            <div><dt>Forma</dt><dd>{{ \App\Models\Animal::FORMAS_AQUISICAO[$animal->forma_aquisicao] ?? '—' }}</dd></div>
            <div><dt>Valor</dt><dd>{{ $animal->valor_aquisicao ? 'R$ ' . number_format($animal->valor_aquisicao, 2, ',', '.') : '—' }}</dd></div>
            <div><dt>Situação</dt><dd><span class="selo selo--{{ $animal->situacao }}">{{ $animal->situacaoTexto() }}</span></dd></div>
            <div><dt>Data da situação</dt><dd>{{ $animal->situacao_data ? $animal->situacao_data->format('d/m/Y') : '—' }}</dd></div>
            <div><dt>Motivo</dt><dd>{{ $animal->situacao_motivo ?: '—' }}</dd></div>
        </dl>
    </section>
</div>

{{-- Genealogia --}}
<div class="painel-aba painel-aba--oculto" id="aba-genealogia">
    <section class="cartao">
        <h2>Genealogia</h2>
        <dl class="dados">
            <div><dt>Pai</dt><dd>
                @if ($animal->pai_id && $animal->paiAnimal)
                    <a href="{{ route('painel.animais.show', $animal->paiAnimal) }}"><b>{{ $animal->paiAnimal->nome }}</b></a>
                    @if ($animal->paiAnimal->raca) · {{ $animal->paiAnimal->raca }}@endif
                @elseif ($animal->pai_referencia_id && $animal->paiReferencia)
                    <b>{{ $animal->paiReferencia->nome }}</b>
                    @if ($animal->paiReferencia->registro) · Reg. {{ $animal->paiReferencia->registro }}@endif
                    @if ($animal->paiReferencia->registro_associacao) ({{ $animal->paiReferencia->registro_associacao }})@endif
                    <span class="selo selo--amarelo" style="margin-left:4px">Pré-cadastro</span>
                @else
                    {{ $animal->pai ?: '—' }}
                    @if ($animal->pai_registro) <small class="suave">Reg.: {{ $animal->pai_registro }}</small>@endif
                @endif
            </dd></div>
            <div><dt>Mãe</dt><dd>
                @if ($animal->mae_id && $animal->maeAnimal)
                    <a href="{{ route('painel.animais.show', $animal->maeAnimal) }}"><b>{{ $animal->maeAnimal->nome }}</b></a>
                    @if ($animal->maeAnimal->raca) · {{ $animal->maeAnimal->raca }}@endif
                @elseif ($animal->mae_referencia_id && $animal->maeReferencia)
                    <b>{{ $animal->maeReferencia->nome }}</b>
                    @if ($animal->maeReferencia->registro) · Reg. {{ $animal->maeReferencia->registro }}@endif
                    @if ($animal->maeReferencia->registro_associacao) ({{ $animal->maeReferencia->registro_associacao }})@endif
                    <span class="selo selo--amarelo" style="margin-left:4px">Pré-cadastro</span>
                @else
                    {{ $animal->mae ?: '—' }}
                    @if ($animal->mae_registro) <small class="suave">Reg.: {{ $animal->mae_registro }}</small>@endif
                @endif
            </dd></div>
        </dl>
    </section>

    {{-- Árvore genealógica --}}
    @if (($arvore['pai']['animal'] ?? null) || ($arvore['mae']['animal'] ?? null) || ($arvore['pai']['nome'] ?? null) || ($arvore['mae']['nome'] ?? null))
    <section class="cartao" style="margin-top:1rem">
        <h2>Árvore genealógica</h2>
        <div class="arvore-genealogica">
            <div class="arvore-nivel arvore-raiz"><b>{{ $animal->nome }}</b></div>
            <div class="arvore-filhos">
                <div class="arvore-ramo">
                    @php $pai = $arvore['pai']['animal'] ?? null; @endphp
                    <div class="arvore-nivel">
                        @if ($pai)<a href="{{ route('painel.animais.show', $pai) }}">{{ $pai->nome }}</a>@else {{ $arvore['pai']['nome'] ?? '—' }} @endif
                        <small class="suave">Pai</small>
                    </div>
                    @if ($pai)
                    <div class="arvore-filhos">
                        <div class="arvore-ramo">
                            @php $avoPat = $arvore['pai']['pai']['animal'] ?? null; @endphp
                            <div class="arvore-nivel arvore-folha">
                                @if ($avoPat)<a href="{{ route('painel.animais.show', $avoPat) }}">{{ $avoPat->nome }}</a>@else {{ $arvore['pai']['pai']['nome'] ?? '—' }} @endif
                                <small class="suave">Avô pat.</small>
                            </div>
                        </div>
                        <div class="arvore-ramo">
                            @php $avoMat = $arvore['pai']['mae']['animal'] ?? null; @endphp
                            <div class="arvore-nivel arvore-folha">
                                @if ($avoMat)<a href="{{ route('painel.animais.show', $avoMat) }}">{{ $avoMat->nome }}</a>@else {{ $arvore['pai']['mae']['nome'] ?? '—' }} @endif
                                <small class="suave">Avó pat.</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="arvore-ramo">
                    @php $mae = $arvore['mae']['animal'] ?? null; @endphp
                    <div class="arvore-nivel">
                        @if ($mae)<a href="{{ route('painel.animais.show', $mae) }}">{{ $mae->nome }}</a>@else {{ $arvore['mae']['nome'] ?? '—' }} @endif
                        <small class="suave">Mãe</small>
                    </div>
                    @if ($mae)
                    <div class="arvore-filhos">
                        <div class="arvore-ramo">
                            @php $avoPat2 = $arvore['mae']['pai']['animal'] ?? null; @endphp
                            <div class="arvore-nivel arvore-folha">
                                @if ($avoPat2)<a href="{{ route('painel.animais.show', $avoPat2) }}">{{ $avoPat2->nome }}</a>@else {{ $arvore['mae']['pai']['nome'] ?? '—' }} @endif
                                <small class="suave">Avô mat.</small>
                            </div>
                        </div>
                        <div class="arvore-ramo">
                            @php $avoMat2 = $arvore['mae']['mae']['animal'] ?? null; @endphp
                            <div class="arvore-nivel arvore-folha">
                                @if ($avoMat2)<a href="{{ route('painel.animais.show', $avoMat2) }}">{{ $avoMat2->nome }}</a>@else {{ $arvore['mae']['mae']['nome'] ?? '—' }} @endif
                                <small class="suave">Avó mat.</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    @endif
</div>

{{-- Fotos --}}
@if (\App\Support\FotosAnimal::disponivel())
<div class="painel-aba painel-aba--oculto" id="aba-fotos">
    <section class="cartao" id="fotos">
        <div class="cartao__titulo"><h2>Fotos</h2><a href="{{ route('painel.animais.editar', $animal) }}#fotos">{{ $fotos->isEmpty() ? 'Adicionar fotos' : 'Alterar' }}</a></div>
        @if ($fotos->isEmpty())
            <p class="suave">Ainda sem fotos. Dá para enviar até {{ \App\Support\FotosAnimal::MAXIMO }}, em retrato.</p>
        @else
        <div class="fotos-animal fotos-animal--ficha">
            @foreach ($fotos as $foto)
            <a class="foto-animal__quadro" href="{{ route('painel.animais.foto', [$animal, $foto->posicao]) }}?v={{ $foto->id }}-{{ $foto->bytes }}" target="_blank" rel="noopener" data-foto-ficha="{{ $foto->posicao }}">
                <img src="{{ route('painel.animais.foto', [$animal, $foto->posicao]) }}?v={{ $foto->id }}-{{ $foto->bytes }}" alt="Foto {{ $foto->posicao }} de {{ $animal->nome }}" loading="lazy" width="{{ $foto->largura }}" height="{{ $foto->altura }}">
            </a>
            @endforeach
        </div>
        @endif
    </section>
</div>
@endif

{{-- Sociedade --}}
@if (\App\Support\Sociedade::disponivel())
<div class="painel-aba painel-aba--oculto" id="aba-sociedade">
    <section class="cartao" id="sociedade">
        <div class="cartao__titulo"><h2>Sociedade</h2><a href="{{ route('painel.animais.editar', $animal) }}#sociedade">Alterar</a></div>
        @if ($socios->isEmpty())
            <p><b>100% de {{ $haras->nome }}.</b> <span class="suave">Sem sócios.</span></p>
        @else
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr><th>Sócio</th><th>CPF / CNPJ</th><th class="num">Parte</th><th>Cadastro no MinhaTropa</th></tr></thead>
            <tbody>
                <tr><td><b>{{ $haras->nome }}</b> <small class="suave">esta propriedade</small></td><td>{{ $haras->documentoFormatado() }}</td><td class="num"><b>{{ \App\Support\Sociedade::pct($parteDaPropriedade) }}</b></td><td>—</td></tr>
                @foreach ($socios as $s)
                <tr data-socio-doc="{{ $s->documento }}">
                    <td><b>{{ $s->nome_atual }}</b>@if ($s->detalhe)<br><small class="suave">{{ $s->detalhe }}</small>@endif</td>
                    <td>{{ \App\Support\Documento::formatar($s->documento) }}</td>
                    <td class="num"><b>{{ \App\Support\Sociedade::pct($s->percentual) }}</b></td>
                    <td>
                        @if ($s->com_conta)
                            <span class="selo selo--ativo">Cadastrado</span>
                        @else
                            <span class="selo selo--solicitado">{{ $s->cadastrado ? 'Cadastrado, sem acesso' : 'Aguardando cadastro' }}</span>
                            <div class="convite">
                                <input readonly value="{{ $s->convite_url }}" aria-label="Link de convite de {{ $s->nome_atual }}" onfocus="this.select()">
                                <button type="button" class="botao botao--contorno botao--pequeno" data-copiar="{{ $s->convite_url }}">Copiar link</button>
                                <a class="botao botao--contorno botao--pequeno" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode('Olá, '.strtok($s->nome_atual, ' ').'! Você foi lançado(a) como sócio(a) do animal '.$animal->nome.' ('.\App\Support\Sociedade::pct($s->percentual).') em '.$haras->nome.', no MinhaTropa. Faça seu cadastro por este link: '.$s->convite_url) }}">WhatsApp</a>
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table></div>
        <p class="suave" style="margin-top:10px"><small>Envie o link de convite a quem ainda não tem cadastro: é um cadastro simples, e depois dele o sócio vê o animal no painel profissional.</small></p>
        @endif
    </section>
</div>
@endif

@if (\App\Support\Genealogia::disponivel())
@php $refsDoAnimal = \App\Support\Genealogia::referenciasDoAnimal($animal); @endphp
@if ($refsDoAnimal->isNotEmpty())
<div class="painel-aba painel-aba--oculto" id="aba-pre-cadastros">
<section class="cartao">
    <h2>Pré-cadastros vinculados a este animal</h2>
    <p class="suave" style="margin-bottom:.5rem">Outros Haras informaram este animal como genitor antes de ele ser cadastrado. A genealogia foi vinculada automaticamente.</p>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Referenciado como</th><th>Registro informado</th><th>Informado por</th><th>Vinculado em</th></tr></thead>
        <tbody>
        @foreach ($refsDoAnimal as $ref)
            <tr>
                <td>{{ $ref->nome }} <small class="suave">({{ $ref->sexo === 'macho' ? 'pai' : 'mãe' }})</small></td>
                <td>{{ $ref->registro ?? '—' }}@if ($ref->registro_associacao) <small>({{ $ref->registro_associacao }})</small>@endif</td>
                <td>{{ $ref->empresa_nome ?? '—' }}</td>
                <td>{{ $ref->vinculado_em ? data_local($ref->vinculado_em) : '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
</div>
@endif
@endif

{{-- Observações --}}
<div class="painel-aba painel-aba--oculto" id="aba-observacoes">
    <section class="cartao"><h2>Observações</h2><p class="pre">{{ $animal->observacoes ?: '—' }}</p></section>
</div>

{{-- Óbito --}}
@if ($animal->situacao === 'morto' && $animal->morte_data)
<div class="painel-aba painel-aba--oculto" id="aba-obito">
    <section class="cartao">
        <h2>Óbito registrado</h2>
        <dl class="dados">
            <div><dt>Data</dt><dd>{{ $animal->morte_data->format('d/m/Y') }}</dd></div>
            <div><dt>Causa</dt><dd>{{ $animal->morte_causa ?: '—' }}</dd></div>
            <div><dt>Laudo</dt><dd class="pre">{{ $animal->morte_laudo ?: '—' }}</dd></div>
        </dl>
    </section>
</div>
@elseif ($animal->situacao === 'ativo')
<div class="painel-aba painel-aba--oculto" id="aba-obito">
    <section class="cartao">
        <details>
            <summary style="cursor:pointer;font-weight:600;color:var(--vermelho, #b91c1c)">Registrar óbito</summary>
            <form method="post" action="{{ route('painel.animais.morte', $animal) }}" class="formulario" style="margin-top:1rem">
                @csrf @method('put')
                <div class="grade-3">
                    <label class="campo"><span>Data do óbito *</span><input type="date" name="morte_data" required value="{{ old('morte_data') }}"></label>
                    <label class="campo"><span>Causa</span><input name="morte_causa" value="{{ old('morte_causa') }}" maxlength="255"></label>
                </div>
                <label class="campo"><span>Laudo / observações</span><textarea name="morte_laudo" rows="3" maxlength="5000">{{ old('morte_laudo') }}</textarea></label>
                <button class="botao botao--contorno" style="margin-top:.5rem;color:var(--vermelho, #b91c1c);border-color:var(--vermelho, #b91c1c)">Confirmar óbito</button>
            </form>
        </details>
    </section>
</div>
@endif

<script>
document.querySelectorAll('.abas-cadastro').forEach(function(nav){
    nav.querySelectorAll('.aba-cadastro').forEach(function(btn){
        btn.addEventListener('click', function(){
            nav.querySelectorAll('.aba-cadastro').forEach(function(b){b.classList.remove('aba-cadastro--ativa')});
            btn.classList.add('aba-cadastro--ativa');
            var alvo = btn.getAttribute('data-aba');
            document.querySelectorAll('.painel-aba').forEach(function(p){
                p.classList.toggle('painel-aba--oculto', p.id !== 'aba-' + alvo);
            });
        });
    });
});
</script>
@endsection
