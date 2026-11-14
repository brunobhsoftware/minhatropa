@extends('layouts.admin')
@section('titulo', 'Contatos do site')
@section('conteudo')
<div class="cabecalho"><div><h1>Contatos do site</h1><p>Pessoas que pediram para conhecer o MinhaTropa pelo formulário da página inicial.</p></div></div>
<div class="abas">
    <a href="{{ route('admin.contatos') }}" class="{{ $filtro !== 'novos' ? 'ativo' : '' }}">Todos</a>
    <a href="{{ route('admin.contatos', ['filtro' => 'novos']) }}" class="{{ $filtro === 'novos' ? 'ativo' : '' }}">Novos</a>
</div>
<section class="cartao">
    @forelse ($contatos as $c)
        <article class="contato {{ $c->lido_em ? 'contato--lido' : '' }}">
            <div class="contato__topo">
                <div><b>{{ $c->nome }}</b> @if (! $c->lido_em)<span class="selo selo--teste">Novo</span>@endif
                    <small class="suave">{{ data_local($c->criado_em) }}</small></div>
                <form method="post" action="{{ route('admin.contatos.lido', $c->id) }}">@csrf<button class="botao botao--contorno botao--pequeno">{{ $c->lido_em ? 'Voltar para novos' : 'Marcar como atendido' }}</button></form>
            </div>
            <dl class="dados">
                @if ($c->haras)<div><dt>Haras / Fazenda</dt><dd>{{ $c->haras }}</dd></div>@endif
                @if ($c->cidade_uf)<div><dt>Cidade/UF</dt><dd>{{ $c->cidade_uf }}</dd></div>@endif
                @if ($c->quantidade_animais)<div><dt>Animais</dt><dd>{{ $c->quantidade_animais }}</dd></div>@endif
                @if ($c->email)<div><dt>E-mail</dt><dd><a href="mailto:{{ $c->email }}">{{ $c->email }}</a></dd></div>@endif
                @if ($c->telefone)<div><dt>Telefone</dt><dd><a href="https://wa.me/55{{ preg_replace('/\D/', '', $c->telefone) }}" target="_blank" rel="noopener">{{ $c->telefone }}</a></dd></div>@endif
            </dl>
            @if ($c->mensagem)<p class="contato__mensagem">{{ $c->mensagem }}</p>@endif
        </article>
    @empty
        <div class="vazio"><p>Nenhum contato {{ $filtro === 'novos' ? 'novo' : 'recebido ainda' }}.</p></div>
    @endforelse
</section>
{{ $contatos->links() }}
@endsection
