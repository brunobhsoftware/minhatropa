@extends('layouts.painel')
@section('titulo', $animal->nome)
@section('conteudo')
<div class="cabecalho">
    <div>
        <p class="migalha"><a href="{{ route('painel.animais') }}">Animais</a> ›</p>
        <h1>{{ $animal->nome }} <span class="selo selo--{{ $animal->situacao }}">{{ $animal->situacaoTexto() }}</span></h1>
        <p>{{ $animal->especieTexto() }} · {{ $animal->categoriaTexto() }} · {{ $animal->raca ?: 'Sem raça' }} · {{ $animal->pelagemTexto() }} · {{ $animal->idade() }}</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('painel.animais.editar', $animal) }}" class="botao botao--primario"><x-icone nome="editar" :tamanho="16" /> Editar</a>
    </div>
</div>

<div class="grade-2">
    <section class="cartao">
        <h2>Identificação</h2>
        <dl class="dados">
            <div><dt>Nome</dt><dd>{{ $animal->nome }}</dd></div>
            @if ($animal->nome_registro)<div><dt>Nome de registro</dt><dd>{{ $animal->nome_registro }}</dd></div>@endif
            @if ($animal->apelido)<div><dt>Apelido</dt><dd>{{ $animal->apelido }}</dd></div>@endif
            <div><dt>Espécie</dt><dd>{{ $animal->especieTexto() }}</dd></div>
            <div><dt>Sexo</dt><dd>{{ $animal->sexo === 'macho' ? 'Macho' : ($animal->sexo === 'femea' ? 'Fêmea' : '—') }}</dd></div>
            <div><dt>Categoria</dt><dd>{{ $animal->categoriaTexto() }}</dd></div>
            <div><dt>Raça</dt><dd>{{ $animal->raca ?: '—' }}</dd></div>
            <div><dt>Pelagem</dt><dd>{{ $animal->pelagemTexto() }}</dd></div>
            <div><dt>Nascimento</dt><dd>{{ $animal->data_nascimento ? $animal->data_nascimento->format('d/m/Y') : '—' }} ({{ $animal->idade() }})</dd></div>
            @if ($animal->numero_plantel)<div><dt>Nº plantel</dt><dd>{{ $animal->numero_plantel }}</dd></div>@endif
            @if ($animal->local_baia)<div><dt>Local / baia</dt><dd>{{ $animal->local_baia }}</dd></div>@endif
        </dl>
    </section>
    <section class="cartao">
        <h2>Registro e saúde</h2>
        <dl class="dados">
            <div><dt>Registro</dt><dd>{{ $animal->registro ?: '—' }}</dd></div>
            @if ($animal->registro_associacao)<div><dt>Associação</dt><dd>{{ \App\Models\Animal::ASSOCIACOES[$animal->registro_associacao] ?? $animal->registro_associacao }}</dd></div>@endif
            <div><dt>Registro definitivo</dt><dd>{{ $animal->registro_definitivo ? 'Sim' : 'Não' }}</dd></div>
            <div><dt>Microchip</dt><dd>{{ $animal->microchip ?: '—' }}</dd></div>
            <div><dt>DNA coletado</dt><dd>{{ $animal->dna_coletado ? 'Sim' : 'Não' }}</dd></div>
            @if ($animal->peso_kg)<div><dt>Peso</dt><dd>{{ $animal->peso_kg }} kg</dd></div>@endif
            @if ($animal->altura_cm)<div><dt>Altura</dt><dd>{{ $animal->altura_cm }} cm</dd></div>@endif
            <div><dt>Castrado</dt><dd>{{ $animal->castrado ? 'Sim' : 'Não' }}</dd></div>
        </dl>
    </section>
</div>

<div class="grade-2">
    <section class="cartao">
        <h2>Aquisição</h2>
        <dl class="dados">
            <div><dt>Data de aquisição</dt><dd>{{ $animal->data_aquisicao ? $animal->data_aquisicao->format('d/m/Y') : '—' }}</dd></div>
            <div><dt>Forma</dt><dd>{{ \App\Models\Animal::FORMAS_AQUISICAO[$animal->forma_aquisicao] ?? '—' }}</dd></div>
            @if ($animal->valor_aquisicao)<div><dt>Valor</dt><dd>R$ {{ number_format($animal->valor_aquisicao, 2, ',', '.') }}</dd></div>@endif
            <div><dt>Situação</dt><dd><span class="selo selo--{{ $animal->situacao }}">{{ $animal->situacaoTexto() }}</span></dd></div>
            @if ($animal->situacao_data)<div><dt>Data da situação</dt><dd>{{ $animal->situacao_data->format('d/m/Y') }}</dd></div>@endif
            @if ($animal->situacao_motivo)<div><dt>Motivo</dt><dd>{{ $animal->situacao_motivo }}</dd></div>@endif
        </dl>
    </section>
    <section class="cartao">
        <h2>Genealogia</h2>
        <dl class="dados">
            <div><dt>Pai</dt><dd>
                @if ($animal->pai_id && $animal->paiAnimal)
                    <a href="{{ route('painel.animais.show', $animal->paiAnimal) }}"><b>{{ $animal->paiAnimal->nome }}</b></a>
                    @if ($animal->paiAnimal->raca) · {{ $animal->paiAnimal->raca }}@endif
                @else
                    {{ $animal->pai ?: '—' }}
                @endif
            </dd></div>
            <div><dt>Mãe</dt><dd>
                @if ($animal->mae_id && $animal->maeAnimal)
                    <a href="{{ route('painel.animais.show', $animal->maeAnimal) }}"><b>{{ $animal->maeAnimal->nome }}</b></a>
                    @if ($animal->maeAnimal->raca) · {{ $animal->maeAnimal->raca }}@endif
                @else
                    {{ $animal->mae ?: '—' }}
                @endif
            </dd></div>
        </dl>
    </section>
</div>

{{-- Árvore genealógica --}}
@if (($arvore['pai']['animal'] ?? null) || ($arvore['mae']['animal'] ?? null) || ($arvore['pai']['nome'] ?? null) || ($arvore['mae']['nome'] ?? null))
<section class="cartao">
    <h2>Árvore genealógica</h2>
    <div class="arvore-genealogica">
        <div class="arvore-nivel arvore-raiz"><b>{{ $animal->nome }}</b></div>
        <div class="arvore-filhos">
            {{-- Pai --}}
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
            {{-- Mãe --}}
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

@if ($animal->observacoes)
<section class="cartao"><h2>Observações</h2><p class="pre">{{ $animal->observacoes }}</p></section>
@endif

{{-- Morte --}}
@if ($animal->situacao === 'morto' && $animal->morte_data)
<section class="cartao">
    <h2>Óbito registrado</h2>
    <dl class="dados">
        <div><dt>Data</dt><dd>{{ $animal->morte_data->format('d/m/Y') }}</dd></div>
        @if ($animal->morte_causa)<div><dt>Causa</dt><dd>{{ $animal->morte_causa }}</dd></div>@endif
        @if ($animal->morte_laudo)<div><dt>Laudo</dt><dd class="pre">{{ $animal->morte_laudo }}</dd></div>@endif
    </dl>
</section>
@elseif ($animal->situacao === 'ativo')
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
@endif
@endsection
