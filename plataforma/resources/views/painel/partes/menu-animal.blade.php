{{--
    Menu de ações do animal — include compartilhado.
    Variáveis: $animal, $papel, $modo ('cadastro' ou 'ficha').
--}}
@php $modo = $modo ?? 'ficha'; @endphp
<div class="menu-animal">
    @if ($modo === 'ficha')
        <a href="{{ route('painel.animais.editar', $animal) }}" class="botao botao--primario botao--pequeno"><x-icone nome="editar" :tamanho="16" /> Editar</a>
        <a href="{{ route('painel.consulta.show', $animal) }}" class="botao botao--contorno botao--pequeno">Ocorrências</a>
        @if (\App\Support\Permissao::pode($papel, 'cobricoes'))
            <a href="{{ route('painel.cobricoes') }}?animal={{ $animal->uuid }}" class="botao botao--contorno botao--pequeno">Cobrições</a>
        @endif
        @if (\App\Support\Permissao::pode($papel, 'nascimentos'))
            <a href="{{ route('painel.nascimentos') }}?animal={{ $animal->uuid }}" class="botao botao--contorno botao--pequeno">Nascimentos</a>
        @endif
        @if (\App\Support\Permissao::pode($papel, 'transferencias'))
            <a href="{{ route('painel.transferencias') }}?animal={{ $animal->uuid }}" class="botao botao--contorno botao--pequeno">Transferências</a>
        @endif
        @if (\App\Support\Esquema::temV017() && \App\Support\Permissao::pode($papel, 'transferencias', true) && $animal->situacao === 'ativo')
            @php $anuncioAberto = \App\Models\Classificado::abertos()->where('animal_id', $animal->id)->first(); @endphp
            @if ($anuncioAberto)
                <a href="{{ route('painel.classificados.editar', $anuncioAberto) }}" class="botao botao--contorno botao--pequeno" data-a-venda>À venda · {{ moeda($anuncioAberto->preco) }}</a>
            @else
                <a href="{{ route('painel.classificados.novo', ['animal' => $animal->uuid]) }}" class="botao botao--contorno botao--pequeno" data-anunciar>Colocar à venda</a>
            @endif
        @endif
        @if (\App\Support\Permissao::pode($papel, 'animais', true))
        <form method="post" action="{{ route('painel.animais.excluir', $animal) }}" onsubmit="return confirm('Excluir este animal do plantel? Ele sai da lista (permanece no histórico).');" style="display:inline">
            @csrf @method('delete')
            <button type="submit" class="botao botao--escuro botao--pequeno"><x-icone nome="lixeira" :tamanho="16" /> Excluir</button>
        </form>
        @endif
    @endif
</div>
