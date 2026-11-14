{{--
    Um bloco do menu lateral. Item sem subitens é um link; item com subitens abre a lista ao ser clicado
    (um aberto por vez). O grupo já vem aberto quando a tela atual é um dos subitens dele.
--}}
<nav data-menu-grupos>
@foreach ($itensMenu as $item)
    @continue(($item['ver'] ?? true) === false)
    @php
        $filhos = collect($item['itens'] ?? [])->filter(fn ($f) => ($f['ver'] ?? true) !== false)->values();
        $temAtivo = $filhos->contains(fn ($f) => $f['ativo'] ?? false);
        // Grupo "fechado": nada dele pode ser usado ainda (tudo em breve ou módulo não contratado).
        $fechado = $filhos->isNotEmpty() && $filhos->every(fn ($f) => ($f['breve'] ?? false) || (isset($f['modulo']) && ! $f['liberado']));
        $tudoBreve = $filhos->isNotEmpty() && $filhos->every(fn ($f) => $f['breve'] ?? false);
    @endphp
    @if ($item['breve'] ?? false)
        <a href="#" aria-disabled="true" onclick="return false" class="desativado"><x-icone nome="cadeado" /> {{ $item['texto'] }} <span class="selo-breve">em breve</span></a>
    @elseif ($filhos->isEmpty())
        @continue(! isset($item['url']))
        <a href="{{ $item['url'] }}" class="{{ ($item['ativo'] ?? false) ? 'ativo' : '' }}" @if ($item['ativo'] ?? false) aria-current="page" @endif><x-icone :nome="$item['icone']" /> {{ $item['texto'] }}</a>
    @else
        <details class="menu-grupo {{ $fechado ? 'menu-grupo--fechado' : '' }}" data-grupo="{{ \Illuminate\Support\Str::slug($item['texto']) }}" @if ($temAtivo) open @endif>
            <summary><x-icone :nome="$fechado ? 'cadeado' : $item['icone']" /> <span>{{ $item['texto'] }}</span>@if ($tudoBreve)<span class="selo-breve">em breve</span>@endif</summary>
            <div class="menu-grupo__itens">
                @foreach ($filhos as $f)
                    @if ($f['breve'] ?? false)
                        <span class="menu-sub menu-sub--breve">{{ $f['texto'] }} <span class="selo-breve">em breve</span></span>
                    @elseif (isset($f['modulo']))
                        <a href="{{ $f['url'] }}" class="menu-sub {{ $f['ativo'] ? 'ativo' : '' }} {{ $f['liberado'] ? '' : 'menu-sub--bloqueado' }}" data-modulo="{{ $f['modulo'] }}" data-liberado="{{ $f['liberado'] ? 1 : 0 }}" @if ($f['ativo']) aria-current="page" @endif>
                            {{ $f['texto'] }} @unless ($f['liberado'])<span class="selo-modulo" title="Módulo adicional"><x-icone nome="cadeado" :tamanho="12" /> Módulo adicional</span>@endunless</a>
                    @else
                        <a href="{{ $f['url'] }}" class="menu-sub {{ ($f['ativo'] ?? false) ? 'ativo' : '' }}" @if ($f['ativo'] ?? false) aria-current="page" @endif>{{ $f['texto'] }}</a>
                    @endif
                @endforeach
            </div>
        </details>
    @endif
@endforeach
</nav>
