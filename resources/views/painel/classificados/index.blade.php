@extends('layouts.painel')
@section('titulo', 'Classificados')
@section('conteudo')
@php $podeAnunciar = \App\Support\Permissao::pode($papel, 'transferencias', true); @endphp
<div class="cabecalho">
    <div><p class="migalha">Comércio ›</p><h1>Classificados</h1><p>Animais {{ $haras->doRotulo() }} à venda. Enquanto o anúncio está no ar, o animal aparece nos <a href="{{ route('classificados') }}" target="_blank" rel="noopener">classificados do site</a>.</p></div>
    @if ($podeAnunciar)<a href="{{ route('painel.classificados.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Anunciar animal</a>@endif
</div>

<section class="cartao">
    <div class="cartao__titulo"><h2>Anúncios</h2><span class="selo selo--{{ $noAr ? 'ativo' : 'teste' }}">{{ $noAr }} no ar</span></div>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Animal</th><th class="num">Preço</th><th>Período</th><th>Situação</th><th></th></tr></thead>
        <tbody>
            @forelse ($anuncios as $a)
            @php $ft = $fotos->get($a->animal_id); $estado = $a->estado(); @endphp
            <tr data-anuncio="{{ $a->animal->nome }}" data-estado="{{ $estado }}">
                <td><span class="animal-nome">
                    @if ($ft)<img class="animal-mini" src="{{ route('painel.animais.foto', [$a->animal, $ft->posicao]) }}?v={{ $ft->id }}-{{ $ft->bytes }}" alt="" width="36" height="48" loading="lazy">@else<span class="animal-mini animal-mini--vazia" aria-hidden="true"></span>@endif
                    <span><a href="{{ route('painel.animais.show', $a->animal) }}"><b>{{ $a->animal->nome }}</b></a><br><small class="suave">{{ $a->animal->raca ?: 'Sem raça' }} · {{ $a->animal->categoriaTexto() }}</small></span></span></td>
                <td class="num"><b>{{ moeda($a->preco) }}</b></td>
                <td>{{ $a->periodoTexto() }}</td>
                <td><span class="selo selo--{{ ['no_ar' => 'ativo', 'agendado' => 'teste', 'vencido' => 'suspenso', 'vendido' => 'encerrado', 'encerrado' => 'encerrado'][$estado] }}">{{ $a->estadoTexto() }}</span></td>
                <td style="white-space:nowrap">
                    @if ($estado === 'no_ar')<a href="{{ route('classificados.ver', $a->uuid) }}" target="_blank" rel="noopener">Ver no site</a> · @endif
                    @if ($podeAnunciar)
                        <a href="{{ route('painel.classificados.editar', $a) }}">{{ in_array($estado, ['no_ar', 'agendado'], true) ? 'Alterar' : 'Anunciar de novo' }}</a>
                        @if (in_array($estado, ['no_ar', 'agendado'], true))
                        <form method="post" action="{{ route('painel.classificados.encerrar', $a) }}" style="display:inline">@csrf @method('PUT')
                            · <button class="link" name="motivo" value="vendido">Vendido</button> · <button class="link" name="motivo" value="encerrado">Retirar</button>
                        </form>
                        @endif
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="vazio"><p>Nenhum animal anunciado.</p>@if ($podeAnunciar)<a href="{{ route('painel.classificados.novo') }}" class="botao botao--primario">Anunciar o primeiro animal</a>@endif</div></td></tr>
            @endforelse
        </tbody>
    </table></div>
</section>
@endsection
