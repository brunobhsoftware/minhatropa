@extends('layouts.painel')
@section('titulo', 'Sem acesso a esta área')
@section('conteudo')
<div class="cabecalho"><div><h1>Sem acesso a esta área</h1></div></div>
<section class="cartao cartao--destaque" data-sem-acesso>
    <div class="modulo-heroi">
        <div class="modulo-heroi__icone"><x-icone nome="cadeado" :tamanho="32" /></div>
        <div style="flex:1;min-width:240px">
            <h2>{{ $mensagem }}</h2>
            <p>O que cada pessoa vê em {{ $haras->nome }} depende da função que o proprietário definiu para ela. Se você precisa desta área, peça ao proprietário {{ $haras->doRotulo() }} para ajustar o seu acesso em RH → Cadastro da equipe.</p>
        </div>
    </div>
    <div class="acoes-licenca"><a href="{{ route('painel') }}" class="botao botao--primario">Voltar ao início</a></div>
</section>
@endsection
