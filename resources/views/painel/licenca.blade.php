@extends('layouts.painel')
@section('titulo', 'Licença')
@section('conteudo')
<div class="cabecalho"><div><h1>Licença do MinhaTropa</h1></div></div>
@if ($licenca)
    <section class="cartao"><h2>Licença ativa ✅</h2><p>O {{ $haras->nome }} tem acesso completo: cadastre quantos animais precisar. As demais ferramentas vão sendo liberadas aqui conforme ficam prontas.</p></section>
@else
<section class="cartao cartao--destaque">
    <h2>Seu período gratuito {{ $totalAnimais >= $gratis ? 'chegou ao limite' : 'está ativo' }}</h2>
    <p>No período gratuito você configura o Haras e cadastra {{ $gratis }} animal. Você já cadastrou <b>{{ $totalAnimais }}</b>.</p>
    <p>Para cadastrar o restante do plantel e liberar todas as ferramentas, contrate o MinhaTropa:</p>
    <ul class="lista-liberar">
        <li>Cadastro ilimitado de animais</li><li>Agenda e rotinas da equipe</li><li>Saúde veterinária e vacinas</li>
        <li>Reprodução e genealogia</li><li>Treinos e desempenho</li><li>Custos, estoque e financeiro</li><li>Relatórios e indicadores</li>
    </ul>
    <div class="acoes-licenca">
        <a class="botao botao--primario" href="https://wa.me/?text={{ rawurlencode('Olá! Quero contratar o MinhaTropa para o '.$haras->nome.'.') }}" target="_blank" rel="noopener">Falar pelo WhatsApp</a>
        <a class="botao botao--contorno" href="mailto:atendimento@minhatropa.com.br?subject={{ rawurlencode('Contratar MinhaTropa — '.$haras->nome) }}">atendimento@minhatropa.com.br</a>
    </div>
    <p class="suave"><small>A contratação on-line (pagamento) vai chegar em breve. Por enquanto nossa equipe libera a licença assim que combinarmos.</small></p>
</section>
@endif
@endsection
