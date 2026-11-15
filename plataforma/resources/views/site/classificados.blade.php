@extends('layouts.site')
@section('titulo', 'Classificados — animais à venda | MinhaTropa')
@section('descricao', 'Animais à venda anunciados pelos Haras e Fazendas do MinhaTropa.')
@section('conteudo')
<section class="secao classificados">
    <div class="secao__interno">
        <span class="sobretitulo">Classificados</span>
        <h1 class="secao__titulo">Animais à venda</h1>
        <p class="secao__intro">Anunciados pelos Haras e Fazendas que usam o MinhaTropa. O contato é direto com a propriedade.</p>

        <form method="get" action="{{ route('classificados') }}" class="classificados__filtro">
            <input name="busca" value="{{ request('busca') }}" placeholder="Nome ou raça" aria-label="Nome ou raça">
            <select name="sexo" aria-label="Sexo"><option value="">Macho e fêmea</option><option value="macho" @selected(request('sexo') === 'macho')>Macho</option><option value="femea" @selected(request('sexo') === 'femea')>Fêmea</option></select>
            <select name="uf" aria-label="Estado"><option value="">Todos os estados</option>@foreach ($ufs as $uf)<option value="{{ $uf }}" @selected(request('uf') === $uf)>{{ $uf }}</option>@endforeach</select>
            <button class="botao botao--amarelo">Filtrar</button>
        </form>

        @if ($anuncios->isEmpty())
            <p class="classificados__vazio">{{ request()->hasAny(['busca', 'sexo', 'uf']) ? 'Nenhum animal encontrado com esse filtro.' : 'Ainda não há animais anunciados. Volte em breve.' }}</p>
        @else
        <div class="classificados__grade">
            @foreach ($anuncios as $a)
            @php $ft = $fotos->get($a->animal_id); @endphp
            <a class="anuncio" href="{{ route('classificados.ver', $a->uuid) }}" data-anuncio="{{ $a->animal->nome }}">
                <span class="anuncio__foto">@if ($ft)<img src="{{ route('classificados.foto', [$a->uuid, $ft->posicao]) }}?v={{ $ft->id }}-{{ $ft->bytes }}" alt="{{ $a->animal->nome }}" loading="lazy" width="900" height="1200">@else<span>Sem foto</span>@endif</span>
                <b class="anuncio__nome">{{ $a->animal->nome }}</b>
                <span class="anuncio__dados">{{ collect([$a->animal->raca, $a->animal->sexo === 'femea' ? 'Fêmea' : ($a->animal->sexo === 'macho' ? 'Macho' : null), $a->animal->data_nascimento ? $a->animal->idade() : null])->filter()->implode(' · ') }}</span>
                <b class="anuncio__preco">{{ moeda($a->preco) }}</b>
                <span class="anuncio__origem">{{ $a->empresa->nome }}{{ $a->empresa->cidade ? ' · '.$a->empresa->cidade.'/'.$a->empresa->uf : '' }}</span>
            </a>
            @endforeach
        </div>
        @if ($paginas && $paginas->hasPages())
        <nav class="classificados__paginas" aria-label="Páginas">
            @if ($paginas->previousPageUrl())<a href="{{ $paginas->previousPageUrl() }}" class="botao botao--contorno">Anteriores</a>@endif
            <span>Página {{ $paginas->currentPage() }} de {{ $paginas->lastPage() }}</span>
            @if ($paginas->nextPageUrl())<a href="{{ $paginas->nextPageUrl() }}" class="botao botao--contorno">Próximos</a>@endif
        </nav>
        @endif
        @endif
    </div>
</section>
@endsection
