@extends('layouts.painel')
@section('titulo', $anuncio->exists ? 'Alterar anúncio' : 'Anunciar animal')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.classificados') }}">Classificados</a> ›</p><h1>{{ $anuncio->exists ? 'Anúncio de '.$anuncio->animal->nome : 'Anunciar animal' }}</h1></div></div>

@if (! $anuncio->exists && $animais->isEmpty())
<section class="cartao"><div class="vazio"><p>Não há animal ativo sem anúncio para colocar à venda.</p><a href="{{ route('painel.classificados') }}" class="botao botao--contorno">Voltar</a></div></section>
@else
<form method="post" action="{{ $anuncio->exists ? route('painel.classificados.salvar', $anuncio) : route('painel.classificados.criar') }}" class="formulario">
    @csrf
    @if ($anuncio->exists) @method('PUT') @endif
    <section class="cartao"><h2>Animal e preço</h2>
        <div class="grade-3">
            <label class="campo"><span>Animal à venda</span>
                <select name="animal_id" @disabled($anuncio->exists)>
                    @unless ($anuncio->exists)<option value=""></option>@endunless
                    @foreach ($animais as $a)
                    <option value="{{ $a->id }}" @selected((int) old('animal_id', $escolhido) === $a->id)>{{ $a->nome }}{{ $a->raca ? ' · '.$a->raca : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Preço (R$)</span><input name="preco" value="{{ old('preco', $anuncio->preco !== null ? number_format((float) $anuncio->preco, 2, ',', '.') : '') }}" inputmode="decimal" maxlength="16" placeholder="15.000,00" required></label>
        </div>
    </section>
    <section class="cartao"><h2>Período no ar</h2>
        <div class="grade-3">
            <label class="campo"><span>A partir de</span><input type="date" name="inicio" value="{{ old('inicio', $anuncio->inicio ? \Illuminate\Support\Carbon::parse($anuncio->inicio)->format('Y-m-d') : '') }}" required></label>
            <label class="campo"><span>Até (opcional)</span><input type="date" name="fim" value="{{ old('fim', $anuncio->fim?->format('Y-m-d')) }}"><small class="suave">Em branco, o anúncio fica no ar até você retirar.</small></label>
        </div>
    </section>
    <section class="cartao"><h2>Anúncio e contato</h2>
        <label class="campo"><span>Descrição (opcional)</span><textarea name="descricao" rows="4" maxlength="3000" placeholder="Temperamento, treino, premiações, condições de venda…">{{ old('descricao', $anuncio->descricao) }}</textarea></label>
        <div class="grade-3">
            <label class="campo"><span>Falar com</span><input name="contato_nome" value="{{ old('contato_nome', $anuncio->contato_nome) }}" maxlength="120"></label>
            <label class="campo"><span>WhatsApp para contato</span><input name="contato_whatsapp" value="{{ old('contato_whatsapp', $anuncio->contato_whatsapp) }}" inputmode="tel" maxlength="20" placeholder="(31) 99999-0000"><small class="suave">Aparece no anúncio do site, no botão de contato.</small></label>
        </div>
    </section>
    <div class="acoes-form"><a href="{{ route('painel.classificados') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">{{ $anuncio->exists ? 'Salvar anúncio' : 'Publicar anúncio' }}</button></div>
</form>
@endif
@endsection
