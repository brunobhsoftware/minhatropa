@extends('layouts.painel')
@section('titulo', 'Cobrição — ' . $cobricao->egua?->nome)
@section('conteudo')
<div class="cabecalho">
    <div>
        <p class="migalha"><a href="{{ route('painel.cobricoes') }}">Cobrições</a> ›</p>
        <h1>Cobrição de {{ $cobricao->egua?->nome ?? '—' }} <span class="selo selo--{{ $cobricao->resultado }}">{{ $cobricao->resultadoTexto() }}</span></h1>
        <p>{{ $cobricao->data_cobricao->format('d/m/Y') }} · {{ $cobricao->tipoTexto() }}</p>
    </div>
    <a href="{{ route('painel.cobricoes.editar', $cobricao) }}" class="botao botao--primario"><x-icone nome="editar" :tamanho="16" /> Editar</a>
</div>

<div class="grade-2">
    <section class="cartao">
        <h2>Dados da cobrição</h2>
        <dl class="dados">
            <div><dt>Tipo</dt><dd>{{ $cobricao->tipoTexto() }}</dd></div>
            @if ($cobricao->subtipo)<div><dt>Subtipo</dt><dd>{{ \App\Models\Cobricao::SUBTIPOS[$cobricao->subtipo] ?? $cobricao->subtipo }}</dd></div>@endif
            <div><dt>Data</dt><dd>{{ $cobricao->data_cobricao->format('d/m/Y') }}</dd></div>
            @if ($cobricao->local)<div><dt>Local</dt><dd>{{ $cobricao->local }}</dd></div>@endif
            @if ($cobricao->veterinario)<div><dt>Veterinário</dt><dd>{{ $cobricao->veterinario->nome }}</dd></div>@endif
            <div><dt>Registrado em</dt><dd>{{ data_local($cobricao->criado_em) }}</dd></div>
        </dl>
    </section>

    <section class="cartao">
        <h2>Animais envolvidos</h2>
        <dl class="dados">
            <div><dt>Égua</dt><dd>@if ($cobricao->egua)<a href="{{ route('painel.animais.show', $cobricao->egua) }}"><b>{{ $cobricao->egua->nome }}</b></a>@else — @endif</dd></div>
            <div><dt>Garanhão</dt><dd>@if ($cobricao->garanhao)<a href="{{ route('painel.animais.show', $cobricao->garanhao) }}"><b>{{ $cobricao->garanhao->nome }}</b></a>@else {{ $cobricao->garanhao_nome ?? '—' }} @endif</dd></div>
            @if ($cobricao->receptora)
            <div><dt>Receptora</dt><dd><a href="{{ route('painel.animais.show', $cobricao->receptora) }}">{{ $cobricao->receptora->nome }}</a></dd></div>
            @endif
        </dl>
    </section>
</div>

<section class="cartao">
    <h2>Resultado</h2>
    <dl class="dados">
        <div><dt>Situação</dt><dd><span class="selo selo--{{ $cobricao->resultado }}">{{ $cobricao->resultadoTexto() }}</span></dd></div>
        @if ($cobricao->data_confirmacao)<div><dt>Data confirmação</dt><dd>{{ $cobricao->data_confirmacao->format('d/m/Y') }}</dd></div>@endif
        @if ($cobricao->data_prevista_parto)<div><dt>Previsão de parto</dt><dd>{{ $cobricao->data_prevista_parto->format('d/m/Y') }}</dd></div>@endif
    </dl>
</section>

@if ($cobricao->nascimento)
<section class="cartao">
    <h2>Nascimento vinculado</h2>
    <dl class="dados">
        <div><dt>Produto</dt><dd>@if ($cobricao->nascimento->animal)<a href="{{ route('painel.animais.show', $cobricao->nascimento->animal) }}"><b>{{ $cobricao->nascimento->animal->nome }}</b></a>@else {{ $cobricao->nascimento->nome_produto ?? '—' }} @endif</dd></div>
        <div><dt>Data</dt><dd>{{ $cobricao->nascimento->data_nascimento->format('d/m/Y') }}</dd></div>
        <div><dt>Sexo</dt><dd>{{ $cobricao->nascimento->sexo === 'macho' ? 'Macho' : ($cobricao->nascimento->sexo === 'femea' ? 'Fêmea' : '—') }}</dd></div>
    </dl>
    <a href="{{ route('painel.nascimentos.show', $cobricao->nascimento) }}" class="botao botao--contorno botao--pequeno" style="margin-top:.5rem">Ver nascimento</a>
</section>
@endif

@if ($cobricao->observacoes)
<section class="cartao"><h2>Observações</h2><p class="pre">{{ $cobricao->observacoes }}</p></section>
@endif
@endsection
