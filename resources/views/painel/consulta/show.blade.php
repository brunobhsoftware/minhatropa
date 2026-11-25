@extends('layouts.painel')
@section('titulo', $animal->nome . ' — Ficha')
@section('conteudo')
<div class="cabecalho">
    <div>
        <p class="migalha"><a href="{{ route('painel.consulta') }}">Consulta de animais</a> ›</p>
        <h1>{{ $animal->nome }} <span class="selo selo--{{ $animal->situacao }}">{{ $animal->situacaoTexto() }}</span></h1>
        <p>{{ $animal->especieTexto() }} · {{ $animal->categoriaTexto() }} · {{ $animal->raca ?: 'Sem raça' }} · {{ $animal->pelagemTexto() }} · {{ $animal->idade() }}
            @if (! $proprio) · <b>{{ $animal->empresa->nome ?? 'Propriedade desconhecida' }}</b>@endif
        </p>
    </div>
    <div style="display:flex;gap:8px">
        @if ($proprio)
            <a href="{{ route('painel.animais.editar', $animal) }}" class="botao botao--primario"><x-icone nome="editar" :tamanho="16" /> Editar</a>
            <a href="{{ route('painel.animais.show', $animal) }}" class="botao botao--contorno">Ficha do plantel</a>
        @else
            <span class="selo selo--cinza" style="padding:8px 14px">Somente leitura</span>
        @endif
    </div>
</div>

{{-- Abas --}}
@php
    $abas = [
        'dados' => 'Dados',
        'cobricoes' => 'Cobrições',
        'nascimentos' => 'Nascimentos',
        'transferencias' => 'Transferências',
        'baixas' => 'Baixas',
        'filhos' => 'Filhos',
        'genealogia' => 'Genealogia',
        'exames' => 'Exames',
        'resenhas' => 'Resenhas',
        'premiacoes' => 'Premiações',
        'alertas' => 'Alertas',
        'semen' => 'Sêmen congelados',
        'situacao' => 'Situação',
    ];
    $emBreve = ['exames', 'resenhas', 'premiacoes', 'alertas', 'semen'];
    $contadores = [
        'cobricoes' => $cobricoes->count(),
        'nascimentos' => $nascimentos->count(),
        'transferencias' => $transferencias->count(),
        'filhos' => $filhos->count(),
    ];
@endphp

<div class="abas-nav" role="tablist">
    @foreach ($abas as $chave => $rotulo)
        <a href="{{ route('painel.consulta.show', $animal) }}?aba={{ $chave }}"
           role="tab" class="abas-nav__item @if($aba === $chave) abas-nav__item--ativa @endif"
           @if(in_array($chave, $emBreve)) data-breve="1" @endif>
            {{ $rotulo }}
            @if (isset($contadores[$chave]) && $contadores[$chave] > 0)
                <span class="abas-nav__contagem">{{ $contadores[$chave] }}</span>
            @endif
            @if (in_array($chave, $emBreve))
                <span class="abas-nav__breve">breve</span>
            @endif
        </a>
    @endforeach
</div>

