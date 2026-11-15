@extends('layouts.painel')
@section('titulo', $evento->titulo)
@section('conteudo')
<div class="cabecalho">
    <div>
        <a href="{{ route('painel.clinica') }}" class="suave" style="font-size:.85rem">Saúde animal ›</a>
        <h1>{{ $evento->titulo }}</h1>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('painel.clinica.editar', $evento) }}" class="botao botao--escuro botao--pequeno"><x-icone nome="editar" :tamanho="14" /> Editar</a>
    </div>
</div>

<div class="cartao">
    <div class="grade-2" style="margin-bottom:1.5rem">
        <div>
            <small class="suave">Tipo</small><br>
            <span class="selo selo--pequeno" style="background:{{ \App\Models\EventoSaude::TIPO_CORES[$evento->tipo] ?? '#6b7280' }}22;color:{{ \App\Models\EventoSaude::TIPO_CORES[$evento->tipo] ?? '#6b7280' }}">{{ $evento->tipoTexto() }}</span>
        </div>
        <div>
            <small class="suave">Animal</small><br>
            <b>{{ $evento->animal?->nome ?? '—' }}</b>
        </div>
    </div>

    <div class="grade-2" style="margin-bottom:1.5rem">
        <div><small class="suave">Data</small><br>{{ $evento->data_evento->format('d/m/Y') }}</div>
        <div><small class="suave">Próximo agendamento</small><br>{{ $evento->data_proxima ? $evento->data_proxima->format('d/m/Y') : '—' }}</div>
    </div>

    @if ($evento->descricao)
        <div style="margin-bottom:1.5rem"><small class="suave">Descrição</small><br>{!! nl2br(e($evento->descricao)) !!}</div>
    @endif

    @if ($evento->medicamento || $evento->dosagem || $evento->via_administracao || $evento->lote)
    <fieldset style="border:1px solid var(--borda);border-radius:8px;padding:1rem;margin-bottom:1.5rem">
        <legend style="font-weight:600;padding:0 .5rem">Medicamento / Produto</legend>
        <div class="grade-2">
            <div><small class="suave">Medicamento</small><br>{{ $evento->medicamento ?? '—' }}</div>
            <div><small class="suave">Dosagem</small><br>{{ $evento->dosagem ?? '—' }}</div>
            <div><small class="suave">Via de administração</small><br>{{ $evento->via_administracao ?? '—' }}</div>
            <div><small class="suave">Lote</small><br>{{ $evento->lote ?? '—' }}</div>
        </div>
    </fieldset>
    @endif

    <div class="grade-2" style="margin-bottom:1.5rem">
        <div><small class="suave">Peso do animal</small><br>{{ $evento->peso_animal ? number_format($evento->peso_animal, 2, ',', '.') . ' kg' : '—' }}</div>
        <div><small class="suave">Temperatura</small><br>{{ $evento->temperatura ? number_format($evento->temperatura, 1, ',', '.') . ' °C' : '—' }}</div>
    </div>

    <div class="grade-2" style="margin-bottom:1.5rem">
        <div><small class="suave">Custo</small><br>{{ $evento->custo ? 'R$ ' . number_format($evento->custo, 2, ',', '.') : '—' }}</div>
        <div><small class="suave">Registrado por</small><br>{{ $evento->criadoPor?->nome ?? '—' }} em {{ $evento->criado_em->format('d/m/Y H:i') }}</div>
    </div>

    @if ($evento->observacoes)
        <div><small class="suave">Observações</small><br>{!! nl2br(e($evento->observacoes)) !!}</div>
    @endif
</div>
@endsection
