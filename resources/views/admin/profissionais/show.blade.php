@extends('layouts.admin')
@section('titulo', $profissional->nome)
@section('conteudo')
<div class="cabecalho">
    <div><p class="migalha"><a href="{{ route('admin.profissionais') }}">Profissionais</a> ›</p><h1>{{ $profissional->nome }} <span class="selo selo--{{ $profissional->situacao }}">{{ $profissional->situacaoTexto() }}</span></h1>
        @if ($profissional->situacao_motivo)<p>Motivo: {{ $profissional->situacao_motivo }}</p>@endif</div>
    <a href="{{ route('admin.profissionais.editar', $profissional) }}" class="botao botao--primario"><x-icone nome="editar" :tamanho="16" /> Editar dados</a>
</div>

<div class="grade-2">
    <section class="cartao">
        <h2>Dados pessoais</h2>
        <dl class="dados">
            <div><dt>CPF</dt><dd>{{ $profissional->cpfFormatado() }}</dd></div>
            <div><dt>E-mail</dt><dd>{{ $profissional->email ?: '—' }}</dd></div>
            <div><dt>Celular</dt><dd>{{ $profissional->telefone ?: '—' }}</dd></div>
            <div><dt>Telefone fixo</dt><dd>{{ $profissional->telefone_fixo ?: '—' }}</dd></div>
            <div><dt>WhatsApp</dt><dd>@if ($profissional->whatsapp)<a href="https://wa.me/55{{ preg_replace('/\D/', '', $profissional->whatsapp) }}" target="_blank" rel="noopener">{{ $profissional->whatsapp }}</a>@else — @endif</dd></div>
            <div><dt>Nascimento</dt><dd>{{ $profissional->data_nascimento ? $profissional->data_nascimento->format('d/m/Y') : '—' }}</dd></div>
            @if ($profissional->crmv)<div><dt>CRMV</dt><dd>{{ $profissional->crmv }}</dd></div>@endif
            <div><dt>Cadastrado em</dt><dd>{{ data_local($profissional->criado_em) }}</dd></div>
        </dl>
    </section>
    <section class="cartao">
        <h2>Endereço</h2>
        <dl class="dados">
            <div><dt>Endereço</dt><dd>{{ collect([$profissional->logradouro, $profissional->numero, $profissional->complemento, $profissional->bairro])->filter()->implode(', ') ?: '—' }}<br>{{ $profissional->cidadeUf() }} @if ($profissional->cep)· CEP {{ substr($profissional->cep, 0, 5) }}-{{ substr($profissional->cep, 5) }}@endif</dd></div>
        </dl>
    </section>
</div>

@if (count($especialidades))
<section class="cartao">
    <h2>Funções</h2>
    <div class="lista-tags">@foreach ($especialidades as $e)<span class="tag">{{ \App\Models\Profissional::ESPECIALIDADES[$e] ?? $e }}</span>@endforeach</div>
</section>
@endif

@if ($profissional->descricao)
<section class="cartao"><h2>Descrição / experiência</h2><p class="pre">{{ $profissional->descricao }}</p></section>
@endif

@if ($vinculos->isNotEmpty())
<section class="cartao">
    <h2>Haras / Fazendas vinculados</h2>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Haras / Fazenda</th><th>Função</th><th>Início</th><th>Situação</th></tr></thead>
        <tbody>
        @foreach ($vinculos as $h)
            <tr>
                <td><a href="{{ route('admin.haras.show', $h) }}"><b>{{ $h->nome }}</b></a></td>
                <td>{{ $h->pivot->funcao ?: '—' }}</td>
                <td>{{ $h->pivot->data_inicio ? \Carbon\Carbon::parse($h->pivot->data_inicio)->format('d/m/Y') : '—' }}</td>
                <td><span class="selo selo--{{ $h->pivot->situacao }}">{{ $h->pivot->situacao === 'ativo' ? 'Ativo' : 'Desativado' }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif

<section class="cartao">
    <h2>Situação</h2>
    <form method="post" action="{{ route('admin.profissionais.situacao', $profissional) }}" class="formulario">
        @csrf @method('put')
        <div class="opcoes-situacao">
            @foreach (\App\Models\Profissional::SITUACOES as $valor => $texto)
                <label class="opcao-cartao"><input type="radio" name="situacao" value="{{ $valor }}" @checked($profissional->situacao === $valor)><span class="selo selo--{{ $valor }}">{{ $texto }}</span></label>
            @endforeach
        </div>
        <label class="campo"><span>Motivo (obrigatório para banir)</span><input name="situacao_motivo" value="{{ old('situacao_motivo', $profissional->situacao_motivo) }}" maxlength="255"></label>
        <button class="botao botao--contorno botao--pequeno">Alterar situação</button>
    </form>
</section>
@endsection
