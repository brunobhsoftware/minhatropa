@extends('layouts.painel')
@section('titulo', 'Meu Plantel')
@section('conteudo')
@php $v5 = \App\Support\Esquema::temV005(); @endphp
<div class="cabecalho">
    <div><h1>Meu Plantel</h1><p>Animais {{ $haras->doRotulo() }}.@if ($limite !== null) <b>{{ $ativosPlantel }} de {{ $limite }}</b> animais ativos da faixa {{ $faixa?->nome }}.@endif</p></div>
    <div class="acoes-cabecalho">
        <a href="{{ route('painel.ocorrencias') }}" class="botao botao--contorno"><x-icone nome="historico" :tamanho="18" /> Ocorrências</a>
        @if (\App\Support\Permissao::pode($papel, 'animais', true))
        {{-- No limite da faixa o botão continua aqui: o clique mostra o aviso com a oferta de upgrade. --}}
        <a href="{{ route('painel.animais.novo') }}" class="botao {{ $podeCadastrar ? 'botao--primario' : 'botao--escuro' }}"><x-icone nome="mais" :tamanho="18" /> Cadastrar animal</a>
        <a href="{{ route('painel.animais.importar') }}" class="botao botao--contorno"><x-icone nome="banco" :tamanho="18" /> Importar tropa</a>
        @endif
    </div>
</div>

