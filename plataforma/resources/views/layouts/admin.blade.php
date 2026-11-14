@extends('layouts.base')

@php
    $menuAdmin = [
        ['rota' => 'admin.inicio', 'icone' => 'painel', 'texto' => 'Início', 'exato' => true],
        ['rota' => 'admin.haras', 'icone' => 'haras', 'texto' => 'Haras'],
        ['rota' => 'admin.profissionais', 'icone' => 'usuarios', 'texto' => 'Profissionais'],
        ['rota' => 'admin.contatos', 'icone' => 'mensagem', 'texto' => 'Contatos do site'],
        ['rota' => 'admin.usuarios', 'icone' => 'usuarios', 'texto' => 'Administradores'],
        ['rota' => 'admin.banco', 'icone' => 'banco', 'texto' => 'Atualizações do banco'],
        ['rota' => 'admin.auditoria', 'icone' => 'historico', 'texto' => 'Auditoria'],
    ];
    $emBreve = ['Planos e cobrança', 'Mercado Pago', 'E-mails enviados'];
@endphp

@section('corpo')
<div class="app">
    <aside class="menu-lateral" id="menu-lateral" aria-label="Menu da administração">
        <a href="{{ route('admin.inicio') }}" class="menu-lateral__marca">
            <img src="{{ asset('img/logo-minhatropa-branca.png') }}" alt="MinhaTropa" width="190" height="42">
        </a>
        <div class="menu-lateral__grupo">Administração</div>
        <nav>
            @foreach ($menuAdmin as $item)
                @php $ativo = ($item['exato'] ?? false) ? request()->routeIs($item['rota']) : request()->routeIs($item['rota'].'*'); @endphp
                <a href="{{ route($item['rota']) }}" class="{{ $ativo ? 'ativo' : '' }}" @if ($ativo) aria-current="page" @endif><x-icone :nome="$item['icone']" /> {{ $item['texto'] }}</a>
            @endforeach
        </nav>
        <div class="menu-lateral__grupo">Próxima etapa</div>
        <nav>
            @foreach ($emBreve as $texto)
                <a href="#" aria-disabled="true" onclick="return false" class="desativado">{{ $texto }} <span class="selo-breve">em breve</span></a>
            @endforeach
        </nav>
        <div class="menu-lateral__fim">
            <a href="{{ route('inicio') }}" target="_blank" rel="noopener"><x-icone nome="site" /> Ver o site</a>
        </div>
    </aside>

    <div class="principal">
        <header class="topo-painel">
            <button type="button" class="topo-painel__menu" data-menu-alternar aria-controls="menu-lateral" aria-expanded="false" aria-label="Abrir menu"><x-icone nome="menu" /></button>
            <span class="topo-painel__selo">Administração da plataforma</span>
            <span class="topo-painel__quem">{{ auth()->user()->nome }}</span>
            <a href="{{ route('admin.minha-senha') }}" class="botao botao--contorno botao--pequeno"><x-icone nome="chave" :tamanho="16" /> Minha senha</a>
            <form method="post" action="{{ route('admin.sair') }}">@csrf<button class="botao botao--escuro botao--pequeno"><x-icone nome="sair" :tamanho="16" /> Sair</button></form>
        </header>
        <main class="conteudo" id="conteudo">
            @include('layouts.alertas')
            @yield('conteudo')
        </main>
    </div>
</div>
@endsection
