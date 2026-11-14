@extends('layouts.base')
@section('titulo_sufixo', 'MinhaTropa')
@php
    $licenca = (bool) ($haras->licenca_ativa ?? false);
    $menu = [
        ['rota' => 'painel', 'icone' => 'painel', 'texto' => 'Início', 'exato' => true],
        ['rota' => 'painel.animais', 'icone' => 'cavalo', 'texto' => 'Animais'],
        ['rota' => 'painel.cobricoes', 'icone' => 'historico', 'texto' => 'Cobrições', 'licenca' => true],
        ['rota' => 'painel.nascimentos', 'icone' => 'mais', 'texto' => 'Nascimentos', 'licenca' => true],
        ['rota' => 'painel.transferencias', 'icone' => 'site', 'texto' => 'Transferências', 'licenca' => true],
        ['rota' => 'painel.profissionais', 'icone' => 'usuarios', 'texto' => 'Equipe'],
        ['rota' => 'painel.haras', 'icone' => 'haras', 'texto' => 'Dados do Haras'],
    ];
    $bloqueados = ['Agenda e rotinas', 'Saúde veterinária', 'Treinos e desempenho', 'Custos e financeiro', 'Relatórios'];
    $outros = isset($haras) ? \App\Support\HarasAtual::disponiveis(auth()->user())->count() > 1 : false;
@endphp
@section('corpo')
<div class="app">
    <aside class="menu-lateral" id="menu-lateral" aria-label="Menu do Haras">
        <a href="{{ route('painel') }}" class="menu-lateral__marca"><img src="{{ asset('img/logo-minhatropa-branca.png') }}" alt="MinhaTropa" width="190" height="42"></a>
        <div class="menu-lateral__haras"><small>Haras</small><b>{{ $haras->nome }}</b>@if ($outros)<a href="{{ route('painel.escolher') }}">Trocar</a>@endif</div>
        <nav>
            @foreach ($menu as $item)
                @if (($item['licenca'] ?? false) && ! $licenca)
                    <a href="{{ route('painel.licenca') }}" class="desativado">🔒 {{ $item['texto'] }}</a>
                @else
                    @php $ativo = ($item['exato'] ?? false) ? request()->routeIs($item['rota']) : request()->routeIs($item['rota'].'*'); @endphp
                    <a href="{{ route($item['rota']) }}" class="{{ $ativo ? 'ativo' : '' }}" @if ($ativo) aria-current="page" @endif><x-icone :nome="$item['icone']" /> {{ $item['texto'] }}</a>
                @endif
            @endforeach
        </nav>
        <div class="menu-lateral__grupo">{{ $licenca ? 'Ferramentas' : 'Liberado após a contratação' }}</div>
        <nav>
            @foreach ($bloqueados as $texto)
                @if ($licenca)
                    <a href="#" aria-disabled="true" onclick="return false" class="desativado">{{ $texto }} <span class="selo-breve">em breve</span></a>
                @else
                    <a href="{{ route('painel.licenca') }}" class="desativado">🔒 {{ $texto }}</a>
                @endif
            @endforeach
        </nav>
        @if (auth()->user()->admin_plataforma)
        <div class="menu-lateral__fim"><a href="{{ route('admin.inicio') }}"><x-icone nome="chave" /> Administração</a></div>
        @endif
    </aside>
    <div class="principal">
        <header class="topo-painel">
            <button type="button" class="topo-painel__menu" data-menu-alternar aria-controls="menu-lateral" aria-expanded="false" aria-label="Abrir menu"><x-icone nome="menu" /></button>
            @if (! $licenca)<a href="{{ route('painel.licenca') }}" class="topo-painel__selo selo selo--teste">Período gratuito</a>@endif
            <span class="topo-painel__quem">{{ auth()->user()->nome }}</span>
            <form method="post" action="{{ route('sair') }}">@csrf<button class="botao botao--escuro botao--pequeno"><x-icone nome="sair" :tamanho="16" /> Sair</button></form>
        </header>
        <main class="conteudo" id="conteudo">
            @include('layouts.alertas')
            @yield('conteudo')
        </main>
    </div>
</div>
@endsection
