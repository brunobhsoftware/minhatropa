@extends('layouts.painel')
@section('titulo', $nascimento->nome_produto ?: 'Nascimento #'.$nascimento->id)
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.nascimentos') }}">Nascimentos</a> ›</p><h1>{{ $nascimento->nome_produto ?: 'Nascimento #'.$nascimento->id }}</h1></div></div>

<div class="grade-2">
    <section class="cartao">
        <h2>Dados do nascimento</h2>
        <dl class="dados">
            <div><dt>Data</dt><dd>{{ $nascimento->data_nascimento->format('d/m/Y H:i') }}</dd></div>
            <div><dt>Tipo de parto</dt><dd>{{ $nascimento->tipoPartoTexto() }}</dd></div>
            <div><dt>Sexo</dt><dd>{{ $nascimento->sexo === 'macho' ? 'Macho' : ($nascimento->sexo === 'femea' ? 'Fêmea' : ($nascimento->sexo ?? '—')) }}</dd></div>
            <div><dt>Peso ao nascer</dt><dd>{{ $nascimento->peso_nascimento ? $nascimento->peso_nascimento.' kg' : '—' }}</dd></div>
            <div><dt>Nasceu vivo</dt><dd>{!! $nascimento->vivo ? '<span class="selo selo--ativo">Sim</span>' : '<span class="selo selo--morto">Não</span>' !!}</dd></div>
            <div><dt>Registrado em</dt><dd>{{ data_local($nascimento->criado_em) }}</dd></div>
        </dl>
    </section>
    <section class="cartao">
        <h2>Genealogia</h2>
        <dl class="dados">
            <div><dt>Mãe</dt><dd>@if ($nascimento->mae)<a href="{{ route('painel.animais.show', $nascimento->mae) }}"><b>{{ $nascimento->mae->nome }}</b></a>@else — @endif</dd></div>
            <div><dt>Pai</dt><dd>@if ($nascimento->pai)<a href="{{ route('painel.animais.show', $nascimento->pai) }}"><b>{{ $nascimento->pai->nome }}</b></a>@else {{ $nascimento->pai_nome ?: '—' }} @endif</dd></div>
            @if ($nascimento->cobricao)
            <div><dt>Cobrição</dt><dd><a href="{{ route('painel.cobricoes.show', $nascimento->cobricao) }}">{{ $nascimento->cobricao->data_cobricao->format('d/m/Y') }} — {{ $nascimento->cobricao->egua?->nome }}</a></dd></div>
            @endif
            @if ($nascimento->veterinario)
            <div><dt>Veterinário</dt><dd>{{ $nascimento->veterinario->nome }}</dd></div>
            @endif
        </dl>
    </section>
</div>

@if ($nascimento->animal)
<section class="cartao">
    <h2>Animal cadastrado</h2>
    <p><a href="{{ route('painel.animais.show', $nascimento->animal) }}" class="botao botao--contorno botao--pequeno">{{ $nascimento->animal->nome }}</a></p>
</section>
@endif

@if ($nascimento->observacoes)
<section class="cartao"><h2>Observações</h2><p class="pre">{{ $nascimento->observacoes }}</p></section>
@endif
@endsection
