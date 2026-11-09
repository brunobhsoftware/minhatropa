@extends('layouts.admin')
@section('titulo', 'Auditoria')
@section('conteudo')
<div class="cabecalho"><div><h1>Auditoria</h1><p>Tudo o que foi feito na plataforma, por quem e quando.</p></div></div>
<form class="filtros" method="get">
    <input type="search" name="busca" value="{{ $busca }}" placeholder="Buscar por ação ou tabela (ex.: haras)">
    <button class="botao botao--contorno"><x-icone nome="busca" :tamanho="16" /> Buscar</button>
</form>
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Quando</th><th>Quem</th><th>Ação</th><th>Registro</th><th>IP</th></tr></thead>
        <tbody>
        @forelse ($registros as $r)
            <tr><td>{{ data_local($r->criado_em) }}</td><td>{{ $r->usuario?->nome ?? '—' }}</td><td><code>{{ $r->acao }}</code></td><td>{{ $r->tabela }} #{{ $r->registro_id }}</td><td><small>{{ $r->ip }}</small></td></tr>
        @empty
            <tr><td colspan="5"><div class="vazio"><p>Nada registrado ainda.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
{{ $registros->links() }}
@endsection
