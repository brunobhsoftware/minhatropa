@extends('layouts.admin')
@section('titulo', 'Início')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Olá, {{ auth()->user()->primeiroNome() }}!</h1><p>Visão geral da plataforma MinhaTropa.</p></div>
    <a href="{{ route('admin.haras.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Novo Haras</a>
</div>

@if ($bancoPendente > 0)
<div class="alerta alerta--aviso"><p>Há <b>{{ $bancoPendente }}</b> atualização(ões) do banco pendente(s). <a href="{{ route('admin.banco') }}">Aplicar agora →</a></p></div>
@endif

<div class="numeros">
    <a class="numero" href="{{ route('admin.haras') }}"><x-icone nome="haras" /><b>{{ $totalHaras }}</b><span>Haras cadastrados</span></a>
    <a class="numero" href="{{ route('admin.haras', ['situacao' => 'ativo']) }}"><x-icone nome="check" /><b>{{ $porSituacao['ativo'] ?? 0 }}</b><span>Ativos</span></a>
    <a class="numero" href="{{ route('admin.haras', ['situacao' => 'teste']) }}"><x-icone nome="cavalo" /><b>{{ $porSituacao['teste'] ?? 0 }}</b><span>Em teste</span></a>
    <a class="numero" href="{{ route('admin.contatos', ['filtro' => 'novos']) }}"><x-icone nome="mensagem" /><b>{{ $contatosNovos }}</b><span>Contatos novos</span></a>
</div>

<div class="grade-2">
    <section class="cartao">
        <div class="cartao__titulo"><h2>Últimos Haras</h2><a href="{{ route('admin.haras') }}">Ver todos</a></div>
        @forelse ($ultimosHaras as $h)
            <a class="linha-lista" href="{{ route('admin.haras.show', $h) }}"><span><b>{{ $h->nome }}</b><small>{{ $h->cidadeUf() }}</small></span><span class="selo selo--{{ $h->situacao }}">{{ $h->situacaoTexto() }}</span></a>
        @empty
            <div class="vazio"><p>Nenhum Haras cadastrado ainda.</p><a href="{{ route('admin.haras.novo') }}" class="botao botao--primario botao--pequeno">Cadastrar o primeiro</a></div>
        @endforelse
    </section>
    <section class="cartao">
        <div class="cartao__titulo"><h2>Contatos do site</h2><a href="{{ route('admin.contatos') }}">Ver todos</a></div>
        @forelse ($ultimosContatos as $c)
            <div class="linha-lista"><span><b>{{ $c->nome }}</b><small>{{ $c->haras ?: 'Haras não informado' }} · {{ data_local($c->criado_em) }}</small></span>@if (! $c->lido_em)<span class="selo selo--teste">Novo</span>@endif</div>
        @empty
            <div class="vazio"><p>Nenhum contato recebido ainda.</p></div>
        @endforelse
    </section>
</div>
<p class="suave rodape-info"><small>{{ $totalUsuarios }} usuário(s) ativo(s) na plataforma.</small></p>
@endsection