@if ($totais && $totais->total > 0)
<div class="grade-3" style="margin-bottom:1.5rem">
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Total</small><br><b style="font-size:1.5rem">{{ $totais->total }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Ativos</small><br><b style="font-size:1.5rem;color:var(--verde)">{{ $totais->ativos ?? 0 }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Machos / Fêmeas</small><br><b style="font-size:1.5rem">{{ $totais->machos ?? 0 }} / {{ $totais->femeas ?? 0 }}</b></div>
</div>
@endif

<section class="cartao">
    <form method="get" action="{{ route('painel.animais') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1rem">
        <input name="busca" value="{{ request('busca') }}" placeholder="Pesquisar por nome, registro, microchip…" style="flex:1;min-width:180px">
        @if ($v5)
        <select name="especie" style="width:auto"><option value="">Todas as espécies</option>@foreach (\App\Models\Animal::ESPECIES as $k => $t)<option value="{{ $k }}" @selected(request('especie') === $k)>{{ $t }}</option>@endforeach</select>
        <select name="situacao" style="width:auto"><option value="">Todas as situações</option>@foreach (\App\Models\Animal::SITUACOES as $k => $t)<option value="{{ $k }}" @selected(request('situacao') === $k)>{{ $t }}</option>@endforeach</select>
        @endif
        <select name="categoria" style="width:auto"><option value="">Todas as categorias</option>@foreach (\App\Models\Animal::CATEGORIAS as $k => $t)<option value="{{ $k }}" @selected(request('categoria') === $k)>{{ $t }}</option>@endforeach</select>
        <button class="botao botao--primario botao--pequeno">Filtrar</button>
    </form>
    @php
        $podeEditar = \App\Support\Permissao::pode($papel, 'animais', true);
        $colspan = ($v5 ? 7 : 5) + ($podeEditar ? 1 : 0);
    @endphp
    <form method="post" action="{{ route('painel.animais.excluir-varios') }}" data-tropa onsubmit="return confirmarExclusaoTropa(this);">
        @csrf
        @if ($podeEditar)
        <div class="barra-selecao" data-barra hidden>
            <span><b data-contagem>0</b> selecionado(s)</span>
            <button type="submit" class="botao botao--escuro botao--pequeno"><x-icone nome="lixeira" :tamanho="16" /> Excluir selecionados</button>
            <button type="button" class="botao botao--contorno botao--pequeno" data-limpar>Limpar</button>
        </div>
        @endif
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr>@if ($podeEditar)<th style="width:34px"><input type="checkbox" data-marcar-todos aria-label="Selecionar todos"></th>@endif<th>Nome</th>@if($v5)<th>Espécie</th>@endif<th>Categoria</th><th>Raça</th><th>Pelagem</th><th>Idade</th>@if($v5)<th>Situação</th>@endif</tr></thead>
            <tbody>
            @forelse ($animais as $a)
                <tr>
                    @if ($podeEditar)<td><input type="checkbox" name="uuids[]" value="{{ $a->uuid }}" data-item aria-label="Selecionar {{ $a->nome }}"></td>@endif
                    <td><span class="animal-nome">@php $ft = $fotos->get($a->id); @endphp
                        @if ($ft)<img class="animal-mini" src="{{ route('painel.animais.foto', [$a, $ft->posicao]) }}?v={{ $ft->id }}-{{ $ft->bytes }}" alt="" width="36" height="48" loading="lazy" data-mini>@elseif (\App\Support\FotosAnimal::disponivel())<span class="animal-mini animal-mini--vazia" aria-hidden="true"></span>@endif
                        @if($v5)<a href="{{ route('painel.animais.show', $a) }}"><b>{{ $a->nome }}</b></a>@else <b>{{ $a->nome }}</b>@endif</span></td>
                    @if($v5)<td>{{ $a->especieTexto() }}</td>@endif
                    <td>{{ $a->categoriaTexto() }}</td>
                    <td>{{ $a->raca ?: '—' }}</td>
                    <td>@if($v5){{ $a->pelagemTexto() }}@else{{ $a->pelagem ?: '—' }}@endif</td>
                    <td>{{ $a->data_nascimento ? \Carbon\Carbon::parse($a->data_nascimento)->age . ' anos' : '—' }}</td>
                    @if($v5)<td><span class="selo selo--{{ $a->situacao }}">{{ $a->situacaoTexto() }}</span></td>@endif
                </tr>
            @empty
                <tr><td colspan="{{ $colspan }}"><div class="vazio"><p>Nenhum animal cadastrado.</p>@if ($podeCadastrar && $podeEditar)<a href="{{ route('painel.animais.novo') }}" class="botao botao--primario botao--pequeno">Cadastrar o primeiro</a>@endif</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </form>
</section>
@if ($podeEditar)
<style>
    .barra-selecao { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin-bottom:.75rem; padding:.5rem .75rem; background:var(--cinza-claro,#f3f4f6); border-radius:8px; }
    .tabela td input[type=checkbox], .tabela th input[type=checkbox] { width:18px; height:18px; }
</style>
<script>
function confirmarExclusaoTropa(form){
    var n = form.querySelectorAll('[data-item]:checked').length;
    if(!n){ alert('Selecione ao menos um animal para excluir.'); return false; }
    return confirm('Excluir ' + n + (n>1 ? ' animais' : ' animal') + ' do plantel? Eles saem da lista (permanecem no histórico).');
}
(function(){
    var form = document.querySelector('[data-tropa]');
    if(!form) return;
    var todos = form.querySelector('[data-marcar-todos]');
    var itens = Array.prototype.slice.call(form.querySelectorAll('[data-item]'));
    var barra = form.querySelector('[data-barra]');
    var contagem = form.querySelector('[data-contagem]');
    function atualiza(){
        var n = itens.filter(function(c){ return c.checked; }).length;
        if(contagem) contagem.textContent = n;
        if(barra) barra.hidden = (n === 0);
        if(todos) todos.checked = (n > 0 && n === itens.length);
    }
    if(todos) todos.addEventListener('change', function(){ itens.forEach(function(c){ c.checked = todos.checked; }); atualiza(); });
    itens.forEach(function(c){ c.addEventListener('change', atualiza); });
    var limpar = form.querySelector('[data-limpar]');
    if(limpar) limpar.addEventListener('click', function(){ itens.forEach(function(c){ c.checked = false; }); if(todos) todos.checked = false; atualiza(); });
})();
</script>
@endif
@if ($sociedades->isNotEmpty())
<section class="cartao" style="margin-top:1.5rem" data-sociedades>
    <h2>Animais em sociedade em outras propriedades</h2>
    <p class="suave" style="margin-bottom:.75rem">{{ $haras->nome }} tem parte nestes animais, cadastrados em outra propriedade. Eles não contam no seu plantel.</p>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Animal</th><th>Onde está cadastrado</th><th class="num">Nossa parte</th></tr></thead>
        <tbody>
        @foreach ($sociedades as $s)
            <tr><td><b>{{ $s->nome }}</b> <small class="suave">{{ $s->raca }}</small></td><td>{{ $s->propriedade }} <small class="suave">{{ $s->cidade ? $s->cidade.'/'.$s->uf : '' }}</small></td>
                <td class="num"><b>{{ \App\Support\Sociedade::pct($s->percentual) }}</b></td></tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif
@endsection
