@extends('layouts.acesso')
@section('titulo', 'Escolher Haras')
@section('titulo_sufixo', 'MinhaTropa')
@section('conteudo')
<h1>Qual Haras você quer acessar?</h1>
<div class="lista-escolha">
@foreach ($lista as $h)
    <form method="post" action="{{ route('painel.trocar', $h) }}">@csrf<button class="botao botao--contorno botao--largo"><b>{{ $h->nome }}</b> <small>{{ $h->cidadeUf() }}</small></button></form>
@endforeach
</div>
@endsection
