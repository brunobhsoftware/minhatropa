@extends('layouts.profissional')
@section('titulo', 'Meus dados')
@section('conteudo')
@php $novo = ! $profissional->exists; $v = fn ($c) => old($c, $profissional->{$c}); $sel = (array) old('especialidades', $funcoes); @endphp
<div class="cabecalho"><div><h1>{{ $novo ? 'Meu cadastro de Profissional' : 'Meus dados' }}</h1><p>Seu cadastro único de pessoa física no MinhaTropa, identificado pelo CPF.</p></div></div>
<form method="post" action="{{ route('profissional.dados.salvar') }}" class="formulario" novalidate>
    @csrf @method('put')
    <section class="cartao"><h2>Dados pessoais</h2>
        <div class="grade-3">
            <label class="campo"><span>CPF {{ $novo ? '*' : '' }}</span>
                @if ($novo)<input name="cpf" value="{{ old('cpf') }}" required inputmode="numeric" data-mascara="cpf" maxlength="14" autocomplete="off">
                @else<input value="{{ $profissional->cpfFormatado() }}" disabled><small>O CPF é a sua identificação e não muda.</small>@endif</label>
            <label class="campo campo--duplo"><span>Nome completo *</span><input name="nome" value="{{ $novo ? old('nome', $usuario->nome) : $v('nome') }}" required maxlength="150"></label>
            <label class="campo"><span>Data de nascimento</span><input type="date" name="data_nascimento" value="{{ old('data_nascimento', $profissional->data_nascimento ? \Illuminate\Support\Carbon::parse($profissional->data_nascimento)->format('Y-m-d') : '') }}"></label>
            <label class="campo"><span>E-mail (o do seu acesso)</span><input value="{{ $usuario->email }}" disabled></label>
            <label class="campo"><span>Celular / WhatsApp {{ $novo ? '*' : '' }}</span><input name="telefone" value="{{ $novo ? old('telefone', $usuario->telefone) : ($v('telefone') ?: $v('whatsapp')) }}" data-mascara="telefone" maxlength="30"></label>
            <x-uf-municipio :uf="$profissional->uf" :municipio-id="$profissional->municipio_id" :cidade="$profissional->cidade" obrigatorio />
        </div>
    </section>
    <section class="cartao"><h2>Minhas funções</h2>
        <p class="suave" style="margin-bottom:1rem">Marque todas as que se aplicam. É por elas que os Haras / Fazendas encontram você.</p>
        <div class="grade-opcoes">
            @foreach (\App\Models\Profissional::ESPECIALIDADES as $valor => $texto)
            <label class="opcao-check"><input type="checkbox" name="especialidades[]" value="{{ $valor }}" @checked(in_array($valor, $sel)) @if ($titular && $valor === 'proprietario') onclick="return false" @endif> {{ $texto }}</label>
            @endforeach
        </div>
        <div class="grade-3" style="margin-top:14px">
            <label class="campo"><span>CRMV (se for veterinário)</span><input name="crmv" value="{{ $v('crmv') }}" maxlength="30"></label>
            @unless ($novo)<label class="campo campo--duplo"><span>Apresentação profissional</span><textarea name="descricao" rows="2" maxlength="5000" placeholder="Experiência, especialidades, região de atendimento…">{{ $v('descricao') }}</textarea></label>@endunless
        </div>
    </section>
    <div class="acoes-form"><a href="{{ route('profissional.inicio') }}" class="botao botao--contorno">Voltar</a><button class="botao botao--primario">{{ $novo ? 'Concluir meu cadastro' : 'Salvar' }}</button></div>
</form>
@endsection
