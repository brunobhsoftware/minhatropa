@extends('layouts.painel')
@section('titulo', 'Plano e módulos')
@section('conteudo')
@php $cheio = $limite !== null && $totalAnimais >= $limite; @endphp
<div class="cabecalho"><div><h1>Plano e módulos</h1><p>A faixa do plano acompanha o tamanho do plantel. Os módulos adicionais são contratados à parte, quando você quiser.</p></div></div>

<section class="cartao cartao--destaque">
    <div class="cartao__titulo"><h2>Sua faixa: {{ $faixa?->nome ?? 'Plano gratuito' }}</h2><span class="selo selo--{{ $licenca ? 'ativo' : 'teste' }}">{{ $faixa?->valorTexto() ?? 'Grátis' }}</span></div>
    @if ($limite === null)
        <p>{{ $haras->nome }} tem <b>{{ $totalAnimais }}</b> animais ativos e está em uma faixa sem limite de plantel.</p>
    @else
        <p>{{ $haras->nome }} tem <b>{{ $totalAnimais }}</b> de <b>{{ $limite }}</b> animais ativos desta faixa.</p>
        <div class="uso-plantel {{ $cheio ? 'uso-plantel--cheio' : '' }}"><span style="width:{{ min(100, $limite ? round($totalAnimais / $limite * 100) : 0) }}%"></span></div>
        <p class="suave"><small>Contam os animais ativos no plantel. Vendidos, transferidos e mortos ficam no histórico e não ocupam vaga.</small></p>
    @endif
    @if ($solicitada)
        <div class="alerta alerta--aviso"><p>Você pediu a mudança para <b>{{ $solicitada->nome }}</b> ({{ $solicitada->faixaTexto() }}). Nossa equipe entra em contato para liberar.</p></div>
    @endif
</section>

<section class="cartao">
    <h2>Faixas de plantel</h2>
    <div class="faixas">
        @foreach ($faixas as $f)
            @php $atual = $faixa && $f->id === $faixa->id; $menor = $faixa && $f->limite_min < $faixa->limite_min; @endphp
            <div class="faixa {{ $atual ? 'faixa--atual' : '' }}" data-faixa="{{ $f->codigo }}">
                <b>{{ $f->nome }}</b>
                <span>{{ $f->faixaTexto() }}</span>
                <span class="faixa__valor">{{ $f->valorTexto() }}</span>
                @if ($atual)
                    <span class="selo selo--ativo" style="justify-self:start">Faixa em vigor</span>
                @elseif ($solicitada && $solicitada->id === $f->id)
                    <span class="selo selo--solicitado" style="justify-self:start">Mudança pedida</span>
                @elseif (! $menor)
                    <form method="post" action="{{ route('painel.plano.solicitar') }}">@csrf <input type="hidden" name="faixa" value="{{ $f->id }}">
                        <button class="botao botao--primario botao--pequeno">Fazer upgrade</button></form>
                @endif
            </div>
        @endforeach
    </div>
    <p class="suave" style="margin-top:14px"><small>A contratação on-line (pagamento) vai chegar em breve. Por enquanto, ao pedir o upgrade nossa equipe entra em contato e libera a faixa.</small></p>
    <div class="acoes-licenca">
        <a class="botao botao--contorno" href="https://wa.me/?text={{ rawurlencode('Olá! Quero mudar a faixa de plantel do MinhaTropa para '.$haras->nome.'.') }}" target="_blank" rel="noopener">Falar pelo WhatsApp</a>
        <a class="botao botao--contorno" href="mailto:atendimento@minhatropa.com.br?subject={{ rawurlencode('Plano MinhaTropa — '.$haras->nome) }}">atendimento@minhatropa.com.br</a>
    </div>
</section>

<section class="cartao">
    <h2>Módulos adicionais</h2>
    <p class="suave">Contrate só o que a propriedade precisa, em qualquer faixa de plantel.</p>
    <div class="modulos">
        @foreach ($modulos as $m)
            @php $sit = $modulosHaras[$m->codigo] ?? null; @endphp
            <a class="modulo-cartao" href="{{ route('painel.modulos.show', $m) }}">
                <b><x-icone :nome="$sit === 'ativo' ? ($m->icone ?: 'check') : 'cadeado'" /> {{ $m->nome }}</b>
                <span class="suave">{{ $m->resumo }}</span>
                <span>@if ($sit === 'ativo')<span class="selo selo--ativo">Ativo</span>@elseif ($sit === 'solicitado')<span class="selo selo--solicitado">Ativação pedida</span>@else<span class="selo selo--encerrado">Módulo adicional</span>@endif
                    <small class="suave">{{ $m->valorTexto() }}</small></span>
            </a>
        @endforeach
    </div>
</section>
@endsection
