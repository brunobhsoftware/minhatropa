@extends('layouts.admin')
@section('titulo', $profissional->exists ? 'Editar '.$profissional->nome : 'Novo profissional')
@section('conteudo')
<div class="cabecalho">
    <div><h1>{{ $profissional->exists ? 'Editar profissional' : 'Novo profissional' }}</h1>@if ($profissional->exists)<p>{{ $profissional->nome }}</p>@endif</div>
    <a href="{{ $profissional->exists ? route('admin.profissionais.show', $profissional) : route('admin.profissionais') }}" class="botao botao--contorno">Cancelar</a>
</div>
@php $v = fn ($c) => old($c, $profissional->{$c}); @endphp
<form method="post" action="{{ $profissional->exists ? route('admin.profissionais.salvar', $profissional) : route('admin.profissionais.criar') }}" class="formulario">
    @csrf @if ($profissional->exists) @method('put') @endif
    <section class="cartao">
        <h2>Dados pessoais</h2>
        <div class="grade-3">
            <label class="campo"><span>Nome completo *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="150"></label>
            <label class="campo"><span>CPF *</span><input name="cpf" value="{{ old('cpf', $profissional->cpf ? $profissional->cpfFormatado() : '') }}" required inputmode="numeric" data-mascara="cpf" maxlength="14"></label>
            <label class="campo"><span>Data de nascimento</span><input type="date" name="data_nascimento" value="{{ old('data_nascimento', $profissional->data_nascimento?->format('Y-m-d')) }}"></label>
            <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ $v('email') }}" maxlength="190"></label>
            <label class="campo"><span>Celular</span><input name="telefone" value="{{ $v('telefone') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>Telefone fixo</span><input name="telefone_fixo" value="{{ $v('telefone_fixo') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>WhatsApp</span><input name="whatsapp" value="{{ $v('whatsapp') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>CRMV (veterinários)</span><input name="crmv" value="{{ $v('crmv') }}" maxlength="30" placeholder="Ex.: CRMV-MG 12345"></label>
        </div>
    </section>
    <section class="cartao">
        <h2>Funções</h2>
        <p class="suave" style="margin-bottom:1rem">Selecione todas as funções do profissional. A mesma pessoa pode acumular várias (ex.: Proprietário de Haras/Fazenda e Veterinário).</p>
        <div class="grade-opcoes">
            @foreach (\App\Models\Profissional::ESPECIALIDADES as $valor => $texto)
            <label class="opcao-check"><input type="checkbox" name="especialidades[]" value="{{ $valor }}" @checked(in_array($valor, (array) old('especialidades', $especialidadesSelecionadas)))> {{ $texto }}</label>
            @endforeach
        </div>
    </section>
    <section class="cartao">
        <h2>Endereço</h2>
        <div class="grade-3">
            <label class="campo"><span>CEP</span><input name="cep" value="{{ old('cep', $profissional->cep ? substr($profissional->cep, 0, 5).'-'.substr($profissional->cep, 5) : '') }}" inputmode="numeric" data-mascara="cep" data-cep maxlength="9"></label>
            <label class="campo campo--duplo"><span>Logradouro</span><input name="logradouro" value="{{ $v('logradouro') }}" maxlength="190"></label>
            <label class="campo"><span>Número</span><input name="numero" value="{{ $v('numero') }}" maxlength="20"></label>
            <label class="campo"><span>Complemento</span><input name="complemento" value="{{ $v('complemento') }}" maxlength="100"></label>
            <label class="campo"><span>Bairro</span><input name="bairro" value="{{ $v('bairro') }}" maxlength="100"></label>
            <x-uf-municipio :uf="$profissional->uf" :municipio-id="$profissional->municipio_id" :cidade="$profissional->cidade" />
        </div>
    </section>
    <section class="cartao">
        <h2>Descrição / experiência</h2>
        <label class="campo"><textarea name="descricao" rows="4" maxlength="5000" placeholder="Qualificações, experiências, observações...">{{ $v('descricao') }}</textarea></label>
    </section>
    <div class="acoes-form"><button class="botao botao--primario">{{ $profissional->exists ? 'Salvar alterações' : 'Cadastrar profissional' }}</button></div>
</form>
@endsection
