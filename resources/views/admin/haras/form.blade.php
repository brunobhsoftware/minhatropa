@extends('layouts.admin')
@section('titulo', $empresa->exists ? 'Editar '.$empresa->nome : 'Novo Haras')
@section('conteudo')
<div class="cabecalho">
    <div><h1>{{ $empresa->exists ? 'Editar Haras' : 'Novo Haras' }}</h1>@if ($empresa->exists)<p>{{ $empresa->nome }}</p>@endif</div>
    <a href="{{ $empresa->exists ? route('admin.haras.show', $empresa) : route('admin.haras') }}" class="botao botao--contorno">Cancelar</a>
</div>
@php $v = fn ($campo) => old($campo, $empresa->{$campo}); @endphp
<form method="post" action="{{ $empresa->exists ? route('admin.haras.salvar', $empresa) : route('admin.haras.criar') }}" class="formulario">
    @csrf @if ($empresa->exists) @method('put') @endif
    <section class="cartao">
        <h2>Identificação</h2>
        <div class="grade-2">
            <label class="campo"><span>Nome do Haras *</span><input name="nome" value="{{ $v('nome') }}" required maxlength="150" placeholder="Ex.: Haras Vista Alegre"></label>
            <label class="campo"><span>Razão social / nome do proprietário</span><input name="razao_social" value="{{ $v('razao_social') }}" maxlength="190"></label>
            <label class="campo"><span>CPF ou CNPJ</span><input name="documento" value="{{ old('documento', $empresa->documento ? $empresa->documentoFormatado() : '') }}" inputmode="numeric" data-mascara="documento" maxlength="18"></label>
            <label class="campo"><span>Inscrição estadual / produtor rural</span><input name="inscricao_estadual" value="{{ $v('inscricao_estadual') }}" maxlength="30"></label>
            <label class="campo"><span>Área (hectares)</span><input name="area_hectares" value="{{ old('area_hectares', $empresa->area_hectares !== null ? number_format((float) $empresa->area_hectares, 2, ',', '.') : '') }}" inputmode="decimal"></label>
            @if (! $empresa->exists)
            <label class="campo"><span>Situação inicial</span><select name="situacao">
                @foreach (\App\Models\Empresa::SITUACOES as $valor => $texto)@continue(in_array($valor, ['suspenso', 'encerrado']))<option value="{{ $valor }}" @selected(old('situacao', $empresa->situacao) === $valor)>{{ $texto }}</option>@endforeach
            </select></label>
            @endif
            @if ($planos->isNotEmpty())
            <label class="campo"><span>Plano</span><select name="plano_id"><option value="">Sem plano</option>
                @foreach ($planos as $p)<option value="{{ $p->id }}" @selected((string) $v('plano_id') === (string) $p->id)>{{ $p->nome }}</option>@endforeach
            </select></label>
            @endif
        </div>
    </section>
    <section class="cartao">
        <h2>Contato</h2>
        <div class="grade-3">
            <label class="campo"><span>E-mail do Haras</span><input type="email" name="email" value="{{ $v('email') }}" maxlength="190"></label>
            <label class="campo"><span>Telefone</span><input name="telefone" value="{{ $v('telefone') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>WhatsApp</span><input name="whatsapp" value="{{ $v('whatsapp') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>Responsável</span><input name="responsavel_nome" value="{{ $v('responsavel_nome') }}" maxlength="150"></label>
            <label class="campo"><span>E-mail do responsável</span><input type="email" name="responsavel_email" value="{{ $v('responsavel_email') }}" maxlength="190"></label>
            <label class="campo"><span>Telefone do responsável</span><input name="responsavel_telefone" value="{{ $v('responsavel_telefone') }}" data-mascara="telefone" maxlength="30"></label>
        </div>
    </section>
    <section class="cartao">
        <h2>Endereço da propriedade</h2>
        <div class="grade-3">
            <label class="campo"><span>CEP</span><input name="cep" value="{{ old('cep', $empresa->cep ? substr($empresa->cep, 0, 5).'-'.substr($empresa->cep, 5) : '') }}" inputmode="numeric" data-mascara="cep" data-cep maxlength="9"></label>
            <label class="campo campo--duplo"><span>Logradouro / estrada</span><input name="logradouro" value="{{ $v('logradouro') }}" maxlength="190"></label>
            <label class="campo"><span>Número / km</span><input name="numero" value="{{ $v('numero') }}" maxlength="20"></label>
            <label class="campo"><span>Complemento</span><input name="complemento" value="{{ $v('complemento') }}" maxlength="100"></label>
            <label class="campo"><span>Bairro / zona rural</span><input name="bairro" value="{{ $v('bairro') }}" maxlength="100"></label>
            <label class="campo"><span>Cidade</span><input name="cidade" value="{{ $v('cidade') }}" maxlength="100"></label>
            <label class="campo"><span>UF</span><select name="uf"><option value=""></option>@foreach ($ufs as $uf)<option @selected($v('uf') === $uf)>{{ $uf }}</option>@endforeach</select></label>
        </div>
    </section>
    <section class="cartao">
        <h2>Observações internas</h2>
        <label class="campo"><textarea name="observacoes" rows="4" maxlength="5000" placeholder="Anotações da equipe (não aparecem para o Haras)">{{ $v('observacoes') }}</textarea></label>
    </section>
    <div class="acoes-form"><button class="botao botao--primario">{{ $empresa->exists ? 'Salvar alterações' : 'Cadastrar Haras' }}</button></div>
</form>
@endsection
