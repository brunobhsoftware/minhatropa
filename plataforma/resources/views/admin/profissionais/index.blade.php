@extends('layouts.admin')
@section('titulo', 'Profissionais')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Profissionais</h1><p>Base única de pessoas físicas da plataforma, identificadas pelo CPF.</p></div>
    <a href="{{ route('admin.profissionais.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Novo profissional</a>
</div>
<section class="cartao">
    <form class="barra-busca" method="get">
        <input name="busca" value="{{ $busca }}" placeholder="Nome, CPF ou e-mail…" autofocus>
        <span class="filtro-geo"><x-uf-municipio :uf="$uf" :municipio-id="$municipioId ?: null" :cidade="$cidadeFiltro" filtro /></span>
        <select name="funcao"><option value="">Todas as funções</option>
            @foreach (\App\Models\Profissional::ESPECIALIDADES as $v => $t)<option value="{{ $v }}" @selected($funcao === $v)>{{ $t }}</option>@endforeach
        </select>
        <select name="situacao"><option value="">Todas as situações</option>
            @foreach (\App\Models\Profissional::SITUACOES as $v => $t)<option value="{{ $v }}" @selected($situacao === $v)>{{ $t }}</option>@endforeach
        </select>
        <button class="botao botao--primario botao--pequeno">Buscar</button>
        @if ($busca || $situacao || $uf || $funcao)<a href="{{ route('admin.profissionais') }}" class="botao botao--contorno botao--pequeno">Limpar</a>@endif
    </form>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Nome</th><th>CPF</th><th>Cidade</th><th>Funções</th><th>Situação</th></tr></thead>
        <tbody>
        @forelse ($profissionais as $p)
            <tr class="clicavel" data-href="{{ route('admin.profissionais.show', $p) }}">
                <td><a href="{{ route('admin.profissionais.show', $p) }}"><b>{{ $p->nome }}</b></a></td>
                <td>{{ $p->cpfFormatado() }}</td>
                <td>{{ $p->cidadeUf() }}</td>
                <td>{{ $p->especialidadesTexto() }}</td>
                <td><span class="selo selo--{{ $p->situacao }}">{{ $p->situacaoTexto() }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="vazio"><p>Nenhum profissional encontrado.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <x-paginacao :paginator="$profissionais" />
</section>
@endsection
