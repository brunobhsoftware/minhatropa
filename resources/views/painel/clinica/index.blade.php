@extends('layouts.painel')
@section('titulo', 'Clínica veterinária')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Clínica veterinária</h1><p>Eventos de saúde dos animais.</p></div>
    <a href="{{ route('painel.clinica.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Novo registro</a>
</div>

<div class="grade-4" style="margin-bottom:1.5rem">
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Total</small><br><b style="font-size:1.5rem">{{ $totais->total }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Vacinas</small><br><b style="font-size:1.5rem;color:#10b981">{{ $totais->vacinas }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Vermifugações</small><br><b style="font-size:1.5rem;color:#8b5cf6">{{ $totais->vermifugacoes }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Tratamentos</small><br><b style="font-size:1.5rem;color:#f59e0b">{{ $totais->tratamentos }}</b></div>
</div>

<section class="cartao">
    <form method="get" action="{{ route('painel.clinica') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1rem">
        <input name="busca" value="{{ request('busca') }}" placeholder="Pesquisar…" style="flex:1;min-width:150px">
        <select name="tipo" style="width:auto">
            <option value="">Todos os tipos</option>
            @foreach (\App\Models\EventoSaude::TIPOS as $k => $v)
                <option value="{{ $k }}" @selected(request('tipo') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <select name="animal_id" style="width:auto">
            <option value="">Todos os animais</option>
            @foreach ($animais as $a)
                <option value="{{ $a->id }}" @selected(request('animal_id') == $a->id)>{{ $a->nome }}</option>
            @endforeach
        </select>
        <input type="date" name="de" value="{{ request('de') }}" style="width:auto" title="De">
        <input type="date" name="ate" value="{{ request('ate') }}" style="width:auto" title="Até">
        <button class="botao botao--primario botao--pequeno">Filtrar</button>
    </form>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Data</th><th>Tipo</th><th>Título</th><th>Animal</th><th>Medicamento</th><th>Custo</th><th></th></tr></thead>
        <tbody>
        @forelse ($eventos as $e)
            <tr>
                <td style="white-space:nowrap">{{ $e->data_evento->format('d/m/Y') }}</td>
                <td><span class="selo selo--pequeno" style="background:{{ \App\Models\EventoSaude::TIPO_CORES[$e->tipo] ?? '#6b7280' }}22;color:{{ \App\Models\EventoSaude::TIPO_CORES[$e->tipo] ?? '#6b7280' }}">{{ \App\Models\EventoSaude::TIPOS[$e->tipo] ?? $e->tipo }}</span></td>
                <td><a href="{{ route('painel.clinica.ver', $e) }}"><b>{{ $e->titulo }}</b></a></td>
                <td>{{ $e->animal_nome }}</td>
                <td>{{ $e->medicamento ?? '—' }}</td>
                <td>@if($e->custo) R$ {{ number_format($e->custo, 2, ',', '.') }} @else — @endif</td>
                <td style="white-space:nowrap">
                    <a href="{{ route('painel.clinica.editar', $e) }}" class="botao botao--escuro botao--pequeno"><x-icone nome="editar" :tamanho="14" /></a>
                    <form method="post" action="{{ route('painel.clinica.excluir', $e) }}" style="display:inline" onsubmit="return confirm('Excluir &quot;{{ $e->titulo }}&quot;?')">@csrf @method('DELETE')
                        <button class="botao botao--escuro botao--pequeno" title="Excluir"><x-icone nome="lixeira" :tamanho="14" /></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="vazio"><p>Nenhum registro de saúde.</p>
                <a href="{{ route('painel.clinica.novo') }}" class="botao botao--primario botao--pequeno">Criar o primeiro</a>
            </div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