<div class="abas-conteudo">
{{-- ==================== DADOS ==================== --}}
@if ($aba === 'dados')
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
                @if ($animal->local_baia && $proprio)<div><dt>Local / baia</dt><dd>{{ $animal->local_baia }}</dd></div>@endif
                <div><dt>Propriedade</dt><dd>{{ $animal->empresa->nome ?? '—' }}</dd></div>
            </dl>
        </section>
        <section class="cartao">
            <h2>Registro e saúde</h2>
            <dl class="dados">
                <div><dt>Registro</dt><dd>{{ $animal->registro ?: '—' }}</dd></div>
                @if ($animal->registro_associacao)<div><dt>Associação</dt><dd>{{ \App\Models\Animal::ASSOCIACOES[$animal->registro_associacao] ?? $animal->registro_associacao }}</dd></div>@endif
                @if ($animal->id_associacao)<div><dt>ID na associação</dt><dd>{{ $animal->id_associacao }}</dd></div>@endif
                <div><dt>Registro definitivo</dt><dd>{{ $animal->registro_definitivo ? 'Sim' : 'Não' }}</dd></div>
                <div><dt>Microchip</dt><dd>{{ $animal->microchip ?: '—' }}</dd></div>
                <div><dt>DNA coletado</dt><dd>{{ $animal->dna_coletado ? 'Sim' : 'Não' }}</dd></div>
                @if ($animal->peso_kg)<div><dt>Peso</dt><dd>{{ $animal->peso_kg }} kg</dd></div>@endif
                @if ($animal->altura_cm)<div><dt>Altura</dt><dd>{{ $animal->altura_cm }} cm</dd></div>@endif
                <div><dt>Castrado</dt><dd>{{ $animal->castrado ? 'Sim' : 'Não' }}</dd></div>
            </dl>
        </section>
    </div>

    @if ($animal->local_nascimento || $animal->criador_nome || $animal->proprietario_nome)
    <section class="cartao">
        <h2>Trilha da vida</h2>
        <dl class="dados">
            @if ($animal->local_nascimento)<div><dt>Local de nascimento</dt><dd>{{ $animal->local_nascimento }}</dd></div>@endif
            @if ($animal->criador_nome)<div><dt>Criador</dt><dd>{{ $animal->criador_nome }}@if ($animal->criador_cidade) · {{ $animal->criador_cidade }}@if ($animal->criador_uf)/{{ $animal->criador_uf }}@endif @endif</dd></div>@endif
            @if ($animal->proprietario_nome)<div><dt>Proprietário atual</dt><dd>{{ $animal->proprietario_nome }}@if ($animal->proprietario_cidade) · {{ $animal->proprietario_cidade }}@if ($animal->proprietario_uf)/{{ $animal->proprietario_uf }}@endif @endif</dd></div>@endif
        </dl>
    </section>
    @endif

    @if ($proprio)
    <div class="grade-2">
        <section class="cartao">
            <h2>Aquisição</h2>
            <dl class="dados">
                <div><dt>Data de aquisição</dt><dd>{{ $animal->data_aquisicao ? $animal->data_aquisicao->format('d/m/Y') : '—' }}</dd></div>
                <div><dt>Forma</dt><dd>{{ \App\Models\Animal::FORMAS_AQUISICAO[$animal->forma_aquisicao] ?? '—' }}</dd></div>
                @if ($animal->valor_aquisicao)<div><dt>Valor</dt><dd>R$ {{ number_format($animal->valor_aquisicao, 2, ',', '.') }}</dd></div>@endif
            </dl>
        </section>
        @if ($socios->isNotEmpty())
        <section class="cartao">
            <h2>Sociedade</h2>
            <div class="tabela-rolagem"><table class="tabela">
                <thead><tr><th>Sócio</th><th>CPF / CNPJ</th><th class="num">Parte</th></tr></thead>
                <tbody>
                @foreach ($socios as $s)
                    <tr>
                        <td>{{ $s->nome_atual }}</td>
                        <td>{{ \App\Support\Documento::formatar($s->documento) }}</td>
                        <td class="num">{{ \App\Support\Sociedade::pct($s->percentual) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>
        @endif
    </div>
    @endif

    <section class="cartao">
        <h2>Genealogia</h2>
        <dl class="dados">
            <div><dt>Pai</dt><dd>
                @if ($animal->pai_id && $animal->paiAnimal)
                    <a href="{{ route('painel.consulta.show', $animal->paiAnimal) }}"><b>{{ $animal->paiAnimal->nome }}</b></a>
                    @if ($animal->paiAnimal->raca) · {{ $animal->paiAnimal->raca }}@endif
                @else {{ $animal->pai ?: '—' }} @endif
            </dd></div>
            <div><dt>Mãe</dt><dd>
                @if ($animal->mae_id && $animal->maeAnimal)
                    <a href="{{ route('painel.consulta.show', $animal->maeAnimal) }}"><b>{{ $animal->maeAnimal->nome }}</b></a>
                    @if ($animal->maeAnimal->raca) · {{ $animal->maeAnimal->raca }}@endif
                @else {{ $animal->mae ?: '—' }} @endif
            </dd></div>
        </dl>
    </section>

    @if ($animal->observacoes)
    <section class="cartao"><h2>Observações</h2><p class="pre">{{ $animal->observacoes }}</p></section>
    @endif

{{-- ==================== COBRIÇÕES ==================== --}}
@elseif ($aba === 'cobricoes')
    @if ($cobricoes->isEmpty())
        <div class="vazio"><p>Nenhuma cobrição registrada para {{ $animal->nome }}.</p></div>
    @else
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr><th>Data</th><th>Tipo</th><th>Égua</th><th>Garanhão</th><th>Resultado</th><th></th></tr></thead>
            <tbody>
            @foreach ($cobricoes as $c)
                @php
                    $egua = $c->egua_id == $animal->id ? $animal->nome : (\App\Models\Animal::find($c->egua_id)?->nome ?? '—');
                    $gar = $c->garanhao_id == $animal->id ? $animal->nome : ($c->garanhao_nome ?: (\App\Models\Animal::find($c->garanhao_id)?->nome ?? '—'));
                    $tipos = ['monta_natural' => 'Monta natural', 'inseminacao_artificial' => 'Inseminação artificial', 'transferencia_embriao' => 'Transferência de embrião'];
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($c->data_cobricao)->format('d/m/Y') }}</td>
                    <td>{{ $tipos[$c->tipo] ?? $c->tipo }}</td>
                    <td>{{ $egua }}</td>
                    <td>{{ $gar }}</td>
                    <td><span class="selo selo--{{ $c->resultado }}">{{ ucfirst(str_replace('_', ' ', $c->resultado)) }}</span></td>
                    <td>
                        @if ($proprio)
                            <a href="{{ route('painel.cobricoes.show', $c->uuid) }}" class="botao botao--contorno botao--pequeno">Ver</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

