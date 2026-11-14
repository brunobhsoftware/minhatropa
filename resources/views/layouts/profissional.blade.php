@extends('layouts.base')
@section('titulo_sufixo', 'MinhaTropa')
@php
    $menuProf = [
        ['rota' => 'profissional.inicio', 'icone' => 'painel', 'texto' => 'Início'],
        ['rota' => 'profissional.dados', 'icone' => 'usuarios', 'texto' => 'Meus dados'],
    ];
    $acessosProf = \App\Support\Esquema::atual() ? \App\Support\HarasAtual::disponiveis(auth()->user()) : collect();
@endphp
@section('corpo')
<div class="app">
    <aside class="menu-lateral" id="menu-lateral" aria-label="Menu do profissional">
        <a href="{{ route('profissional.inicio') }}" class="menu-lateral__marca"><img src="{{ asset('img/logo-minhatropa-branca.png') }}" alt="MinhaTropa" width="190" height="42"></a>
        <div class="menu-lateral__haras"><small>Ambiente do profissional</small><b>{{ auth()->user()->nome }}</b></div>
        <nav>
            @foreach ($menuProf as $item)
                @php $ativo = request()->routeIs($item['rota']); @endphp
                <a href="{{ route($item['rota']) }}" class="{{ $ativo ? 'ativo' : '' }}" @if ($ativo) aria-current="page" @endif><x-icone :nome="$item['icone']" /> {{ $item['texto'] }}</a>
            @endforeach
            <a href="{{ route('cadastro') }}"><x-icone nome="mais" /> Cadastrar Haras / Fazenda</a>
        </nav>
        @if ($acessosProf->isNotEmpty())
        <div class="menu-lateral__grupo">Propriedades que acesso</div>
        <nav>
            @foreach ($acessosProf as $h)
                <form method="post" action="{{ route('painel.trocar', $h) }}" class="menu-lateral__form">@csrf<button><x-icone nome="haras" /> {{ $h->nome }}</button></form>
            @endforeach
        </nav>
        @endif
        @if (auth()->user()->admin_plataforma)
        <div class="menu-lateral__fim"><a href="{{ route('admin.inicio') }}"><x-icone nome="chave" /> Administração</a></div>
        @endif
    </aside>
    <div class="principal">
        <header class="topo-painel">
            <button type="button" class="topo-painel__menu" data-menu-alternar aria-controls="menu-lateral" aria-expanded="false" aria-label="Abrir menu"><x-icone nome="menu" /></button>
            <span class="topo-painel__selo selo selo--encerrado">Profissional</span>
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
