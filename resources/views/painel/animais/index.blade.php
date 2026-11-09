@extends('layouts.painel')
@section('titulo', 'Animais')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Animais</h1><p>O plantel do {{ $haras->nome }}.</p></div>
    @if ($podeCadastrar)
        <a href="{{ route('painel.animais.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Cadastrar animal</a>
    @else
        <a href="{{ route('painel.licenca') }}" class="botao botao--escuro">🔒 Contratar para cadastrar mais</a>
    @endif
</div>
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Nome</th><th>Categoria</th><th>Raça</th><th>Pelagem</th><th>Idade</th><th>Registro</th></tr></thead>
        <tbody>
        @forelse ($animais as $a)
            <tr class="clicavel" data-href="{{ route('painel.animais.editar', $a) }}"><td><a href="{{ route('painel.animais.editar', $a) }}"><b>{{ $a->nome }}</b></a></td>
                <td>{{ \App\Models\Animal::CATEGORIAS[$a->categoria] ?? '—' }}</td><td>{{ $a->raca ?: '—' }}</td><td>{{ $a->pelagem ?: '—' }}</td><td>{{ $a->idade() }}</td><td>{{ $a->registro ?: '—' }}</td></tr>
        @empty
            <tr><td colspan="6"><div class="vazio"><p>Nenhum animal cadastrado ainda. O primeiro é grátis!</p><a href="{{ route('painel.animais.novo') }}" class="botao botao--primario botao--pequeno">Cadastrar o primeiro</a></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
