@extends('layouts.painel')
@section('titulo', $lancamento->exists ? 'Editar lançamento' : 'Novo lançamento')
@section('conteudo')
<div class="cabecalho">
    <div><h1>{{ $lancamento->exists ? 'Editar lançamento' : 'Novo lançamento' }}</h1></div>
    <a href="{{ route('painel.financeiro') }}" class="botao botao--escuro">Voltar</a>
</div>

<section class="cartao" style="max-width:700px">
    <form method="post" action="{{ $lancamento->exists ? route('painel.financeiro.atualizar', $lancamento) : route('painel.financeiro.salvar') }}">
        @csrf
        @if ($lancamento->exists) @method('PUT') @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div>
                <label for="tipo">Tipo *</label>
                <select name="tipo" id="tipo" required>
                    @foreach (\App\Models\LancamentoFinanceiro::TIPOS as $k => $v)
                        <option value="{{ $k }}" @selected(old('tipo', $lancamento->tipo) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                @error('tipo') <small class="erro">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="situacao">Situação *</label>
                <select name="situacao" id="situacao" required>
                    @foreach (\App\Models\LancamentoFinanceiro::SITUACOES as $k => $v)
                        <option value="{{ $k }}" @selected(old('situacao', $lancamento->situacao) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                @error('situacao') <small class="erro">{{ $message }}</small> @enderror
            </div>
        </div>

        <div style="margin-top:1rem">
            <label for="descricao">Descrição *</label>
            <input name="descricao" id="descricao" value="{{ old('descricao', $lancamento->descricao) }}" required maxlength="255">
            @error('descricao') <small class="erro">{{ $message }}</small> @enderror
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem">
            <div>
                <label for="valor">Valor (R$) *</label>
                <input type="number" name="valor" id="valor" value="{{ old('valor', $lancamento->valor) }}" step="0.01" min="0.01" required>
                @error('valor') <small class="erro">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="categoria_financeira_id">Categoria</label>
                <select name="categoria_financeira_id" id="categoria_financeira_id">
                    <option value="">— Sem categoria —</option>
                    @foreach ($categorias as $c)
                        <option value="{{ $c->id }}" @selected(old('categoria_financeira_id', $lancamento->categoria_financeira_id) == $c->id)>{{ $c->nome }}</option>
                    @endforeach
                </select>
                @error('categoria_financeira_id') <small class="erro">{{ $message }}</small> @enderror
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-top:1rem">
            <div>
                <label for="data_vencimento">Vencimento *</label>
                <input type="date" name="data_vencimento" id="data_vencimento" value="{{ old('data_vencimento', $lancamento->data_vencimento?->format('Y-m-d')) }}" required>
                @error('data_vencimento') <small class="erro">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="data_pagamento">Data do pagamento</label>
                <input type="date" name="data_pagamento" id="data_pagamento" value="{{ old('data_pagamento', $lancamento->data_pagamento?->format('Y-m-d')) }}">
                @error('data_pagamento') <small class="erro">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="forma_pagamento">Forma de pagamento</label>
                <select name="forma_pagamento" id="forma_pagamento">
                    <option value="">—</option>
                    @foreach (\App\Models\LancamentoFinanceiro::FORMAS_PAGAMENTO as $k => $v)
                        <option value="{{ $k }}" @selected(old('forma_pagamento', $lancamento->forma_pagamento) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                @error('forma_pagamento') <small class="erro">{{ $message }}</small> @enderror
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1rem">
            <div>
                <label for="animal_id">Animal (opcional)</label>
                <select name="animal_id" id="animal_id">
                    <option value="">— Nenhum —</option>
                    @foreach ($animais as $a)
                        <option value="{{ $a->id }}" @selected(old('animal_id', $lancamento->animal_id) == $a->id)>{{ $a->nome }}</option>
                    @endforeach
                </select>
                @error('animal_id') <small class="erro">{{ $message }}</small> @enderror
            </div>
            <div>
                <label for="documento">Documento (NF, contrato…)</label>
                <input name="documento" id="documento" value="{{ old('documento', $lancamento->documento) }}" maxlength="100">
                @error('documento') <small class="erro">{{ $message }}</small> @enderror
            </div>
        </div>

        <div style="margin-top:1rem">
            <label for="observacoes">Observações</label>
            <textarea name="observacoes" id="observacoes" rows="3" maxlength="5000">{{ old('observacoes', $lancamento->observacoes) }}</textarea>
            @error('observacoes') <small class="erro">{{ $message }}</small> @enderror
        </div>

        <div style="margin-top:1.5rem;display:flex;gap:8px">
            <button class="botao botao--primario">{{ $lancamento->exists ? 'Salvar alterações' : 'Criar lançamento' }}</button>
            <a href="{{ route('painel.financeiro') }}" class="botao botao--escuro">Cancelar</a>
        </div>
    </form>
</section>
@endsection
