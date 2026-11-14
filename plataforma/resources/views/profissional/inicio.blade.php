@extends('layouts.profissional')
@section('titulo', 'Ambiente do profissional')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Olá, {{ $usuario->primeiroNome() }}!</h1><p>Este é o seu ambiente de profissional: seus dados e as propriedades em que você atua.</p></div>
    <a href="{{ route('cadastro') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Cadastrar Haras / Fazenda</a>
</div>

@if (! $profissional)
<section class="cartao cartao--destaque" data-sem-profissional>
    <h2>Complete o seu cadastro de Profissional</h2>
    <p>Sua conta ainda não tem o cadastro de pessoa física (pelo CPF). Com ele, qualquer Haras / Fazenda encontra você para vincular à equipe e você pode cadastrar uma propriedade em seu nome.</p>
    <a href="{{ route('profissional.dados') }}" class="botao botao--primario">Fazer meu cadastro de Profissional</a>
</section>
@else
<section class="cartao">
    <div class="cartao__titulo"><h2>Meu cadastro</h2><a href="{{ route('profissional.dados') }}">Editar meus dados</a></div>
    <dl class="dados">
        <div><dt>Nome</dt><dd>{{ $profissional->nome }}</dd></div>
        <div><dt>CPF</dt><dd>{{ $profissional->cpfFormatado() }}</dd></div>
        <div><dt>Funções</dt><dd>{{ $profissional->especialidadesTexto() ?: 'Nenhuma função marcada' }}</dd></div>
        <div><dt>Cidade</dt><dd>{{ $profissional->cidadeUf() }}</dd></div>
        <div><dt>Contato</dt><dd>{{ collect([$profissional->whatsapp ?: $profissional->telefone, $profissional->email])->filter()->implode(' · ') ?: '—' }}</dd></div>
        @if ($profissional->crmv)<div><dt>CRMV</dt><dd>{{ $profissional->crmv }}</dd></div>@endif
    </dl>
</section>
@endif

@if ($sociedades->isNotEmpty())
<section class="cartao" data-sociedades>
    <h2>Animais em sociedade</h2>
    <p class="suave" style="margin-bottom:.75rem">Animais em que você tem parte, lançados pelo seu CPF.</p>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Animal</th><th>Propriedade</th><th class="num">Minha parte</th><th>Situação</th></tr></thead>
        <tbody>
        @foreach ($sociedades as $s)
            <tr><td><b>{{ $s->nome }}</b><br><small class="suave">{{ $s->raca ?: 'raça não informada' }}</small></td>
                <td>{{ $s->propriedade }}<br><small class="suave">{{ $s->cidade ? $s->cidade.'/'.$s->uf : '' }}</small></td>
                <td class="num"><b>{{ \App\Support\Sociedade::pct($s->percentual) }}</b></td>
                <td><span class="selo selo--{{ $s->situacao }}">{{ \App\Models\Animal::SITUACOES[$s->situacao] ?? $s->situacao }}</span></td></tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif

<section class="cartao">
    <h2>Propriedades em que atuo</h2>
    @if ($vinculos->isEmpty() && $outrosAcessos->isEmpty())
        <div class="vazio"><p>Você ainda não está vinculado a nenhum Haras / Fazenda. Um proprietário pode vincular você pelo seu CPF, ou você pode cadastrar a sua propriedade.</p>
            <a href="{{ route('cadastro') }}" class="botao botao--primario botao--pequeno">Cadastrar Haras / Fazenda</a></div>
    @else
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Propriedade</th><th>Cidade</th><th>Minha função</th><th>Situação</th><th></th></tr></thead>
        <tbody>
        @foreach ($vinculos as $v)
            @php $acesso = $acessos->firstWhere('id', $v->id); @endphp
            <tr><td><b>{{ $v->nome }}</b> <small class="suave">{{ \App\Models\Empresa::TIPOS_PROPRIEDADE[$v->tipo_propriedade] ?? 'Haras' }}</small>@if ($v->titular)<br><span class="selo titular-selo">Proprietário titular</span>@endif</td>
                <td>{{ $v->cidade ? $v->cidade.'/'.$v->uf : '—' }}</td><td>{{ $v->funcao ?: '—' }}</td>
                <td><span class="selo selo--{{ $v->situacao }}">{{ $v->situacao === 'ativo' ? 'Ativo' : 'Desativado' }}</span></td>
                <td class="num">@if ($acesso)<form method="post" action="{{ route('painel.trocar', $acesso) }}">@csrf<button class="botao botao--primario botao--pequeno">Abrir painel</button></form>@else<small class="suave">Sem acesso ao painel</small>@endif</td></tr>
        @endforeach
        @foreach ($outrosAcessos as $h)
            <tr><td><b>{{ $h->nome }}</b> <small class="suave">{{ $h->rotulo() }}</small></td><td>{{ $h->cidadeUf() }}</td>
                <td>{{ \App\Models\EmpresaUsuario::PAPEIS[$h->pivot->papel] ?? $h->pivot->papel }}</td><td><span class="selo selo--ativo">Ativo</span></td>
                <td class="num"><form method="post" action="{{ route('painel.trocar', $h) }}">@csrf<button class="botao botao--primario botao--pequeno">Abrir painel</button></form></td></tr>
        @endforeach
        </tbody>
    </table></div>
    @endif
</section>
@endsection
