@extends('layouts.painel')
@section('titulo', 'Hoje')
@section('conteudo')
<div class="cabecalho">
    <div>
        <h1>Hoje</h1>
        <p>{{ ucfirst($hoje->translatedFormat('l, d \d\e F \d\e Y')) }}</p>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('painel.agenda') }}" class="botao botao--escuro botao--pequeno"><x-icone nome="calendario" :tamanho="16" /> Agenda</a>
        <a href="{{ route('painel.rotinas.nova') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Nova rotina</a>
    </div>
</div>

<div class="grade-4" style="margin-bottom:1.5rem">
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Total do dia</small><br><b style="font-size:1.5rem">{{ $totais->total }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Concluídas</small><br><b style="font-size:1.5rem;color:var(--verde)">{{ $totais->concluidas }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Pendentes</small><br><b style="font-size:1.5rem;color:#f59e0b">{{ $totais->pendentes }}</b></div>
    <div class="cartao" style="text-align:center;padding:1rem"><small class="suave">Atrasadas</small><br><b style="font-size:1.5rem;color:var(--vermelho)">{{ $totais->atrasadas }}</b></div>
</div>

@if ($atrasadas->count())
<section class="cartao" style="border-left:4px solid var(--vermelho);margin-bottom:1.5rem">
    <h2 style="color:var(--vermelho);margin-bottom:.5rem">⚠ Atrasadas (dias anteriores)</h2>
    @foreach ($atrasadas as $e)
        <div class="agenda-card agenda-card--{{ $e->prioridade }}" style="{{ $e->tipo_cor ? 'border-left-color:'.$e->tipo_cor : '' }}">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                @if ($e->tipo_icone)<span style="font-size:1.2rem">{{ $e->tipo_icone }}</span>@endif
                <b>{{ $e->titulo }}</b>
                @if ($e->animal_nome)<span class="suave">· {{ $e->animal_nome }}</span>@endif
                <span class="suave" style="font-size:.8rem">{{ $e->data_prevista->format('d/m') }}</span>
                <span class="selo selo--atrasada selo--pequeno">Atrasada</span>
            </div>
            <form method="post" action="{{ route('painel.agenda.registrar', $e) }}" style="display:flex;gap:8px;align-items:end;margin-top:6px">
                @csrf
                <input type="hidden" name="situacao" value="concluida">
                <input type="hidden" name="voltar" value="{{ request()->fullUrl() }}">
                <input name="observacoes" placeholder="Observação (opcional)" style="flex:1;font-size:.85rem">
                <button class="botao botao--primario botao--pequeno"><x-icone nome="check" :tamanho="14" /> Concluir</button>
            </form>
        </div>
    @endforeach
</section>
@endif

<section class="cartao">
    <h2 style="margin-bottom:1rem">Tarefas de hoje</h2>
    @forelse ($execucoes as $e)
        <div class="agenda-card agenda-card--{{ $e->prioridade }}" style="{{ $e->tipo_cor ? 'border-left-color:'.$e->tipo_cor : '' }}">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                @if ($e->tipo_icone)<span style="font-size:1.2rem">{{ $e->tipo_icone }}</span>@endif
                <b>{{ $e->titulo }}</b>
                @if ($e->animal_nome)<span class="suave">· {{ $e->animal_nome }}</span>@endif
                @if ($e->hora_prevista)<span class="suave">{{ substr($e->hora_prevista, 0, 5) }}</span>@endif
                @if ($e->responsavel_nome)<span class="suave" style="font-size:.8rem">→ {{ $e->responsavel_nome }}</span>@endif
                <span class="selo selo--{{ $e->situacao }} selo--pequeno">{{ \App\Models\ExecucaoRotina::SITUACOES[$e->situacao] ?? $e->situacao }}</span>
            </div>
            @if ($e->situacao === 'pendente')
                <form method="post" action="{{ route('painel.agenda.registrar', $e) }}" style="display:flex;gap:8px;align-items:end;margin-top:6px">
                    @csrf
                    <input type="hidden" name="situacao" value="concluida">
                    <input type="hidden" name="voltar" value="{{ request()->fullUrl() }}">
                    <input name="observacoes" placeholder="Observação (opcional)" style="flex:1;font-size:.85rem">
                    <button class="botao botao--primario botao--pequeno"><x-icone nome="check" :tamanho="14" /> Concluir</button>
                    <button type="submit" name="situacao" value="cancelada" class="botao botao--escuro botao--pequeno" style="font-size:.8rem">Cancelar</button>
                </form>
            @elseif ($e->observacoes)
                <p class="suave" style="margin:.25rem 0 0;font-size:.85rem">{{ $e->observacoes }}</p>
            @endif
        </div>
    @empty
        <div class="vazio"><p>Nenhuma tarefa programada para hoje.</p>
            <a href="{{ route('painel.rotinas.nova') }}" class="botao botao--primario botao--pequeno">Criar rotina</a>
        </div>
    @endforelse
</section>
@endsection
