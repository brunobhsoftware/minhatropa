@extends('layouts.painel')
@section('titulo', 'Nascimentos')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Nascimentos</h1><p>Registro de nascimentos do plantel.</p></div>
    <a href="{{ route('painel.nascimentos.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Registrar nascimento</a>
</div>
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Data</th><th>Mãe</th><th>Pai</th><th>Produto</th><th>Sexo</th><th>Tipo parto</th><th>Vivo</th><th></th></tr></thead>
        <tbody>
        @forelse ($nascimentos as $n)
            <tr>
                <td>{{ $n->data_nascimento->format('d/m/Y') }}</td>
                <td>@if ($n->mae)<a href="{{ route('painel.animais.show', $n->mae) }}"><b>{{ $n->mae->nome }}</b></a>@else — @endif</td>
                <td>{{ $n->nomePai() }}</td>
                <td>{{ $n->nome_produto ?: '—' }}</td>
                <td>{{ $n->sexo === 'macho' ? 'Macho' : ($n->sexo === 'femea' ? 'Fêmea' : ($n->sexo ?? '—')) }}</td>
                <td>{{ $n->tipoPartoTexto() }}</td>
                <td>{!! $n->vivo ? '<span class="selo selo--ativo">Sim</span>' : '<span class="selo selo--morto">Não</span>' !!}</td>
                <td><a href="{{ route('painel.nascimentos.show', $n) }}" class="botao botao--contorno botao--pequeno">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="vazio"><p>Nenhum nascimento registrado.</p><a href="{{ route('painel.nascimentos.novo') }}" class="botao botao--primario botao--pequeno">Registrar o primeiro</a></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
