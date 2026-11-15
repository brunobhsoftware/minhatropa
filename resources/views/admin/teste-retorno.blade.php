@extends('layouts.admin')
@section('titulo', 'Retorno do teste')
@section('conteudo')
<div class="cabecalho"><div><h1>Retorno do pagamento de teste</h1></div></div>

<section class="cartao" style="padding:20px">
    <p><b>Resultado:</b>
        @if ($resultado === 'sucesso') <span class="selo selo--verde">Sucesso</span>
        @elseif ($resultado === 'pendente') <span class="selo selo--amarelo">Pendente</span>
        @elseif ($resultado === 'falha') <span class="selo selo--vermelho">Falha</span>
        @else <span class="selo selo--cinza">{{ $resultado ?: 'Desconhecido' }}</span>
        @endif
    </p>

    @if ($info)
        <p><b>Order:</b> {{ $info['order'] ?? '—' }} · <b>Ambiente:</b> {{ $info['ambiente'] ?? '—' }}</p>
    @endif

    @if ($order && $admin)
        <h3>Dados da order</h3>
        <pre style="background:var(--fundo-2);padding:12px;border-radius:6px;overflow-x:auto;font-size:13px;max-height:400px">{{ json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    @endif

    @if ($extras)
        <h3>Parâmetros extras</h3>
        <ul>@foreach ($extras as $k => $v)<li><b>{{ $k }}:</b> {{ $v }}</li>@endforeach</ul>
    @endif
</section>

<p style="margin-top:16px"><a href="{{ route('admin.mercadopago') }}" class="botao botao--contorno botao--pequeno">← Voltar</a></p>
@endsection
