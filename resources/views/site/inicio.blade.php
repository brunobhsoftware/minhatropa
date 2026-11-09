@extends('layouts.site')

@php
    // Ícones dos módulos (traço azul com detalhe amarelo, como no layout aprovado).
    $icones = [
        'animais' => '<path d="M5 21c0-6 2.5-10.5 7-13l1.5-4.5 1.5 3.5c3 1.2 5 4 5 7.5l-2.2 1.2-3-2-2.3 3.3"/><path class="destaque" d="M9 21c.8-3 2.7-5.2 5-6.3"/><circle cx="14.6" cy="9.3" r=".6" fill="currentColor"/>',
        'agenda' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/><g class="destaque"><path d="M7.5 14h2M11 14h2M14.5 14h2M7.5 17.5h2M11 17.5h2"/></g>',
        'saude' => '<path d="M20.4 5.6a5 5 0 0 0-7.1 0L12 6.9l-1.3-1.3a5 5 0 0 0-7.1 7.1L12 21l8.4-8.3a5 5 0 0 0 0-7.1z"/><path class="destaque" d="M4.5 12.5h4l1.5-3 2.5 6 1.5-3h5.5"/>',
        'reproducao' => '<path d="M7 3h10M7 21h10"/><path d="M8 3c0 5 8 6 8 9s-8 4-8 9"/><path class="destaque" d="M16 3c0 5-8 6-8 9s8 4 8 9M9.5 7.5h5M9.5 16.5h5"/>',
        'treinos' => '<path d="M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M7 6H4v1.5A3.5 3.5 0 0 0 7.5 11M17 6h3v1.5A3.5 3.5 0 0 1 16.5 11"/><path d="M12 14v3M8 21h8M9.5 17h5v4h-5z"/><path class="destaque" d="m12 5.8.9 1.8 2 .3-1.5 1.4.4 2-1.8-1-1.8 1 .4-2-1.5-1.4 2-.3z"/>',
        'financeiro' => '<ellipse cx="9" cy="6" rx="6" ry="2.5"/><path d="M3 6v4c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5V6"/><path class="destaque" d="M3 10v4c0 1.4 2.7 2.5 6 2.5M15 13.5c3.3 0 6 1.1 6 2.5s-2.7 2.5-6 2.5-6-1.1-6-2.5 2.7-2.5 6-2.5zM9 16v2.5c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5V16"/>',
        'relatorios' => '<path d="M3 21h18"/><rect x="5" y="12" width="3.5" height="6" rx=".5"/><rect x="10.25" y="8" width="3.5" height="10" rx=".5"/><rect class="destaque" x="15.5" y="4" width="3.5" height="14" rx=".5"/>',
    ];
    $svg = fn ($nome, $tam = 40) => '<svg class="icone-modulo" width="'.$tam.'" height="'.$tam.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$icones[$nome].'</svg>';

    $modulos = [
        ['animais', 'Cadastro', 'de Animais'],
        ['agenda', 'Agenda', 'e Rotinas'],
        ['saude', 'Saúde', 'Veterinária'],
        ['reproducao', 'Reprodução', 'e Genética'],
        ['treinos', 'Desempenho', 'e Treinos'],
        ['financeiro', 'Custos', 'e Financeiro'],
        ['relatorios', 'Relatórios', 'e Indicadores'],
    ];

    $recursos = [
        ['animais', 'Cadastro completo do plantel', 'Ficha de cada animal com raça, pelagem, registro, categoria (matriz, garanhão, potro, animal em treino), fotos, documentos e histórico.'],
        ['agenda', 'Agenda e rotinas do Haras', 'Tarefas do dia para a equipe: alimentação, casqueamento, ferrageamento, manejo de piquetes e tudo o que não pode ser esquecido.'],
        ['saude', 'Saúde e prevenção', 'Vacinas, vermifugações, exames e atendimentos veterinários com alertas de vencimento e histórico clínico de cada animal.'],
        ['reproducao', 'Reprodução e genealogia', 'Coberturas, inseminações, diagnósticos de prenhez, previsão de parto, nascimentos e a árvore genealógica do seu plantel.'],
        ['treinos', 'Treinos e desempenho', 'Planejamento de treinos, evolução dos animais, provas e competições, com o desempenho de cada um registrado ao longo do tempo.'],
        ['financeiro', 'Custos, estoque e financeiro', 'Despesas por animal e por atividade, controle de ração, medicamentos e insumos, receitas e o resultado real do Haras.'],
    ];
@endphp

@section('conteudo')
{{-- Abertura --}}
<section class="hero">
    <div class="hero__foto" aria-hidden="true"></div>
    <div class="hero__interno">
        <div class="hero__texto">
            <h1>Gestão de Haras, cavalos e outras <span class="destaque-amarelo">operações.</span></h1>
            <p>O sistema completo para organizar sua tropa, controlar animais, treinos, saúde, reprodução e toda a rotina do seu Haras.</p>
            <div class="hero__botoes">
                <a href="#contato" class="botao botao--amarelo botao--grande">Quero conhecer <span aria-hidden="true">→</span></a>
                <a href="#recursos" class="botao botao--contorno-claro botao--grande"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4.5v15l12-7.5z" fill="#FFD60A"/></svg> Ver como funciona</a>
            </div>
        </div>
    </div>
