@extends('layouts.painel')
@section('titulo', 'Equipe')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Equipe</h1><p>Profissionais vinculados ao {{ $haras->nome }}.</p></div>
    <a href="{{ route('painel.profissionais.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Vincular profissional</a>
</div>
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Nome</th><th>CPF</th><th>Função</th><th>Telefone</th><th>Situação</th><th></th></tr></thead>
        <tbody>
        @forelse ($vinculos as $v)
            <tr>
                <td><b>{{ $v->nome }}</b></td>
                <td>{{ \App\Support\Documento::formatar($v->cpf) }}</td>
                <td>{{ $v->funcao ?: '—' }}</td>
                <td>{{ $v->telefone ?: '—' }}</td>
                <td><span class="selo selo--{{ $v->vinculo_situacao }}">{{ $v->vinculo_situacao === 'ativo' ? 'Ativo' : 'Desativado' }}</span></td>
                <td>
                    <form method="post" action="{{ route('painel.profissionais.editar', $v->vinculo_id) }}" class="linha-form">@csrf @method('put')
                        <input type="hidden" name="funcao" value="{{ $v->funcao }}">
                        <select name="vinculo_situacao"><option value="ativo" @selected($v->vinculo_situacao === 'ativo')>Ativo</option><option value="desativado" @selected($v->vinculo_situacao === 'desativado')>Desativado</option></select>
                        <button class="botao botao--contorno botao--pequeno">Salvar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="vazio"><p>Nenhum profissional vinculado. Vincule veterinários, tratadores, peões e toda a equipe.</p><a href="{{ route('painel.profissionais.novo') }}" class="botao botao--primario botao--pequeno">Vincular o primeiro</a></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