{{-- ==================== NASCIMENTOS ==================== --}}
@elseif ($aba === 'nascimentos')
    @if ($nascimentos->isEmpty())
        <div class="vazio"><p>Nenhum nascimento registrado para {{ $animal->nome }}.</p></div>
    @else
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr><th>Data</th><th>Produto</th><th>Sexo</th><th>Mãe</th><th>Pai</th><th>Parto</th><th></th></tr></thead>
            <tbody>
            @foreach ($nascimentos as $n)
                @php
                    $produto = $n->nome_produto ?: ($n->animal_id ? (\App\Models\Animal::find($n->animal_id)?->nome ?? '—') : '—');
                    $mae = $n->mae_id == $animal->id ? $animal->nome : (\App\Models\Animal::find($n->mae_id)?->nome ?? '—');
                    $pai = $n->pai_id == $animal->id ? $animal->nome : ($n->pai_nome ?: (\App\Models\Animal::find($n->pai_id)?->nome ?? '—'));
                    $sexos = ['macho' => 'M', 'femea' => 'F', 'indefinido' => '?'];
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($n->data_nascimento)->format('d/m/Y') }}</td>
                    <td><b>{{ $produto }}</b>@if(! $n->vivo) <span class="selo selo--morto">Natimorto</span>@endif</td>
                    <td>{{ $sexos[$n->sexo] ?? '—' }}</td>
                    <td>{{ $mae }}</td>
                    <td>{{ $pai }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $n->tipo_parto)) }}</td>
                    <td>
                        @if ($proprio)
                            <a href="{{ route('painel.nascimentos.show', $n->uuid) }}" class="botao botao--contorno botao--pequeno">Ver</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

{{-- ==================== TRANSFERÊNCIAS ==================== --}}
@elseif ($aba === 'transferencias')
    @if ($transferencias->isEmpty())
        <div class="vazio"><p>Nenhuma transferência registrada para {{ $animal->nome }}.</p></div>
    @else
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr><th>Data</th><th>Tipo</th><th>Origem</th><th>Destino</th><th>Situação</th><th></th></tr></thead>
            <tbody>
            @foreach ($transferencias as $t)
                @php
                    $origem = \App\Models\Empresa::find($t->empresa_origem_id);
                    $destino = $t->empresa_destino_id ? \App\Models\Empresa::find($t->empresa_destino_id) : null;
                    $tipos = ['venda' => 'Venda', 'doacao' => 'Doação', 'emprestimo' => 'Empréstimo', 'devolucao' => 'Devolução', 'outro' => 'Outro'];
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t->data_transferencia)->format('d/m/Y') }}</td>
                    <td>{{ $tipos[$t->tipo] ?? $t->tipo }}</td>
                    <td>{{ $origem->nome ?? '—' }}</td>
                    <td>{{ $destino->nome ?? ($t->destino_externo ?: '—') }}</td>
                    <td><span class="selo selo--{{ $t->situacao }}">{{ ucfirst($t->situacao) }}</span></td>
                    <td>
                        @if ($proprio)
                            <a href="{{ route('painel.transferencias.show', $t->uuid) }}" class="botao botao--contorno botao--pequeno">Ver</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