</section>

{{-- Barra de módulos --}}
<section class="modulos" id="solucoes" aria-label="Módulos da plataforma">
    <ul class="modulos__lista">
        @foreach ($modulos as [$icone, $linha1, $linha2])
            <li>{!! $svg($icone, 36) !!}<span><b>{{ $linha1 }}</b> <b>{{ $linha2 }}</b></span></li>
        @endforeach
    </ul>
</section>

{{-- Tudo o que você precisa --}}
<section class="tudo">
    <div class="tudo__interno">
        <div class="tudo__texto">
            <span class="traco" aria-hidden="true"></span>
            <h2>Tudo o que você precisa <span>para a gestão do seu Haras.</span></h2>
            <ul class="lista-check">
                <li>Controle completo do plantel</li>
                <li>Gestão de treinamentos e desempenho</li>
                <li>Acompanhamento veterinário e preventivo</li>
                <li>Controle reprodutivo e genealogia</li>
                <li>Custos, estoque e financeiro do Haras</li>
                <li>Relatórios e indicadores em tempo real</li>
            </ul>
        </div>
        <div class="tudo__telas" aria-label="Ilustração do painel do MinhaTropa no computador e no celular">
            <div class="notebook">
                <div class="notebook__tela">
                    <div class="painel-demo">
                        <div class="painel-demo__topo">
                            <img src="{{ asset('img/logo-minhatropa-branca.png') }}" alt="" width="110" height="24" loading="lazy">
                            <span class="painel-demo__haras">Haras Vista Alegre ▾</span>
                        </div>
                        <div class="painel-demo__corpo">
                            <ul class="painel-demo__menu">
                                <li class="ativo">Início</li><li>Animais</li><li>Agenda</li><li>Saúde</li><li>Reprodução</li><li>Treinos</li><li>Financeiro</li><li>Relatórios</li>
                            </ul>
                            <div class="painel-demo__conteudo">
                                <b class="painel-demo__titulo">Painel do Haras</b>
                                <div class="painel-demo__numeros">
                                    <div>{!! $svg('animais', 18) !!}<b>128</b><small>Animais</small></div>
                                    <div>{!! $svg('agenda', 18) !!}<b>18</b><small>Rotinas hoje</small></div>
                                    <div>{!! $svg('saude', 18) !!}<b>5</b><small>Atendimentos</small></div>
                                    <div>{!! $svg('reproducao', 18) !!}<b>12</b><small>Reprodução</small></div>
                                </div>
                                <div class="painel-demo__graficos">
                                    <div class="painel-demo__grafico">
                                        <small>Desempenho do Haras</small>
                                        <div class="barras" aria-hidden="true"><i style="height:30%"></i><i style="height:45%"></i><i style="height:38%"></i><i style="height:60%"></i><i style="height:52%"></i><i style="height:75%"></i><i style="height:66%"></i><i style="height:90%"></i></div>
                                    </div>
                                    <div class="painel-demo__grafico">
                                        <small>Plantel por categoria</small>
                                        <div class="rosca-area"><span class="rosca" aria-hidden="true"></span>
                                            <ul class="legenda"><li><i style="background:#FFD60A"></i>Matriz</li><li><i style="background:#2e7d32"></i>Garanhão</li><li><i style="background:#f4a100"></i>Potro</li><li><i style="background:#98a2b3"></i>Treino</li></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="notebook__base"></div>
            </div>
            <div class="celular">
                <div class="celular__tela">
                    <img src="{{ asset('img/simbolo-minhatropa-branco.png') }}" alt="" width="54" height="40" loading="lazy">
                    <b>Minha<span>Tropa</span></b>
                    <ul>
                        <li>{!! $svg('animais', 18) !!} Animais</li>
                        <li>{!! $svg('agenda', 18) !!} Rotinas</li>
                        <li>{!! $svg('saude', 18) !!} Saúde</li>
                        <li>{!! $svg('treinos', 18) !!} Treinos</li>
                    </ul>
                </div>
            </div>
            <small class="tudo__aviso">Imagem ilustrativa.</small>
        </div>
    </div>
    <div class="tudo__por-do-sol" aria-hidden="true"></div>
</section>

{{-- Recursos --}}
<section class="secao" id="recursos">
    <div class="secao__interno">
        <span class="sobretitulo">Recursos</span>
        <h2 class="secao__titulo">Uma plataforma para toda a rotina do Haras</h2>
        <p class="secao__intro">Do nascimento do potro ao resultado financeiro: as informações do seu Haras organizadas, acessíveis pelo computador e pelo celular.</p>
        <div class="recursos">
            @foreach ($recursos as [$icone, $titulo, $texto])
                <article class="recurso">
                    <div class="recurso__icone">{!! $svg($icone, 34) !!}</div>
                    <h3>{{ $titulo }}</h3>
                    <p>{{ $texto }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Para quem --}}
