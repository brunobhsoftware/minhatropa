{{-- Página pública de um Haras / Fazenda. As cores vêm do cadastro (cor principal e cor de fundo); o texto se ajusta para ficar legível. --}}
@php
    $tel = fn ($t) => preg_replace('/\D/', '', (string) $t);
    $whats = $tel($p->whatsapp);
    $logo = $p->imagemUrl('logo'); $capa = $p->imagemUrl('capa'); $marca = $p->imagemUrl('marca');
    $endereco = collect([trim($p->logradouro.' '.$p->numero), $p->bairro, $p->cidade ? $p->cidade.'/'.$p->uf : null])->filter()->implode(' · ');
    $mapa = $p->latitude && $p->longitude ? 'https://www.google.com/maps?q='.(float) $p->latitude.','.(float) $p->longitude : null;
    $descricao = $p->pagina_slogan ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', (string) $p->pagina_apresentacao)), 150) ?: $p->rotulo().' em '.$p->cidadeUf();
    $claro = $cores['texto'] === '#101828';
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $cores['primaria'] }}">
    <title>{{ $p->nome }} — {{ $p->cidadeUf() }}</title>
    <meta name="description" content="{{ $descricao }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $p->nome }}">
    <meta property="og:description" content="{{ $descricao }}">
    @if ($capa ?: $logo)<meta property="og:image" content="{{ $capa ?: $logo }}">@endif
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root{--primaria:{{ $cores['primaria'] }};--sobre-primaria:{{ $cores['sobre_primaria'] }};--fundo:{{ $cores['fundo'] }};--texto:{{ $cores['texto'] }};
            --suave:{{ $claro ? 'rgba(16,24,40,.68)' : 'rgba(255,255,255,.74)' }};--linha:{{ $claro ? 'rgba(16,24,40,.12)' : 'rgba(255,255,255,.18)' }};--cartao:{{ $claro ? 'rgba(16,24,40,.04)' : 'rgba(255,255,255,.07)' }}}
        *{box-sizing:border-box}
        body{margin:0;font:400 1rem/1.6 Inter,system-ui,sans-serif;background:var(--fundo);color:var(--texto)}
        a{color:inherit}
        .faixa{max-width:1040px;margin:0 auto;padding:0 20px}
        .capa{background:var(--primaria);color:var(--sobre-primaria);position:relative;overflow:hidden}
        .capa__imagem{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.38}
        .capa__interno{position:relative;display:flex;gap:24px;align-items:center;padding:56px 20px;flex-wrap:wrap}
        .capa__logo{flex:none;width:132px;height:132px;border-radius:20px;background:#fff;display:grid;place-items:center;padding:10px;box-shadow:0 12px 32px rgba(0,0,0,.25)}
        .capa__logo img{max-width:100%;max-height:100%;object-fit:contain}
        .capa__tipo{font-size:.78rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;opacity:.85}
        h1{font:700 clamp(1.9rem,5vw,3rem)/1.1 "Space Grotesk",sans-serif;margin:4px 0 8px;text-wrap:balance}
        .capa__slogan{font-size:1.1rem;margin:0;max-width:44ch;opacity:.95}
        .capa__local{margin:10px 0 0;font-weight:500;opacity:.9}
        .acoes{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}
        .botao{display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border-radius:999px;font-weight:600;text-decoration:none;border:2px solid transparent}
        .botao--cheio{background:var(--sobre-primaria);color:var(--primaria)}
        .botao--contorno{border-color:currentColor}
        .botao--primario{background:var(--primaria);color:var(--sobre-primaria)}
        main.faixa{padding:44px 20px 56px;display:grid;gap:36px}
        h2{font:700 1.4rem/1.2 "Space Grotesk",sans-serif;margin:0 0 12px}
        .sobre{max-width:70ch;white-space:pre-line;font-size:1.05rem}
        .etiquetas{display:flex;flex-wrap:wrap;gap:8px;margin:0;padding:0;list-style:none}
        .etiquetas li{border:1px solid var(--linha);border-radius:999px;padding:5px 14px;font-weight:500;font-size:.95rem}
        .grade{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px}
        .cartao{background:var(--cartao);border:1px solid var(--linha);border-radius:16px;padding:20px}
        .cartao h3{margin:0 0 8px;font:700 1rem "Space Grotesk",sans-serif}
        .cartao p{margin:0 0 6px}.suave{color:var(--suave)}
        .marca{display:flex;gap:16px;align-items:center;flex-wrap:wrap}
        .marca img{max-height:110px;max-width:160px;background:#fff;border-radius:12px;padding:8px}
        .lista-contato{list-style:none;margin:0;padding:0;display:grid;gap:6px}
        footer{border-top:1px solid var(--linha);padding:20px 0;font-size:.88rem;color:var(--suave);text-align:center}
        @media(max-width:560px){.capa__interno{padding:36px 20px}.capa__logo{width:96px;height:96px}}
    </style>
</head>
<body>
    <header class="capa">
        @if ($capa)<img class="capa__imagem" src="{{ $capa }}" alt="">@endif
        <div class="faixa capa__interno">
            @if ($logo)<div class="capa__logo"><img src="{{ $logo }}" alt="Logomarca {{ $p->nome }}"></div>@endif
            <div>
                <span class="capa__tipo">{{ $p->rotulo() }}</span>
                <h1>{{ $p->nome }}</h1>
                @if ($p->pagina_slogan)<p class="capa__slogan">{{ $p->pagina_slogan }}</p>@endif
                @if ($p->cidade)<p class="capa__local">{{ $p->cidade }}/{{ $p->uf }}</p>@endif
                @if ($p->pagina_mostrar_contato && ($whats || $p->email))
                <div class="acoes">
                    @if ($whats)<a class="botao botao--cheio" href="https://wa.me/55{{ $whats }}" target="_blank" rel="noopener">Falar pelo WhatsApp</a>@endif
                    @if ($p->email)<a class="botao botao--contorno" href="mailto:{{ $p->email }}">Enviar e-mail</a>@endif
                </div>
                @endif
            </div>
        </div>
    </header>

    <main class="faixa">
        @if ($p->pagina_apresentacao)
        <section><h2>Sobre {{ $p->tipo_propriedade === 'fazenda' ? 'a Fazenda' : 'o '.$p->rotulo() }}</h2><p class="sobre">{{ $p->pagina_apresentacao }}</p></section>
        @endif

        @if ($racas || $p->finalidadesArray() || $p->marchasArray())
        <section><h2>Criação</h2>
            <div class="grade">
                @if ($racas)<div class="cartao"><h3>Raças</h3><ul class="etiquetas">@foreach ($racas as $r)<li>{{ $r }}</li>@endforeach</ul></div>@endif
                @if ($p->finalidadesArray())<div class="cartao"><h3>Finalidade</h3><ul class="etiquetas">@foreach ($p->finalidadesArray() as $f)<li>{{ \App\Models\Empresa::FINALIDADES[$f] ?? $f }}</li>@endforeach</ul></div>@endif
                @if ($p->marchasArray())<div class="cartao"><h3>Marcha</h3><ul class="etiquetas">@foreach ($p->marchasArray() as $m)<li>{{ \App\Models\Empresa::MARCHAS[$m] ?? $m }}</li>@endforeach</ul></div>@endif
            </div>
        </section>
        @endif

        @if ($marca || $p->marca_descricao)
        <section><h2>Marca do criador</h2>
            <div class="marca">@if ($marca)<img src="{{ $marca }}" alt="Marca do criador">@endif @if ($p->marca_descricao)<p class="suave" style="max-width:52ch">{{ $p->marca_descricao }}</p>@endif</div>
        </section>
        @endif

        @if (($p->pagina_mostrar_endereco && ($endereco || $p->como_chegar)) || $p->pagina_mostrar_contato)
        <section><h2>Onde estamos e contato</h2>
            <div class="grade">
                @if ($p->pagina_mostrar_endereco && ($endereco || $p->como_chegar))
                <div class="cartao"><h3>Localização</h3>
                    @if ($endereco)<p>{{ $endereco }}</p>@endif
                    @if ($p->como_chegar)<p class="suave">{{ $p->como_chegar }}</p>@endif
                    @if ($mapa)<p style="margin-top:10px"><a class="botao botao--primario" href="{{ $mapa }}" target="_blank" rel="noopener">Ver no mapa</a></p>@endif
                </div>
                @endif
                @if ($p->pagina_mostrar_contato)
                <div class="cartao"><h3>Contato</h3>
                    <ul class="lista-contato">
                        @if ($p->whatsapp)<li>WhatsApp: <a href="https://wa.me/55{{ $whats }}" target="_blank" rel="noopener">{{ $p->whatsapp }}</a></li>@endif
                        @if ($p->telefone)<li>Telefone: <a href="tel:+55{{ $tel($p->telefone) }}">{{ $p->telefone }}</a></li>@endif
                        @if ($p->email)<li>E-mail: <a href="mailto:{{ $p->email }}">{{ $p->email }}</a></li>@endif
                        @if ($p->site_url)<li>Site: <a href="{{ $p->site_url }}" target="_blank" rel="noopener nofollow">{{ preg_replace('#^https?://#', '', rtrim($p->site_url, '/')) }}</a></li>@endif
                        @if ($p->instagram)<li>Instagram: <a href="https://www.instagram.com/{{ $p->instagram }}" target="_blank" rel="noopener nofollow">{{ '@'.$p->instagram }}</a></li>@endif
                        @if ($p->facebook)<li><a href="{{ $p->facebook }}" target="_blank" rel="noopener nofollow">Facebook</a></li>@endif
                    </ul>
                </div>
                @endif
            </div>
        </section>
        @endif
    </main>
    <footer><div class="faixa">{{ $p->nome }} · Página criada com <a href="{{ route('inicio') }}">MinhaTropa</a></div></footer>
</body>
</html>
