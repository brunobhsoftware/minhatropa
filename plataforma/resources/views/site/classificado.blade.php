@extends('layouts.site')
@php
    $animal = $anuncio->animal; $p = $anuncio->empresa;
    $whats = $anuncio->contato_whatsapp;
    $texto = rawurlencode('Olá! Vi '.$animal->nome.' nos classificados do MinhaTropa e gostaria de mais informações.');
@endphp
@section('titulo', $animal->nome.' à venda — Classificados MinhaTropa')
@section('descricao', $animal->nome.' à venda por '.moeda($anuncio->preco).', anunciado por '.$p->nome.'.')
@section('conteudo')
<section class="secao classificados">
    <div class="secao__interno">
        <p class="classificados__voltar"><a href="{{ route('classificados') }}">‹ Todos os classificados</a></p>
        <div class="anuncio-pagina">
            <div class="anuncio-pagina__fotos">
                @forelse ($fotos as $ft)
                <img src="{{ route('classificados.foto', [$anuncio->uuid, $ft->posicao]) }}?v={{ $ft->id }}-{{ $ft->bytes }}" alt="Foto {{ $ft->posicao }} de {{ $animal->nome }}" width="900" height="1200" @if (! $loop->first) loading="lazy" @endif>
                @empty
                <div class="anuncio__foto"><span>Sem foto</span></div>
                @endforelse
            </div>
            <div class="anuncio-pagina__dados">
                <span class="sobretitulo">À venda</span>
                <h1>{{ $animal->nome }}</h1>
                <p class="anuncio-pagina__preco">{{ moeda($anuncio->preco) }}</p>
                <dl class="anuncio-pagina__ficha">
                    <div><dt>Espécie</dt><dd>{{ $animal->especieTexto() }}</dd></div>
                    @if ($animal->raca)<div><dt>Raça</dt><dd>{{ $animal->raca }}</dd></div>@endif
                    @if ($animal->sexo)<div><dt>Sexo</dt><dd>{{ $animal->sexo === 'femea' ? 'Fêmea' : 'Macho' }}</dd></div>@endif
                    @if ($animal->pelagem)<div><dt>Pelagem</dt><dd>{{ $animal->pelagemTexto() }}</dd></div>@endif
                    @if ($animal->data_nascimento)<div><dt>Idade</dt><dd>{{ $animal->idade() }}</dd></div>@endif
                    @if ($animal->registro)<div><dt>Registro</dt><dd>{{ $animal->registro }}</dd></div>@endif
                </dl>
                @if ($anuncio->descricao)<p class="anuncio-pagina__texto">{!! nl2br(e($anuncio->descricao)) !!}</p>@endif
                <div class="anuncio-pagina__origem">
                    <b>{{ $p->nome }}</b>
                    @if ($p->cidade)<span>{{ $p->cidade }}/{{ $p->uf }}</span>@endif
                    @if ($anuncio->contato_nome)<span>Falar com {{ $anuncio->contato_nome }}</span>@endif
                </div>
                @if ($whats)
                    <a class="botao botao--amarelo" href="https://wa.me/55{{ $whats }}?text={{ $texto }}" target="_blank" rel="noopener" data-contato>Falar no WhatsApp</a>
                @elseif (($p->pagina_ativa ?? false) && $p->subsite_slug)
                    <a class="botao botao--amarelo" href="{{ route('propriedade.pagina', $p->subsite_slug) }}" data-contato>Ver a página {{ $p->doRotulo() }}</a>
                @endif
                <p class="anuncio-pagina__aviso">Anúncio de responsabilidade da propriedade. A negociação é feita direto com ela.</p>
            </div>
        </div>
    </div>
</section>
@endsection
