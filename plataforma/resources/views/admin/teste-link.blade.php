@extends('layouts.admin')
@section('titulo', 'Pagamento de teste')
@section('conteudo')
<div class="cabecalho"><div><h1>Pagamento de teste</h1><p>Order criada no Mercado Pago{{ $teste ? ' (teste)' : ' (produção)' }}.</p></div></div>

<section class="cartao" style="padding:20px">
    <p><b>Valor:</b> R$ {{ number_format($valor, 2, ',', '.') }} · <b>Order:</b> {{ $orderId }} · <b>Referência:</b> {{ $referencia }}</p>

    @if ($link)
        <p style="margin:16px 0"><a href="{{ $link }}" target="_blank" rel="noopener" class="botao botao--primario">Abrir link de pagamento ↗</a></p>
        <p class="suave"><small>Abra numa janela anônima para simular o comprador.{{ $teste ? ' Pague com cartão de teste (titular APRO, CPF 12345678909).' : '' }}</small></p>
    @else
        <p class="suave">A order foi criada, mas o Mercado Pago não devolveu um link de pagamento.</p>
    @endif
</section>

<p style="margin-top:16px"><a href="{{ route('admin.mercadopago') }}" class="botao botao--contorno botao--pequeno">← Voltar</a></p>
@endsection