{{-- ==================== BAIXAS ==================== --}}
@elseif ($aba === 'baixas')
    @if ($animal->situacao === 'morto' && $animal->morte_data)
        <section class="cartao">
            <h2>Óbito registrado</h2>
            <dl class="dados">
                <div><dt>Data</dt><dd>{{ $animal->morte_data->format('d/m/Y') }}</dd></div>
                @if ($animal->morte_causa)<div><dt>Causa</dt><dd>{{ $animal->morte_causa }}</dd></div>@endif
                @if ($animal->morte_laudo)<div><dt>Laudo</dt><dd class="pre">{{ $animal->morte_laudo }}</dd></div>@endif
                <div><dt>Situação</dt><dd><span class="selo selo--morto">Morto</span></dd></div>
            </dl>
        </section>
    @else
        <div class="vazio"><p>{{ $animal->nome }} não tem registro de baixa (óbito).</p></div>
    @endif

{{-- ==================== FILHOS ==================== --}}
@elseif ($aba === 'filhos')
    @if ($filhos->isEmpty())
        <div class="vazio"><p>Nenhum filho registrado de {{ $animal->nome }}.</p></div>
    @else
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr><th>Nome</th><th>Sexo</th><th>Raça</th><th>Nascimento</th><th>Parentesco</th><th>Situação</th><th></th></tr></thead>
            <tbody>
            @foreach ($filhos as $f)
                @php
                    $parentesco = [];
                    if ($f->pai_id === $animal->id) $parentesco[] = 'Pai';
                    if ($f->mae_id === $animal->id) $parentesco[] = 'Mãe';
                @endphp
                <tr>
                    <td><a href="{{ route('painel.consulta.show', $f) }}"><b>{{ $f->nome }}</b></a></td>
                    <td>{{ $f->sexo === 'macho' ? 'M' : ($f->sexo === 'femea' ? 'F' : '—') }}</td>
                    <td>{{ $f->raca ?: '—' }}</td>
                    <td>{{ $f->data_nascimento ? $f->data_nascimento->format('d/m/Y') : '—' }}</td>
                    <td>{{ implode(' / ', $parentesco) }}</td>
                    <td><span class="selo selo--{{ $f->situacao }}">{{ $f->situacaoTexto() }}</span></td>
                    <td><a href="{{ route('painel.consulta.show', $f) }}" class="botao botao--contorno botao--pequeno">Ver ficha</a></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

