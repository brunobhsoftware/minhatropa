@extends('layouts.painel')
@section('titulo', $rotina->exists ? 'Editar rotina' : 'Nova rotina')
@section('conteudo')
@php $editando = $rotina->exists; @endphp
<div class="cabecalho">
    <div>
        <a href="{{ route('painel.rotinas') }}" class="suave" style="font-size:.85rem">Rotinas ›</a>
        <h1>{{ $editando ? 'Editar rotina' : 'Nova rotina' }}</h1>
    </div>
</div>

<form method="post" action="{{ $editando ? route('painel.rotinas.atualizar', $rotina) : route('painel.rotinas.salvar') }}" class="cartao">
    @csrf
    @if ($editando) @method('PUT') @endif

    <div class="grade-2" style="margin-bottom:1rem">
        <div>
            <label for="tipo_rotina_id">Tipo</label>
            <select name="tipo_rotina_id" id="tipo_rotina_id">
                <option value="">— Sem tipo —</option>
                @foreach ($tipos as $t)
                    <option value="{{ $t->id }}" @selected(old('tipo_rotina_id', $rotina->tipo_rotina_id) == $t->id)>{{ $t->icone }} {{ $t->nome }}</option>
                @endforeach
            </select>
            @error('tipo_rotina_id') <small class="erro">{{ $message }}</small> @enderror
        </div>
        <div>
            <label for="titulo">Título <span class="obrigatorio">*</span></label>
            <input name="titulo" id="titulo" value="{{ old('titulo', $rotina->titulo) }}" required maxlength="150">
            @error('titulo') <small class="erro">{{ $message }}</small> @enderror
        </div>
    </div>

    <div style="margin-bottom:1rem">
        <label for="descricao">Descrição</label>
        <textarea name="descricao" id="descricao" rows="3" maxlength="5000">{{ old('descricao', $rotina->descricao) }}</textarea>
        @error('descricao') <small class="erro">{{ $message }}</small> @enderror
    </div>

    <div class="grade-2" style="margin-bottom:1rem">
        <div>
            <label for="animal_id">Animal</label>
            <select name="animal_id" id="animal_id">
                <option value="">Todo o Haras</option>
                @foreach ($animais as $a)
                    <option value="{{ $a->id }}" @selected(old('animal_id', $rotina->animal_id) == $a->id)>{{ $a->nome }}</option>
                @endforeach
            </select>
            @error('animal_id') <small class="erro">{{ $message }}</small> @enderror
        </div>
        <div>
            <label for="prioridade">Prioridade</label>
            <select name="prioridade" id="prioridade" required>
                @foreach (\App\Models\Rotina::PRIORIDADES as $k => $v)
                    <option value="{{ $k }}" @selected(old('prioridade', $rotina->prioridade) === $k)>{{ $v }}</option>
                @endforeach
            </select>
            @error('prioridade') <small class="erro">{{ $message }}</small> @enderror
        </div>
    </div>

    <fieldset style="border:1px solid var(--borda);border-radius:8px;padding:1rem;margin-bottom:1rem">
        <legend style="font-weight:600;padding:0 .5rem">Frequência</legend>
        <div class="grade-2" style="margin-bottom:.5rem">
            <div>
                <label for="frequencia">Repetição <span class="obrigatorio">*</span></label>
                <select name="frequencia" id="frequencia" required onchange="alternarFrequencia()">
                    @foreach (\App\Models\Rotina::FREQUENCIAS as $k => $v)
                        <option value="{{ $k }}" @selected(old('frequencia', $rotina->frequencia) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="hora_prevista">Horário previsto</label>
                <input type="time" name="hora_prevista" id="hora_prevista" value="{{ old('hora_prevista', $rotina->hora_prevista ? substr($rotina->hora_prevista, 0, 5) : '') }}">
            </div>
        </div>

        <div id="campo-dia-semana" style="display:none;margin-bottom:.5rem">
            <label for="dia_semana">Dia da semana</label>
            <select name="dia_semana" id="dia_semana">
                @foreach (['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $i => $ds)
                    <option value="{{ $i }}" @selected(old('dia_semana', $rotina->dia_semana) == $i)>{{ $ds }}</option>
                @endforeach
            </select>
        </div>

        <div id="campo-dia-mes" style="display:none;margin-bottom:.5rem">
            <label for="dia_mes">Dia do mês</label>
            <input type="number" name="dia_mes" id="dia_mes" min="1" max="31" value="{{ old('dia_mes', $rotina->dia_mes) }}">
        </div>

        <div id="campo-frequencia-dias" style="display:none;margin-bottom:.5rem">
            <label for="frequencia_dias">A cada quantos dias?</label>
            <input type="number" name="frequencia_dias" id="frequencia_dias" min="1" max="365" value="{{ old('frequencia_dias', $rotina->frequencia_dias) }}">
        </div>

        <div class="grade-2">
            <div>
                <label for="data_inicio">Data de início <span class="obrigatorio">*</span></label>
                <input type="date" name="data_inicio" id="data_inicio" value="{{ old('data_inicio', $rotina->data_inicio?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                @error('data_inicio') <small class="erro">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="data_fim">Data de término</label>
                <input type="date" name="data_fim" id="data_fim" value="{{ old('data_fim', $rotina->data_fim?->format('Y-m-d')) }}">
                <small class="suave">Deixe vazio para rotina contínua.</small>
                @error('data_fim') <small class="erro">{{ $message }}</small> @enderror
            </div>
        </div>
    </fieldset>

    <div class="grade-2" style="margin-bottom:1rem">
        <div>
            <label for="responsavel_id">Responsável</label>
            <select name="responsavel_id" id="responsavel_id">
                <option value="">— Não definido —</option>
                @foreach ($usuarios as $u)
                    <option value="{{ $u->id }}" @selected(old('responsavel_id', $rotina->responsavel_id) == $u->id)>{{ $u->nome }}</option>
                @endforeach
            </select>
        </div>
        @if ($editando)
        <div>
            <label for="ativa">Situação</label>
            <select name="ativa" id="ativa">
                <option value="1" @selected(old('ativa', $rotina->ativa))>Ativa</option>
                <option value="0" @selected(! old('ativa', $rotina->ativa))>Inativa</option>
            </select>
        </div>
        @endif
    </div>

    <button class="botao botao--primario">{{ $editando ? 'Salvar rotina' : 'Criar rotina' }}</button>
</form>

<script>
function alternarFrequencia() {
    var f = document.getElementById('frequencia').value;
    document.getElementById('campo-dia-semana').style.display = f === 'semanal' ? '' : 'none';
    document.getElementById('campo-dia-mes').style.display = f === 'mensal' ? '' : 'none';
    document.getElementById('campo-frequencia-dias').style.display = f === 'personalizada' ? '' : 'none';
}
alternarFrequencia();
</script>
@endsection
