@extends('layouts.acesso')
@section('titulo', 'Sem Haras')
@section('titulo_sufixo', 'MinhaTropa')
@section('conteudo')
<h1>Nenhum Haras disponível</h1>
<p class="suave">Seu acesso ainda não está ligado a um Haras ativo. Fale com o responsável pelo Haras ou com o atendimento: <a href="mailto:atendimento@minhatropa.com.br">atendimento@minhatropa.com.br</a>.</p>
<form method="post" action="{{ route('sair') }}">@csrf<button class="botao botao--contorno botao--largo">Sair</button></form>
@endsection
