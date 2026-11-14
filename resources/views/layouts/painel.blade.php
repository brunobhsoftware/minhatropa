@extends('layouts.base')
@section('titulo_sufixo', 'MinhaTropa')
@php
    $licenca = (bool) ($haras->licenca_ativa ?? false);
    $papel = $papel ?? 'consulta';
    $dono = $papel === 'proprietario';
    $pode = fn (string $area, bool $escrita = false) => \App\Support\Permissao::pode($papel, $area, $escrita);
    $modulosHaras = $modulosHaras ?? [];
    $modulos = \App\Support\Modulos::todos()->keyBy('codigo');
    $rota = fn (string ...$padroes) => request()->routeIs(...$padroes);
    $moduloAtual = request()->routeIs('painel.modulos.show') ? request()->route('modulo')?->codigo : null;

    // Item de módulo adicional: liberado abre a ferramenta; não contratado mostra o cadeado e leva à tela de benefícios.
    $modulo = function (string $texto, string $codigo) use ($modulos, $modulosHaras, $moduloAtual) {
        if (! $modulos->has($codigo)) {
            return ['texto' => $texto, 'breve' => true];
        }

        return ['texto' => $texto, 'url' => route('painel.modulos.show', $codigo), 'modulo' => $codigo,
            'liberado' => ($modulosHaras[$codigo] ?? null) === 'ativo', 'ativo' => $moduloAtual === $codigo];
    };
    $breve = fn (string $texto) => ['texto' => $texto, 'breve' => true];

    /*
     * Menu lateral: poucos itens na primeira linha; clicar em um item com subitens abre a lista dele.
     * Cada subitem é: link (url), módulo adicional (cadeado até contratar) ou "em breve" (planejado, ainda sem tela).
     * 'ver' = false esconde o que o papel da pessoa não alcança (RBAC).
     */
    $menu = [
        ['texto' => 'Início', 'icone' => 'painel', 'url' => route('painel'), 'ativo' => $rota('painel')],
        ['texto' => 'Meu Plantel', 'icone' => 'cavalo', 'ver' => $pode('animais'), 'itens' => [
            ['texto' => 'Animais do plantel', 'url' => route('painel.animais'), 'ativo' => $rota('painel.animais*') && ! $rota('painel.animais.novo')],
            ['texto' => 'Cadastrar animal', 'url' => route('painel.animais.novo'), 'ativo' => $rota('painel.animais.novo'), 'ver' => $pode('animais', true)],
            $breve('Organograma do plantel'),
        ]],
        ['texto' => 'Ocorrências', 'icone' => 'historico', 'itens' => [
            ['texto' => 'Ocorrências do plantel', 'url' => route('painel.ocorrencias'), 'ver' => $pode('animais'),
                'ativo' => $rota('painel.ocorrencias', 'painel.cobricoes*', 'painel.nascimentos*', 'painel.transferencias*')],
            $breve('Ocorrências '.$haras->doRotulo()),
        ]],
        ['texto' => 'Reprodução', 'icone' => 'mais', 'itens' => [$breve('Solicitação de coleta'), $breve('Pedido de sêmen'), $breve('Acompanhamento de embrião'), $breve('Notificação ao sócio')]],
        ['texto' => 'Comércio', 'icone' => 'site', 'itens' => [$breve('Pregão on-line'), $breve('Venda permanente de coberturas'), $breve('Classificados')]],
        ['texto' => 'Clínica veterinária', 'icone' => 'saude', 'itens' => [$modulo('Manejo sanitário', 'manejo'), $modulo('Vermifugação', 'saude'), $modulo('Medicação', 'saude')]],
        ['texto' => 'Controle de estoque', 'icone' => 'nutricao', 'itens' => [$modulo('Ração, medicamentos, ferramentas', 'nutricao'), $breve('Leitura de nota fiscal')]],
        ['texto' => 'Financeiro', 'icone' => 'plano', 'itens' => [$breve('Contratos'), $breve('Boletos a receber e a pagar'), $breve('Gráficos de custos')]],
        ['texto' => 'RH', 'icone' => 'usuarios', 'itens' => [
            ['texto' => 'Cadastro da equipe', 'url' => route('painel.profissionais'), 'ativo' => $rota('painel.profissionais*'), 'ver' => $pode('equipe')],
            $modulo('Acesso externo da equipe', 'equipe') + ['ver' => $dono || (($modulosHaras['equipe'] ?? null) !== 'ativo')],
            $breve('Escala'),
        ]],
        ['texto' => 'Agenda de eventos', 'icone' => 'historico', 'breve' => true],
    ];
    $configuracao = [
        ['texto' => 'Dados '.$haras->doRotulo(), 'icone' => 'haras', 'ver' => $pode('propriedade'), 'itens' => [
            ['texto' => 'Cadastro completo', 'url' => route('painel.haras'), 'ativo' => $rota('painel.haras*')],
            ['texto' => 'Página, logomarca e cores', 'url' => route('painel.haras').'#pagina', 'ver' => \App\Support\Esquema::temV008() && $pode('propriedade', true)],
        ]],
        ['texto' => 'Plano e módulos', 'icone' => 'plano', 'url' => route('painel.plano'), 'ativo' => $rota('painel.plano*'), 'ver' => $pode('assinatura')],
    ];
    $outros = isset($haras) ? \App\Support\HarasAtual::disponiveis(auth()->user())->count() > 1 : false;
@endphp
@section('corpo')
<div class="app">
    <aside class="menu-lateral" id="menu-lateral" aria-label="Menu {{ $haras->doRotulo() }}">
        <a href="{{ route('painel') }}" class="menu-lateral__marca"><img src="{{ asset('img/logo-minhatropa-branca.png') }}" alt="MinhaTropa" width="190" height="42"></a>
        <div class="menu-lateral__haras"><small>{{ $haras->rotulo() }}</small><b>{{ $haras->nome }}</b>@if ($outros)<a href="{{ route('painel.escolher') }}">Trocar</a>@endif</div>
        @include('painel.partes.menu', ['itensMenu' => $menu])
        <div class="menu-lateral__grupo">Configuração</div>
        @include('painel.partes.menu', ['itensMenu' => $configuracao])
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
