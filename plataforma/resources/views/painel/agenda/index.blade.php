@extends('layouts.painel')
@section('titulo', 'Agenda')
@section('conteudo')
@php
    $diasSemana = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
    $hoje = \Carbon\Carbon::today()->toDateString();
    if ($visao === 'mes') {
        $anterior = $dataRef->copy()->subMonth()->format('Y-m-d');
        $proximo = $dataRef->copy()->addMonth()->format('Y-m-d');
        $titulo = ucfirst($dataRef->translatedFormat('F Y'));
    } else {
        $anterior = $dataRef->copy()->subWeek()->format('Y-m-d');
        $proximo = $dataRef->copy()->addWeek()->format('Y-m-d');
        $titulo = $inicio->format('d/m') . ' — ' . $fim->format('d/m/Y');
    }
@endphp
<div class="cabecalho">
    <div><h1>Agenda e rotinas</h1><p>{{ $titulo }}</p></div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('painel.agenda.hoje') }}" class="botao botao--escuro botao--pequeno">Hoje</a>
        <a href="{{ route('painel.rotinas') }}" class="botao botao--escuro botao--pequeno">Ver rotinas</a>
        <a href="{{ route('painel.rotinas.nova') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Nova rotina</a>
    </div>
</div>

<div class="cartao" style="padding:0;overflow:hidden">
    <div class="calendario__navegacao">
        <a href="{{ route('painel.agenda', ['data' => $anterior, 'visao' => $visao]) }}" class="botao botao--escuro botao--pequeno">‹ Anterior</a>
        <div style="display:flex;gap:4px">
            <a href="{{ route('painel.agenda', ['data' => $dataRef->format('Y-m-d'), 'visao' => 'semana']) }}" class="botao botao--pequeno {{ $visao === 'semana' ? 'botao--primario' : 'botao--escuro' }}">Semana</a>
            <a href="{{ route('painel.agenda', ['data' => $dataRef->format('Y-m-d'), 'visao' => 'mes']) }}" class="botao botao--pequeno {{ $visao === 'mes' ? 'botao--primario' : 'botao--escuro' }}">Mês</a>
        </div>
        <a href="{{ route('painel.agenda', ['data' => $proximo, 'visao' => $visao]) }}" class="botao botao--escuro botao--pequeno">Próximo ›</a>
    </div>

    <div class="calendario calendario--{{ $visao }}">
        <div class="calendario__cabecalho">
            @foreach ($diasSemana as $ds)
                <div class="calendario__dia-nome">{{ $ds }}</div>
            @endforeach
        </div>
        <div class="calendario__corpo">
            @foreach ($dias as $chave => $dia)
                @php
                    $ehHoje = $chave === $hoje;
                    $ehMesAtual = $dia['data']->month === $dataRef->month;
                    $execs = $dia['execucoes'];
                @endphp
                <div class="calendario__dia {{ $ehHoje ? 'calendario__dia--hoje' : '' }} {{ ! $ehMesAtual && $visao === 'mes' ? 'calendario__dia--fora' : '' }}">
                    <div class="calendario__dia-numero">{{ $dia['data']->day }}</div>
                    @if ($visao === 'semana')
                        @foreach ($execs as $e)
                            <div class="agenda-card agenda-card--{{ $e->prioridade }}" style="{{ $e->tipo_cor ? 'border-left-color:'.$e->tipo_cor : '' }}">
                                <div class="agenda-card__hora">{{ $e->hora_prevista ? substr($e->hora_prevista, 0, 5) : '' }}</div>
                                <div class="agenda-card__titulo">
                                    @if ($e->tipo_icone)<span>{{ $e->tipo_icone }}</span>@endif
                                    {{ $e->titulo }}
                                </div>
                                @if ($e->animal_nome)<div class="agenda-card__animal">{{ $e->animal_nome }}</div>@endif
                                <span class="selo selo--{{ $e->situacao }} selo--pequeno">{{ \App\Models\ExecucaoRotina::SITUACOES[$e->situacao] ?? $e->situacao }}</span>
                                @if ($e->situacao === 'pendente' || $e->situacao === 'atrasada')
                                    <form method="post" action="{{ route('painel.agenda.registrar', $e) }}" style="margin-top:4px">
                                        @csrf
                                        <input type="hidden" name="situacao" value="concluida">
                                        <input type="hidden" name="voltar" value="{{ request()->fullUrl() }}">
                                        <button class="botao botao--primario botao--pequeno" style="font-size:.7rem;padding:2px 8px"><x-icone nome="check" :tamanho="12" /> Concluir</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    @else
                        @if ($execs->count())
                            <div class="calendario__resumo">
                                @php
                                    $pendentes = $execs->whereIn('situacao', ['pendente','atrasada'])->count();
                                    $concluidas = $execs->where('situacao', 'concluida')->count();
                                @endphp
                                @if ($pendentes)<span class="calendario__ponto" style="background:#f59e0b" title="{{ $pendentes }} pendente(s)">{{ $pendentes }}</span>@endif
                                @if ($concluidas)<span class="calendario__ponto" style="background:#22c55e" title="{{ $concluidas }} concluída(s)">{{ $concluidas }}</span>@endif
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