<section class="secao secao--escura">
    <div class="secao__interno para-quem">
        <div>
            <span class="sobretitulo">Para quem é</span>
            <h2 class="secao__titulo">Feito para quem vive o dia a dia do Haras</h2>
            <p class="secao__intro">Cada pessoa da equipe acessa o que precisa, com o seu papel: proprietário, gerente, veterinário, treinador, tratador e financeiro. E quem administra mais de um Haras acompanha todos com o mesmo acesso.</p>
        </div>
        <ul class="publicos">
            <li><b>Haras e criatórios</b><span>Plantel, reprodução e genealogia sob controle.</span></li>
            <li><b>Centros de treinamento</b><span>Treinos, evolução e agenda de cada animal.</span></li>
            <li><b>Fazendas com equinos</b><span>Rotinas, custos e estoque da propriedade.</span></li>
            <li><b>Gestores de vários Haras</b><span>Uma visão única de todas as propriedades.</span></li>
        </ul>
    </div>
</section>

{{-- Implantação --}}
<section class="secao" id="implantacao">
    <div class="secao__interno">
        <span class="sobretitulo">Implantação</span>
        <h2 class="secao__titulo">Do primeiro contato ao Haras funcionando</h2>
        <ol class="passos">
            <li><b>Conversa</b><span>Entendemos a rotina, o tamanho do plantel e as prioridades do seu Haras.</span></li>
            <li><b>Configuração</b><span>Cadastramos o Haras, a equipe e o papel de cada pessoa na plataforma.</span></li>
            <li><b>Cadastro do plantel</b><span>Os animais e as informações que você já tem entram no sistema com acompanhamento.</span></li>
            <li><b>Acompanhamento</b><span>Seguimos junto com a equipe nas primeiras semanas de uso.</span></li>
        </ol>
    </div>
</section>

{{-- Contato --}}
<section class="secao secao--cinza" id="contato">
    <div class="secao__interno contato">
        <div class="contato__texto">
            <span class="sobretitulo">Contato</span>
            <h2 class="secao__titulo">Quer levar o MinhaTropa para o seu Haras?</h2>
            <p class="secao__intro">Deixe seus dados que a nossa equipe entra em contato para apresentar a plataforma e combinar a implantação.</p>
            <p class="contato__email">Se preferir, escreva para <a href="mailto:atendimento@minhatropa.com.br">atendimento@minhatropa.com.br</a></p>
            <img src="{{ asset('img/logo-minhatropa.png') }}" alt="" width="320" height="71" loading="lazy" class="contato__logo">
        </div>
        <div class="contato__form">
            @if (session('contato_ok'))
                <div class="aviso aviso--ok" role="status"><b>Recebemos seu contato!</b> Em breve falaremos com você.</div>
            @endif
            @if ($errors->any())
                <div class="aviso aviso--erro" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('contato') }}" novalidate>
                @csrf
                <div class="oculto" aria-hidden="true"><label>Site <input name="site" tabindex="-1" autocomplete="off"></label></div>
                <label class="campo"><span>Seu nome *</span><input name="nome" value="{{ old('nome') }}" required maxlength="150" autocomplete="name"></label>
                <div class="campos-2">
                    <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" maxlength="190" autocomplete="email"></label>
                    <label class="campo"><span>WhatsApp / telefone</span><input type="tel" name="telefone" value="{{ old('telefone') }}" maxlength="30" autocomplete="tel"></label>
                </div>
                <div class="campos-2">
                    <label class="campo"><span>Nome do Haras</span><input name="haras" value="{{ old('haras') }}" maxlength="150"></label>
                    <label class="campo"><span>Cidade/UF</span><input name="cidade_uf" value="{{ old('cidade_uf') }}" maxlength="120"></label>
                </div>
                <label class="campo"><span>Quantos animais, aproximadamente?</span>
                    <select name="quantidade_animais">
                        <option value="">Selecione</option>
                        @foreach (['Até 20', '21 a 50', '51 a 150', '151 a 500', 'Mais de 500'] as $faixa)<option @selected(old('quantidade_animais') === $faixa)>{{ $faixa }}</option>@endforeach
                    </select></label>
                <label class="campo"><span>Mensagem</span><textarea name="mensagem" rows="3" maxlength="3000" placeholder="Conte um pouco sobre o seu Haras (opcional)">{{ old('mensagem') }}</textarea></label>
                <button class="botao botao--amarelo botao--grande botao--largo">Quero conhecer o MinhaTropa <span aria-hidden="true">→</span></button>
                <small class="contato__lgpd">Usamos seus dados só para responder ao seu contato.</small>
            </form>
        </div>
    </div>
</section>
@endsection
