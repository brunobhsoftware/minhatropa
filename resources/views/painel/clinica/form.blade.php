@extends('layouts.painel')
@section('titulo', $evento->exists ? 'Editar registro' : 'Novo registro de saúde')
@section('conteudo')
@php $editando = $evento->exists; @endphp
<div class="cabecalho">
    <div>
        <a href="{{ route('painel.clinica') }}" class="suave" style="font-size:.85rem">Saúde animal ›</a>
        <h1>{{ $editando ? 'Editar registro' : 'Novo registro de saúde' }}</h1>
    </div>
</div>

<form method="post" action="{{ $editando ? route('painel.clinica.atualizar', $evento) : route('painel.clinica.salvar') }}" class="cartao">
    @csrf
    @if ($editando) @method('PUT') @endif

    <div class="grade-2" style="margin-bottom:1rem">
        <div>
            <label for="tipo">Tipo <span class="obrigatorio">*</span></label>
            <select name="tipo" id="tipo" required onchange="alternarCampos()">
                @foreach (\App\Models\EventoSaude::TIPOS as $k => $v)
                    <option value="{{ $k }}" @selected(old('tipo', $evento->tipo) === $k)>{{ $v }}</option>
                @endforeach
            </select>
            @error('tipo') <small class="erro">{{ $message }}</small> @enderror
        </div>
        <div>
            <label for="animal_id">Animal <span class="obrigatorio">*</span></label>
            <select name="animal_id" id="animal_id" required>
                <option value="">Selecione…</option>
                @foreach ($animais as $a)
                    <option value="{{ $a->id }}" @selected(old('animal_id', $evento->animal_id) == $a->id)>{{ $a->nome }}</option>
                @endforeach
            </select>
            @error('animal_id') <small class="erro">{{ $message }}</small> @enderror
        </div>
    </div>

    <div class="grade-2" style="margin-bottom:1rem">
        <div>
            <label for="titulo">Título <span class="obrigatorio">*</span></label>
            <input name="titulo" id="titulo" value="{{ old('titulo', $evento->titulo) }}" required maxlength="200">
            @error('titulo') <small class="erro">{{ $message }}</small> @enderror
        </div>
        <div>
            <label for="data_evento">Data <span class="obrigatorio">*</span></label>
            <input type="date" name="data_evento" id="data_evento" value="{{ old('data_evento', $evento->data_evento?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            @error('data_evento') <small class="erro">{{ $message }}</small> @enderror
        </div>
    </div>

    <div style="margin-bottom:1rem">
        <label for="descricao">Descrição</label>
        <textarea name="descricao" id="descricao" rows="3" maxlength="5000">{{ old('descricao', $evento->descricao) }}</textarea>
        @error('descricao') <small class="erro">{{ $message }}</small> @enderror
    </div>

    <fieldset id="campos-medicamento" style="border:1px solid var(--borda);border-radius:8px;padding:1rem;margin-bottom:1rem">
        <legend style="font-weight:600;padding:0 .5rem">Medicamento / Produto</legend>
        <div class="grade-2" style="margin-bottom:.5rem">
            <div>
                <label for="medicamento">Nome do medicamento</label>
                <input name="medicamento" id="medicamento" value="{{ old('medicamento', $evento->medicamento) }}" maxlength="200">
            </div>
            <div>
                <label for="dosagem">Dosagem</label>
                <input name="dosagem" id="dosagem" value="{{ old('dosagem', $evento->dosagem) }}" maxlength="100" placeholder="Ex: 5ml, 2 comprimidos">
            </div>
        </div>
        <div class="grade-2">
            <div>
                <label for="via_administracao">Via de administração</label>
                <input name="via_administracao" id="via_administracao" value="{{ old('via_administracao', $evento->via_administracao) }}" maxlength="100" placeholder="Ex: oral, intramuscular, tópica">
            </div>
            <div>
                <label for="lote">Lote</label>
                <input name="lote" id="lote" value="{{ old('lote', $evento->lote) }}" maxlength="100">
            </div>
        </div>
    </fieldset>

    <div class="grade-2" style="margin-bottom:1rem">
        <div>
            <label for="peso_animal">Peso do animal (kg)</label>
            <input type="number" step="0.01" name="peso_animal" id="peso_animal" value="{{ old('peso_animal', $evento->peso_animal) }}" min="0" max="99999">
        </div>
        <div>
            <label for="temperatura">Temperatura (°C)</label>
            <input type="number" step="0.1" name="temperatura" id="temperatura" value="{{ old('temperatura', $evento->temperatura) }}" min="30" max="45">
        </div>
    </div>

    <div class="grade-2" style="margin-bottom:1rem">
        <div>
            <label for="custo">Custo (R$)</label>
            <input type="number" step="0.01" name="custo" id="custo" value="{{ old('custo', $evento->custo) }}" min="0">
        </div>
        <div>
            <label for="data_proxima">Próximo agendamento</label>
            <input type="date" name="data_proxima" id="data_proxima" value="{{ old('data_proxima', $evento->data_proxima?->format('Y-m-d')) }}">
            <small class="suave">Quando repetir este procedimento.</small>
        </div>
    </div>

    <div style="margin-bottom:1rem">
        <label for="observacoes">Observações</label>
        <textarea name="observacoes" id="observacoes" rows="2" maxlength="5000">{{ old('observacoes', $evento->observacoes) }}</textarea>
    </div>

    <button class="botao botao--primario">{{ $editando ? 'Salvar registro' : 'Criar registro' }}</button>
</form>

<script>
function alternarCampos() {
    var tipo = document.getElementById('tipo').value;
    var med = document.getElementById('campos-medicamento');
    var temMed = ['vacina','vermifugacao','tratamento','medicacao'].indexOf(tipo) >= 0;
    med.style.display = temMed ? '' : 'none';
}
alternarCampos();
</script>
@endsection
