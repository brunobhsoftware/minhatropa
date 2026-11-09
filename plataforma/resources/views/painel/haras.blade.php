@extends('layouts.painel')
@section('titulo', 'Dados do Haras')
@section('conteudo')
<div class="cabecalho"><div><h1>Dados do Haras</h1><p>Configuração inicial: estes dados aparecem nos relatórios e documentos do Haras.</p></div></div>
@php $v = fn ($c) => old($c, $haras->{$c}); @endphp
<form method="post" action="{{ route('painel.haras.salvar') }}" class="formulario">
    @csrf @method('put')
    <section class="cartao"><h2>Identificação</h2>
        <div class="grade-2">
            <label class="campo"><span>Nome do Haras *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="150"></label>
            <label class="campo"><span>Razão social / proprietário</span><input name="razao_social" value="{{ $v('razao_social') }}" maxlength="190"></label>
            <label class="campo"><span>CPF/CNPJ</span><input value="{{ $haras->documentoFormatado() }}" disabled><small>Para alterar, fale com o atendimento.</small></label>
            <label class="campo"><span>Inscrição estadual / produtor rural</span><input name="inscricao_estadual" value="{{ $v('inscricao_estadual') }}" maxlength="30"></label>
            <label class="campo"><span>Área (hectares)</span><input name="area_hectares" value="{{ old('area_hectares', $haras->area_hectares !== null ? number_format((float) $haras->area_hectares, 2, ',', '.') : '') }}" inputmode="decimal"></label>
            <label class="campo"><span>Quantidade de animais</span><select name="quantidade_animais_estimada"><option value=""></option>@foreach (\App\Models\Empresa::QUANTIDADES as $q)<option @selected($v('quantidade_animais_estimada') === $q)>{{ $q }}</option>@endforeach</select></label>
        </div>
    </section>
    <section class="cartao"><h2>Contato</h2>
        <div class="grade-3">
            <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ $v('email') }}" maxlength="190"></label>
            <label class="campo"><span>Telefone</span><input name="telefone" value="{{ $v('telefone') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>WhatsApp</span><input name="whatsapp" value="{{ $v('whatsapp') }}" data-mascara="telefone" maxlength="30"></label>
        </div>
    </section>
    <section class="cartao"><h2>Endereço da propriedade</h2>
        <div class="grade-3">
            <label class="campo"><span>CEP</span><input name="cep" value="{{ old('cep', $haras->cep ? substr($haras->cep, 0, 5).'-'.substr($haras->cep, 5) : '') }}" data-mascara="cep" data-cep maxlength="9"></label>
            <label class="campo campo--duplo"><span>Logradouro / estrada</span><input name="logradouro" value="{{ $v('logradouro') }}" maxlength="190"></label>
            <label class="campo"><span>Número / km</span><input name="numero" value="{{ $v('numero') }}" maxlength="20"></label>
            <label class="campo"><span>Complemento</span><input name="complemento" value="{{ $v('complemento') }}" maxlength="100"></label>
            <label class="campo"><span>Bairro / zona rural</span><input name="bairro" value="{{ $v('bairro') }}" maxlength="100"></label>
            <label class="campo"><span>Cidade</span><input name="cidade" value="{{ $v('cidade') }}" maxlength="100"></label>
            <label class="campo"><span>UF</span><select name="uf"><option value=""></option>@foreach ($ufs as $uf)<option @selected($v('uf') === $uf)>{{ $uf }}</option>@endforeach</select></label>
        </div>
    </section>
    <div class="acoes-form"><button class="botao botao--primario">Salvar</button></div>
</form>
@endsection