{{-- ==================== GENEALOGIA ==================== --}}
@elseif ($aba === 'genealogia')
    @if (($arvore['pai']['animal'] ?? null) || ($arvore['mae']['animal'] ?? null) || ($arvore['pai']['nome'] ?? null) || ($arvore['mae']['nome'] ?? null))
    <section class="cartao">
        <h2>Árvore genealógica</h2>
        <div class="arvore-genealogica">
            <div class="arvore-nivel arvore-raiz"><b>{{ $animal->nome }}</b></div>
            <div class="arvore-filhos">
                <div class="arvore-ramo">
                    @php $pai = $arvore['pai']['animal'] ?? null; @endphp
                    <div class="arvore-nivel">
                        @if ($pai)<a href="{{ route('painel.consulta.show', $pai) }}">{{ $pai->nome }}</a>@else {{ $arvore['pai']['nome'] ?? '—' }} @endif
                        <small class="suave">Pai</small>
                    </div>
                    @if ($pai)
                    <div class="arvore-filhos">
                        <div class="arvore-ramo">
                            @php $avoPat = $arvore['pai']['pai']['animal'] ?? null; @endphp
                            <div class="arvore-nivel arvore-folha">
                                @if ($avoPat)<a href="{{ route('painel.consulta.show', $avoPat) }}">{{ $avoPat->nome }}</a>@else {{ $arvore['pai']['pai']['nome'] ?? '—' }} @endif
                                <small class="suave">Avô pat.</small>
                            </div>
                        </div>
                        <div class="arvore-ramo">
                            @php $avoMat = $arvore['pai']['mae']['animal'] ?? null; @endphp
                            <div class="arvore-nivel arvore-folha">
                                @if ($avoMat)<a href="{{ route('painel.consulta.show', $avoMat) }}">{{ $avoMat->nome }}</a>@else {{ $arvore['pai']['mae']['nome'] ?? '—' }} @endif
                                <small class="suave">Avó pat.</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="arvore-ramo">
                    @php $mae = $arvore['mae']['animal'] ?? null; @endphp
                    <div class="arvore-nivel">
                        @if ($mae)<a href="{{ route('painel.consulta.show', $mae) }}">{{ $mae->nome }}</a>@else {{ $arvore['mae']['nome'] ?? '—' }} @endif
                        <small class="suave">Mãe</small>
                    </div>
                    @if ($mae)
                    <div class="arvore-filhos">
                        <div class="arvore-ramo">
                            @php $avoPat2 = $arvore['mae']['pai']['animal'] ?? null; @endphp
                            <div class="arvore-nivel arvore-folha">
                                @if ($avoPat2)<a href="{{ route('painel.consulta.show', $avoPat2) }}">{{ $avoPat2->nome }}</a>@else {{ $arvore['mae']['pai']['nome'] ?? '—' }} @endif
                                <small class="suave">Avô mat.</small>
                            </div>
                        </div>
                        <div class="arvore-ramo">
                            @php $avoMat2 = $arvore['mae']['mae']['animal'] ?? null; @endphp
                            <div class="arvore-nivel arvore-folha">
                                @if ($avoMat2)<a href="{{ route('painel.consulta.show', $avoMat2) }}">{{ $avoMat2->nome }}</a>@else {{ $arvore['mae']['mae']['nome'] ?? '—' }} @endif
                                <small class="suave">Avó mat.</small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    @else
        <div class="vazio"><p>Sem dados de genealogia para {{ $animal->nome }}.</p></div>
    @endif

{{-- ==================== SITUAÇÃO ==================== --}}
@elseif ($aba === 'situacao')
    <section class="cartao">
        <h2>Situação atual</h2>
        <dl class="dados">
            <div><dt>Situação</dt><dd><span class="selo selo--{{ $animal->situacao }}">{{ $animal->situacaoTexto() }}</span></dd></div>
            @if ($animal->situacao_data)<div><dt>Data</dt><dd>{{ $animal->situacao_data->format('d/m/Y') }}</dd></div>@endif
            @if ($animal->situacao_motivo)<div><dt>Motivo</dt><dd>{{ $animal->situacao_motivo }}</dd></div>@endif
            @if ($animal->situacao === 'morto' && $animal->morte_data)
                <div><dt>Data do óbito</dt><dd>{{ $animal->morte_data->format('d/m/Y') }}</dd></div>
                @if ($animal->morte_causa)<div><dt>Causa</dt><dd>{{ $animal->morte_causa }}</dd></div>@endif
            @endif
        </dl>
    </section>

{{-- ==================== EM BREVE ==================== --}}
@elseif (in_array($aba, $emBreve))
    @php
        $desc = [
            'exames' => 'Controle de exames laboratoriais, hemogramas e laudos veterinários.',
            'resenhas' => 'Resenha descritiva e marcações físicas do animal.',
            'premiacoes' => 'Premiações, exposições e competições em que o animal participou.',
            'alertas' => 'Alertas e pendências como vacinas, vermifugações e documentos.',
            'semen' => 'Controle de doses de sêmen congelado, partidas e laboratório.',
        ];
    @endphp
    <div class="vazio" style="padding:40px 20px;text-align:center">
        <p style="font-size:1.3rem;margin-bottom:6px"><b>{{ $abas[$aba] }}</b></p>
        <p class="suave">{{ $desc[$aba] ?? '' }}</p>
        <p class="suave" style="margin-top:12px">Em breve no MinhaTropa.</p>
    </div>
@endif
</div>
@endsection
