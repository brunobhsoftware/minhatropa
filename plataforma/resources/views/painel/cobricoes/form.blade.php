@extends('layouts.painel')
@section('titulo', $cobricao->exists ? 'Editar cobrição' : 'Nova cobrição')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.cobricoes') }}">Cobrições</a> ›</p><h1>{{ $cobricao->exists ? 'Editar cobrição' : 'Nova cobrição' }}</h1></div></div>
@php $v = fn ($c) => old($c, $cobricao->{$c}); @endphp
<form method="post" action="{{ $cobricao->exists ? route('painel.cobricoes.salvar', $cobricao) : route('painel.cobricoes.criar') }}" class="formulario">
    @csrf @if ($cobricao->exists) @method('put') @endif

    <section class="cartao"><h2>Animais</h2>
        <div class="grade-3">
            <label class="campo"><span>Égua *</span>
                <select name="egua_id" required>
                    <option value="">Selecione…</option>
                    @foreach ($eguas as $e)
                        <option value="{{ $e->id }}" @selected((int) $v('egua_id') === $e->id)>{{ $e->nome }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Garanhão</span>
                <select name="garanhao_id" id="garanhaoId">
                    <option value="">Garanhão externo (digitar nome)</option>
                    @foreach ($garanhoes as $g)
                        <option value="{{ $g->id }}" @selected((int) $v('garanhao_id') === $g->id)>{{ $g->nome }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo" id="campoGaranhaoNome" style="{{ $v('garanhao_id') ? 'display:none' : '' }}"><span>Nome do garanhão externo</span>
                <input name="garanhao_nome" value="{{ $v('garanhao_nome') }}" maxlength="150" placeholder="Nome, raça, proprietário">
            </label>
            <label class="campo" id="campoReceptora" style="{{ $v('tipo') === 'transferencia_embriao' ? '' : 'display:none' }}"><span>Receptora (TE)</span>
                <select name="receptora_id">
                    <option value="">Nenhuma</option>
                    @foreach ($eguas as $e)
                        <option value="{{ $e->id }}" @selected((int) $v('receptora_id') === $e->id)>{{ $e->nome }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="cartao"><h2>Dados da cobrição</h2>
        <div class="grade-3">
            <label class="campo"><span>Tipo *</span>
                <select name="tipo" id="tipoSelect" required>
                    @foreach (\App\Models\Cobricao::TIPOS as $k => $t)
                        <option value="{{ $k }}" @selected($v('tipo') === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Subtipo / técnica</span>
                <select name="subtipo">
                    <option value="">—</option>
                    @foreach (\App\Models\Cobricao::SUBTIPOS as $k => $t)
                        <option value="{{ $k }}" @selected($v('subtipo') === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Data da cobrição *</span><input type="date" name="data_cobricao" value="{{ old('data_cobricao', $cobricao->data_cobricao?->format('Y-m-d')) }}" required></label>
            <label class="campo"><span>Data da confirmação</span><input type="date" name="data_confirmacao" value="{{ old('data_confirmacao', $cobricao->data_confirmacao?->format('Y-m-d')) }}"></label>
            <label class="campo"><span>Previsão de parto</span><input type="date" name="data_prevista_parto" value="{{ old('data_prevista_parto', $cobricao->data_prevista_parto?->format('Y-m-d')) }}"></label>
            <label class="campo"><span>Veterinário</span>
                <select name="veterinario_id">
                    <option value="">—</option>
                    @foreach ($veterinarios as $vet)
                        <option value="{{ $vet->id }}" @selected((int) $v('veterinario_id') === $vet->id)>{{ $vet->nome }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Local</span><input name="local" value="{{ $v('local') }}" maxlength="150"></label>
            <label class="campo"><span>Resultado</span>
                <select name="resultado">
                    @foreach (\App\Models\Cobricao::RESULTADOS as $k => $t)
                        <option value="{{ $k }}" @selected(($v('resultado') ?? 'pendente') === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="cartao"><h2>Observações</h2><label class="campo"><textarea name="observacoes" rows="3" maxlength="5000">{{ $v('observacoes') }}</textarea></label></section>

    <div class="acoes-form"><a href="{{ route('painel.cobricoes') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">{{ $cobricao->exists ? 'Salvar' : 'Registrar cobrição' }}</button></div>
</form>

<script>
document.getElementById('garanhaoId').addEventListener('change', function() {
    document.getElementById('campoGaranhaoNome').style.display = this.value ? 'none' : '';
});
document.getElementById('tipoSelect').addEventListener('change', function() {
    document.getElementById('campoReceptora').style.display = this.value === 'transferencia_embriao' ? '' : 'none';
});
</script>
@endsection
