<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101828">
    <title>@yield('titulo', 'MinhaTropa — Gestão inteligente para Haras')</title>
    <meta name="description" content="@yield('descricao', 'Sistema completo para a gestão de Haras: cadastro de animais, agenda e rotinas, saúde veterinária, reprodução e genética, treinos, custos e relatórios.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="MinhaTropa">
    <meta property="og:title" content="@yield('titulo', 'MinhaTropa — Gestão inteligente para Haras')">
    <meta property="og:description" content="Organize sua tropa: animais, rotinas, saúde, reprodução, treinos e financeiro do seu Haras em um só lugar.">
    <meta property="og:image" content="{{ asset('img/og-minhatropa.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}">
</head>
<body>
    <a class="pular" href="#conteudo">Pular para o conteúdo</a>
    <header class="topo" id="topo">
        <div class="topo__interno">
            <a href="{{ route('inicio') }}" class="topo__marca" aria-label="MinhaTropa — página inicial">
                <img src="{{ asset('img/logo-minhatropa-branca.png') }}" alt="MinhaTropa — Gestão inteligente para Haras" width="230" height="51">
            </a>
            <button type="button" class="topo__menu" data-menu aria-controls="menu-site" aria-expanded="false" aria-label="Abrir menu">
                <span></span><span></span><span></span>
            </button>
            <nav class="topo__nav" id="menu-site" aria-label="Menu principal">
                <a href="{{ route('inicio') }}#solucoes">Soluções</a>
                <a href="{{ route('inicio') }}#recursos">Recursos</a>
                <a href="{{ route('inicio') }}#implantacao">Implantação</a>
                <a href="{{ route('inicio') }}#contato">Contato</a>
                <span class="topo__acoes">
                    <a href="{{ route('entrar') }}" class="botao botao--contorno-claro"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg> Entrar</a>
                    <a href="{{ route('cadastro') }}" class="botao botao--amarelo">Cadastre seu Haras <span aria-hidden="true">→</span></a>
                </span>
            </nav>
        </div>
    </header>

    <main id="conteudo">
        @yield('conteudo')
    </main>

    <footer class="rodape">
        <div class="rodape__interno">
            <div class="rodape__marca">
                <img src="{{ asset('img/logo-minhatropa-branca.png') }}" alt="MinhaTropa" width="220" height="49" loading="lazy">
                <p>O sistema completo para organizar sua tropa e toda a rotina do seu Haras.</p>
            </div>
            <nav aria-label="Rodapé">
                <h2>Plataforma</h2>
                <a href="{{ route('inicio') }}#solucoes">Soluções</a>
                <a href="{{ route('inicio') }}#recursos">Recursos</a>
                <a href="{{ route('inicio') }}#implantacao">Implantação</a>
            </nav>
            <nav aria-label="Acesso">
                <h2>Acesso</h2>
                <a href="{{ route('inicio') }}#contato">Fale conosco</a>
                <a href="mailto:atendimento@minhatropa.com.br">atendimento@minhatropa.com.br</a>
                <a href="{{ route('cadastro') }}">Cadastre seu Haras</a>
                <a href="{{ route('entrar') }}">Entrar</a>
            </nav>
        </div>
        <div class="rodape__base">
            <span>© {{ date('Y') }} MinhaTropa · minhatropa.com.br</span>
            <span>Desenvolvido por BH Software</span>
        </div>
    </footer>
    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}" defer></script>
</body>
</html>
