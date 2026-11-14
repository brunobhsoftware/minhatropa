@extends('layouts.acesso')
@section('titulo', 'Sem Haras / Fazenda')
@section('titulo_sufixo', 'MinhaTropa')
@section('conteudo')
<h1>Nenhum Haras / Fazenda disponível</h1>
<p class="suave">Seu acesso ainda não está ligado a um Haras / Fazenda ativo. Fale com o responsável pela propriedade ou com o atendimento: <a href="mailto:atendimento@minhatropa.com.br">atendimento@minhatropa.com.br</a>.</p>
<form method="post" action="{{ route('sair') }}">@csrf<button class="botao botao--contorno botao--largo">Sair</button></form>
@endsection
