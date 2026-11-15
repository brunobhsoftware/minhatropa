@extends('layouts.admin')
@section('titulo', 'E-mails enviados')
@section('conteudo')
<div class="cabecalho">
    <div><h1>E-mails enviados</h1><p>Últimos e-mails da plataforma: conferir se saiu ou por que falhou.</p></div>
</div>

<form class="filtro-barra" action="{{ route('admin.emails') }}" method="get">
    <input name="busca" value="{{ $busca }}" placeholder="Buscar por destinatário…" class="busca-campo">
    <select name="status" onchange="this.form.submit()">
        <option value="">Todos os status</option>
        @foreach (['pendente', 'enviando', 'enviado', 'erro'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button class="botao botao--primario botao--pequeno">Buscar</button>
</form>

@if ($envios->isEmpty())
    <div class="vazio"><p>Nenhum e-mail encontrado.</p></div>
@else
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Tipo</th><th>Destinatário</th><th>Assunto</th><th>Status</th><th>Enviado em</th><th>Criado em</th></tr></thead>
        <tbody>
        @foreach ($envios as $e)
            <tr>
                <td><small>{{ $e->tipo }}</small></td>
                <td>{{ $e->destinatario }}</td>
                <td>{{ $e->assunto }}</td>
                <td>
                    @if ($e->status === 'enviado') <span class="selo selo--verde">Enviado</span>
                    @elseif ($e->status === 'erro') <span class="selo selo--vermelho" title="{{ $e->erro }}">Erro</span>
                    @elseif ($e->status === 'enviando') <span class="selo selo--amarelo">Enviando</span>
                    @else <span class="selo selo--cinza">Pendente</span>
                    @endif
                </td>
                <td>{{ $e->enviado_em ? data_local($e->enviado_em) : '—' }}</td>
                <td>{{ data_local($e->criado_em) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
@endif
@endsection
