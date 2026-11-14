@extends('layouts.painel')
@section('titulo', 'Vincular por CPF')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.profissionais') }}">Criador e equipe</a> ›</p><h1>Vincular por CPF</h1></div></div>

{{-- Passo 1: a busca por CPF é sempre o primeiro campo. Consulta a base global de Profissionais. --}}
<section class="cartao">
    <h2>1. CPF</h2>
    <p class="suave" style="margin-bottom:1rem">Digite o CPF da pessoa. Se ela já estiver na base de Profissionais do MinhaTropa, os dados aparecem prontos para vincular.</p>
    <form method="get" action="{{ route('painel.profissionais.novo') }}" class="busca-cpf">
        <label class="campo"><span>CPF *</span><input name="cpf" value="{{ $cpf ? \App\Support\Documento::formatar($cpf) : request('cpf') }}" required inputmode="numeric" data-mascara="cpf" maxlength="14" autocomplete="off" @if (! $cpf) autofocus @endif></label>
        <button class="botao botao--primario"><x-icone nome="busca" :tamanho="18" /> Buscar</button>
    </form>
    @if ($erroCpf)<div class="alerta alerta--erro" style="margin-top:14px"><p>{{ $erroCpf }}</p></div>@endif
</section>

@if ($cpf)
@php $selecionadas = (array) old('funcoes', $encontrado ? array_diff($encontrado->especialidades(), []) : []); @endphp
<section class="cartao">
    @if ($encontrado && $jaVinculado)
        <div class="ficha-profissional"><b>{{ $encontrado->nome }}</b> já está vinculado(a) a esta propriedade. <a href="{{ route('painel.profissionais') }}">Voltar para a equipe</a></div>
    @else
    <h2>2. {{ $encontrado ? 'Profissional encontrado' : 'Cadastro rápido de Profissional' }}</h2>
    @if ($encontrado)
        <div class="ficha-profissional" data-encontrado>
            <b>✓ {{ $encontrado->nome }}</b> · CPF {{ $encontrado->cpfFormatado() }}
            <dl class="dados" style="margin-top:8px">
                <div><dt>Funções na plataforma</dt><dd>{{ $encontrado->especialidadesTexto() }}</dd></div>
                <div><dt>Cidade</dt><dd>{{ $encontrado->cidadeUf() }}</dd></div>
                <div><dt>Contato</dt><dd>{{ collect([$encontrado->telefone ?: $encontrado->whatsapp, $encontrado->email])->filter()->implode(' · ') ?: '—' }}</dd></div>
            </dl>
        </div>
    @else
        <div class="ficha-profissional ficha-profissional--novo" data-nao-encontrado><b>CPF {{ \App\Support\Documento::formatar($cpf) }} ainda não está na base de Profissionais.</b> Preencha o cadastro rápido abaixo: a pessoa entra na base e já fica vinculada.</div>
    @endif
    <form method="post" action="{{ route('painel.profissionais.criar') }}" class="formulario">
        @csrf
        <input type="hidden" name="cpf" value="{{ $cpf }}">
        @unless ($encontrado)
        <div class="grade-3">
            <label class="campo campo--duplo"><span>Nome completo *</span><input name="nome" value="{{ old('nome') }}" required maxlength="150"></label>
            <label class="campo"><span>Celular / WhatsApp</span><input name="telefone" value="{{ old('telefone') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" maxlength="190"></label>
            <x-uf-municipio />
            <label class="campo"><span>CRMV (se veterinário)</span><input name="crmv" value="{{ old('crmv') }}" maxlength="30"></label>
        </div>
        @endunless
        <div>
            <h3>Funções nesta propriedade *</h3>
            <div class="grade-opcoes" style="margin-top:.5rem">
                @foreach (\App\Models\Profissional::ESPECIALIDADES as $valor => $texto)
                <label class="opcao-check"><input type="checkbox" name="funcoes[]" value="{{ $valor }}" @checked(in_array($valor, $selecionadas))> {{ $texto }}</label>
                @endforeach
            </div>
        </div>
        <div class="grade-3">
            <label class="campo"><span>Data de início</span><input type="date" name="data_inicio" value="{{ old('data_inicio', date('Y-m-d')) }}"></label>
            @if ($proprietario)
            <label class="opcao-check" style="align-self:end"><input type="checkbox" name="titular" value="1" @checked(old('titular'))> Definir como criador / proprietário titular</label>
            @endif
        </div>
        <div class="acoes-form"><a href="{{ route('painel.profissionais') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">{{ $encontrado ? 'Vincular à propriedade' : 'Cadastrar e vincular' }}</button></div>
    </form>
    @endif
</section>
@endif
@endsection
