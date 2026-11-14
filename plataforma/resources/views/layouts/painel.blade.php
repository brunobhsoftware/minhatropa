@extends('layouts.base')
@section('titulo_sufixo', 'MinhaTropa')
@php
    $licenca = (bool) ($haras->licenca_ativa ?? false);
    $papel = $papel ?? 'consulta';
    $dono = $papel === 'proprietario';
    // Menu base: o item só aparece se o papel da pessoa alcança a área (RBAC).
    $menu = [
        ['rota' => 'painel', 'icone' => 'painel', 'texto' => 'Início', 'exato' => true],
        ['rota' => 'painel.animais', 'icone' => 'cavalo', 'texto' => 'Animais', 'area' => 'animais'],
        ['rota' => 'painel.cobricoes', 'icone' => 'historico', 'texto' => 'Cobrições', 'area' => 'reproducao', 'licenca' => true],
        ['rota' => 'painel.nascimentos', 'icone' => 'mais', 'texto' => 'Nascimentos', 'area' => 'reproducao', 'licenca' => true],
        ['rota' => 'painel.transferencias', 'icone' => 'site', 'texto' => 'Transferências', 'area' => 'transferencias', 'licenca' => true],
        ['rota' => 'painel.profissionais', 'icone' => 'usuarios', 'texto' => 'Criador e equipe', 'area' => 'equipe'],
        ['rota' => 'painel.haras', 'icone' => 'haras', 'texto' => 'Dados '.$haras->doRotulo(), 'area' => 'propriedade'],
        ['rota' => 'painel.plano', 'icone' => 'plano', 'texto' => 'Plano e módulos', 'area' => 'assinatura'],
    ];
    $modulosMenu = \App\Support\Modulos::todos();
    $modulosHaras = $modulosHaras ?? [];
    $emBreve = ['Agenda e rotinas', 'Treinos e desempenho', 'Custos e financeiro', 'Relatórios'];
    $outros = isset($haras) ? \App\Support\HarasAtual::disponiveis(auth()->user())->count() > 1 : false;
    $areaModulo = ['equipe' => 'equipe', 'saude' => 'saude', 'manejo' => 'manejo', 'nutricao' => 'nutricao'];
@endphp
@section('corpo')
<div class="app">
    <aside class="menu-lateral" id="menu-lateral" aria-label="Menu {{ $haras->doRotulo() }}">
        <a href="{{ route('painel') }}" class="menu-lateral__marca"><img src="{{ asset('img/logo-minhatropa-branca.png') }}" alt="MinhaTropa" width="190" height="42"></a>
        <div class="menu-lateral__haras"><small>{{ $haras->rotulo() }}</small><b>{{ $haras->nome }}</b>@if ($outros)<a href="{{ route('painel.escolher') }}">Trocar</a>@endif</div>
        <nav>
            @foreach ($menu as $item)
                @continue(isset($item['area']) && ! \App\Support\Permissao::pode($papel, $item['area']))
                @if (($item['licenca'] ?? false) && ! $licenca)
                    <a href="{{ $dono ? route('painel.plano') : '#' }}" class="desativado" title="Disponível nos planos pagos">🔒 {{ $item['texto'] }}</a>
                @else
                    @php $ativo = ($item['exato'] ?? false) ? request()->routeIs($item['rota']) : request()->routeIs($item['rota'].'*'); @endphp
                    <a href="{{ route($item['rota']) }}" class="{{ $ativo ? 'ativo' : '' }}" @if ($ativo) aria-current="page" @endif><x-icone :nome="$item['icone']" /> {{ $item['texto'] }}</a>
                @endif
            @endforeach
        </nav>
        @if ($modulosMenu->isNotEmpty())
        <div class="menu-lateral__grupo">Módulos adicionais</div>
        <nav data-modulos>
            @foreach ($modulosMenu as $m)
                @php
                    $liberado = ($modulosHaras[$m->codigo] ?? null) === 'ativo';
                    $ativo = request()->routeIs('painel.modulos.show') && request()->route('modulo')?->codigo === $m->codigo;
                @endphp
                @continue($liberado && ! \App\Support\Permissao::pode($papel, $areaModulo[$m->codigo] ?? 'propriedade'))
                @continue($liberado && $m->codigo === 'equipe') {{-- ativo, o módulo de equipe vive em "Criador e equipe" --}}
                <a href="{{ route('painel.modulos.show', $m) }}" class="{{ $ativo ? 'ativo' : '' }} {{ $liberado ? '' : 'menu-modulo--bloqueado' }}" data-modulo="{{ $m->codigo }}" data-liberado="{{ $liberado ? 1 : 0 }}" @if ($ativo) aria-current="page" @endif>
                    <x-icone :nome="$liberado ? ($m->icone ?: 'check') : 'cadeado'" /> <span>{{ $m->nome }}</span>
                    @unless ($liberado)<span class="selo-modulo">Módulo adicional</span>@endunless
                </a>
            @endforeach
        </nav>
        @endif
        <div class="menu-lateral__grupo">Em breve</div>
        <nav>
            @foreach ($emBreve as $texto)
                <a href="#" aria-disabled="true" onclick="return false" class="desativado">{{ $texto }} <span class="selo-breve">em breve</span></a>
            @endforeach
        </nav>
        <div class="menu-lateral__fim">
            <a href="{{ route('profissional.inicio') }}"><x-icone nome="usuarios" /> Ambiente do profissional</a>
            @if (auth()->user()->admin_plataforma)<a href="{{ route('admin.inicio') }}"><x-icone nome="chave" /> Administração</a>@endif
        </div>
    </aside>
    <div class="principal">
        <header class="topo-painel">
            <button type="button" class="topo-painel__menu" data-menu-alternar aria-controls="menu-lateral" aria-expanded="false" aria-label="Abrir menu"><x-icone nome="menu" /></button>
            @if ($dono)
                @php $faixaTopo = \App\Support\Licenca::faixaAtual($haras); @endphp
                <a href="{{ route('painel.plano') }}" class="topo-painel__selo selo selo--{{ $licenca ? 'ativo' : 'teste' }}">{{ $faixaTopo?->nome ?? 'Plano gratuito' }}</a>
            @else
                <span class="topo-painel__selo selo selo--encerrado">{{ \App\Models\EmpresaUsuario::PAPEIS[$papel] ?? $papel }}</span>
            @endif
            <span class="topo-painel__quem">{{ auth()->user()->nome }}</span>
            <form method="post" action="{{ route('sair') }}">@csrf<button class="botao botao--escuro botao--pequeno"><x-icone nome="sair" :tamanho="16" /> Sair</button></form>
        </header>
        <main class="conteudo" id="conteudo">
            @include('layouts.alertas')
            @yield('conteudo')
        </main>
    </div>
</div>
@if (session('limite_plantel'))
    @include('painel.partes.limite-plantel')
@endif
@endsection
