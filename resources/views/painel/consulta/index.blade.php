@extends('layouts.painel')
@section('titulo', 'Consulta de animais')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Consulta de animais</h1><p>Pesquise qualquer animal da plataforma: do seu plantel ou de outros Haras / Fazendas.</p></div>
</div>

<form class="filtro-barra" action="{{ route('painel.consulta') }}" method="get">
    <input name="busca" value="{{ $busca }}" placeholder="Nome, registro, chip, apelido…" class="busca-campo" autofocus>
    <select name="especie">
        <option value="">Espécie</option>
        @foreach (\App\Models\Animal::ESPECIES as $k => $v)
            <option value="{{ $k }}" @selected($especie === $k)>{{ $v }}</option>
        @endforeach
    </select>
    <select name="sexo">
        <option value="">Sexo</option>
        <option value="macho" @selected($sexo === 'macho')>Macho</option>
        <option value="femea" @selected($sexo === 'femea')>Fêmea</option>
    </select>
    <button class="botao botao--primario botao--pequeno">Buscar</button>
</form>

@if ($busca === '' && ! $especie && ! $sexo)
    <div class="vazio"><p>Digite o nome, registro ou chip do animal para iniciar a consulta.</p></div>
@elseif ($animais->isEmpty())
    <div class="vazio"><p>Nenhum animal encontrado com esses critérios.</p></div>
@else
    <p class="suave" style="margin-bottom:8px">{{ $animais->count() }} resultado(s){{ $animais->count() >= 100 ? ' (limite de 100 — refine a busca)' : '' }}</p>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr>
            <th>Nome</th>
            <th>Espécie</th>
            <th>Raça</th>
            <th>Sexo</th>
            <th>Registro</th>
            <th>Chip</th>
            <th>Propriedade</th>
            <th></th>
        </tr></thead>
        <tbody>
        @foreach ($animais as $a)
            <tr>
                <td><a href="{{ route('painel.consulta.show', $a) }}"><b>{{ $a->nome }}</b></a>
                    @if ($a->empresa_id === $haras->id)<span class="selo selo--ativo" style="font-size:.7rem;margin-left:4px">Meu plantel</span>@endif
                </td>
                <td>{{ $a->especieTexto() }}</td>
                <td>{{ $a->raca ?: '—' }}</td>
                <td>{{ $a->sexo === 'macho' ? 'M' : ($a->sexo === 'femea' ? 'F' : '—') }}</td>
                <td>{{ $a->registro ?: '—' }}</td>
                <td>{{ $a->microchip ?: '—' }}</td>
                <td>{{ $a->empresa->nome ?? '—' }}</td>
                <td><a href="{{ route('painel.consulta.show', $a) }}" class="botao botao--contorno botao--pequeno">Ver ficha</a></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endif
@endsection
