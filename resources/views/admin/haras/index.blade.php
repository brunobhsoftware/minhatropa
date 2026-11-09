@extends('layouts.admin')
@section('titulo', 'Haras')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Haras</h1><p>Todos os Haras que usam a plataforma.</p></div>
    <a href="{{ route('admin.haras.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Novo Haras</a>
</div>
<form class="filtros" method="get">
    <input type="search" name="busca" value="{{ $busca }}" placeholder="Nome, CPF/CNPJ, cidade ou responsável">
    <select name="situacao"><option value="">Todas as situações</option>
        @foreach (\App\Models\Empresa::SITUACOES as $valor => $texto)<option value="{{ $valor }}" @selected($situacao === $valor)>{{ $texto }}</option>@endforeach
    </select>
    <button class="botao botao--contorno"><x-icone nome="busca" :tamanho="16" /> Filtrar</button>
</form>
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Haras</th><th>CPF/CNPJ</th><th>Cidade/UF</th><th>Responsável</th><th class="num">Usuários</th><th>Situação</th></tr></thead>
        <tbody>
        @forelse ($haras as $h)
            <tr class="clicavel" data-href="{{ route('admin.haras.show', $h) }}">
                <td><a href="{{ route('admin.haras.show', $h) }}"><b>{{ $h->nome }}</b></a>@if ($h->razao_social)<br><small class="suave">{{ $h->razao_social }}</small>@endif</td>
                <td>{{ $h->documentoFormatado() }}</td>
                <td>{{ $h->cidadeUf() }}</td>
                <td>{{ $h->responsavel_nome ?: '—' }}</td>
                <td class="num">{{ $h->usuarios_count }}</td>
                <td><span class="selo selo--{{ $h->situacao }}">{{ $h->situacaoTexto() }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="vazio"><p>{{ $busca || $situacao ? 'Nenhum Haras encontrado com esse filtro.' : 'Nenhum Haras cadastrado ainda.' }}</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
{{ $haras->links() }}
@endsection
