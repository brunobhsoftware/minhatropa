@extends('layouts.painel')
@section('titulo', $lancamento->descricao)
@section('conteudo')
<div class="cabecalho">
    <div>
        <h1>{{ $lancamento->descricao }}</h1>
        <p>
            <span class="selo selo--pequeno" style="background:{{ \App\Models\LancamentoFinanceiro::TIPO_CORES[$lancamento->tipo] ?? '#6b7280' }}22;color:{{ \App\Models\LancamentoFinanceiro::TIPO_CORES[$lancamento->tipo] ?? '#6b7280' }}">{{ \App\Models\LancamentoFinanceiro::TIPOS[$lancamento->tipo] ?? $lancamento->tipo }}</span>
            <span class="selo selo--pequeno" style="background:{{ \App\Models\LancamentoFinanceiro::SITUACAO_CORES[$lancamento->situacao] ?? '#6b7280' }}22;color:{{ \App\Models\LancamentoFinanceiro::SITUACAO_CORES[$lancamento->situacao] ?? '#6b7280' }}">{{ \App\Models\LancamentoFinanceiro::SITUACOES[$lancamento->situacao] ?? $lancamento->situacao }}</span>
        </p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('painel.financeiro.editar', $lancamento) }}" class="botao botao--primario"><x-icone nome="editar" :tamanho="16" /> Editar</a>
        <a href="{{ route('painel.financeiro') }}" class="botao botao--escuro">Voltar</a>
    </div>
</div>

<section class="cartao" style="max-width:700px">
    <table class="tabela-detalhes">
        <tr><th>Valor</th><td><b style="font-size:1.2rem;color:{{ $lancamento->tipo === 'receita' ? '#10b981' : '#ef4444' }}">R$ {{ number_format($lancamento->valor, 2, ',', '.') }}</b></td></tr>
        <tr><th>Categoria</th><td>{{ $lancamento->categoria?->nome ?? '—' }}</td></tr>
        <tr><th>Vencimento</th><td>{{ $lancamento->data_vencimento->format('d/m/Y') }}</td></tr>
        <tr><th>Data do pagamento</th><td>{{ $lancamento->data_pagamento ? $lancamento->data_pagamento->format('d/m/Y') : '—' }}</td></tr>
        <tr><th>Forma de pagamento</th><td>{{ \App\Models\LancamentoFinanceiro::FORMAS_PAGAMENTO[$lancamento->forma_pagamento] ?? '—' }}</td></tr>
        <tr><th>Animal</th><td>{{ $lancamento->animal?->nome ?? '—' }}</td></tr>
        <tr><th>Documento</th><td>{{ $lancamento->documento ?? '—' }}</td></tr>
        @if ($lancamento->observacoes)
            <tr><th>Observações</th><td style="white-space:pre-line">{{ $lancamento->observacoes }}</td></tr>
        @endif
        <tr><th>Criado por</th><td>{{ $lancamento->criadoPor?->nome ?? '—' }} em {{ data_local($lancamento->criado_em, 'd/m/Y H:i') }}</td></tr>
    </table>
</section>
@endsection
