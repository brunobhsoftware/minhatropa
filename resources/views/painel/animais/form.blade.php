@extends('layouts.painel')
@section('titulo', $animal->exists ? $animal->nome : 'Novo animal')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.animais') }}">Animais</a> ›</p><h1>{{ $animal->exists ? $animal->nome : 'Novo animal' }}</h1></div></div>
@php $v = fn ($c) => old($c, $animal->{$c}); @endphp
<form method="post" action="{{ $animal->exists ? route('painel.animais.salvar', $animal) : route('painel.animais.criar') }}" class="formulario">
    @csrf @if ($animal->exists) @method('put') @endif
    <section class="cartao"><h2>Identificação</h2>
        <div class="grade-3">
            <label class="campo"><span>Nome *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="120"></label>
            <label class="campo"><span>Sexo</span><select name="sexo"><option value=""></option><option value="macho" @selected($v('sexo') === 'macho')>Macho</option><option value="femea" @selected($v('sexo') === 'femea')>Fêmea</option></select></label>
            <label class="campo"><span>Categoria</span><select name="categoria"><option value=""></option>@foreach (\App\Models\Animal::CATEGORIAS as $k => $t)<option value="{{ $k }}" @selected($v('categoria') === $k)>{{ $t }}</option>@endforeach</select></label>
            <label class="campo"><span>Raça</span><input name="raca" value="{{ $v('raca') }}" maxlength="80" placeholder="Ex.: Mangalarga Marchador"></label>
            <label class="campo"><span>Pelagem</span><input name="pelagem" value="{{ $v('pelagem') }}" maxlength="80" placeholder="Ex.: Alazã"></label>
            <label class="campo"><span>Nascimento</span><input type="date" name="data_nascimento" value="{{ old('data_nascimento', $animal->data_nascimento?->format('Y-m-d')) }}"></label>
            <label class="campo"><span>Registro na associação</span><input name="registro" value="{{ $v('registro') }}" maxlength="60"></label>
            <label class="campo"><span>Microchip</span><input name="microchip" value="{{ $v('microchip') }}" maxlength="40"></label>
        </div>
    </section>
    <section class="cartao"><h2>Genealogia</h2>
        <div class="grade-2">
            <label class="campo"><span>Pai</span><input name="pai" value="{{ $v('pai') }}" maxlength="120"></label>
            <label class="campo"><span>Mãe</span><input name="mae" value="{{ $v('mae') }}" maxlength="120"></label>
        </div>
    </section>
    <section class="cartao"><h2>Observações</h2><label class="campo"><textarea name="observacoes" rows="3" maxlength="5000">{{ $v('observacoes') }}</textarea></label></section>
    <div class="acoes-form"><a href="{{ route('painel.animais') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">{{ $animal->exists ? 'Salvar' : 'Cadastrar animal' }}</button></div>
</form>
@endsection
