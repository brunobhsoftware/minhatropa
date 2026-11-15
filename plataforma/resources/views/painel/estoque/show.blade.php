@extends('layouts.painel')
@section('titulo', $item->nome)
@section('conteudo')
<div class="cabecalho">
    <div>
        <h1>{{ $item->nome }}</h1>
        <p>{{ $item->categoria?->nome ?? 'Sem categoria' }} · {{ $item->unidadeTexto() }}
            @if ($item->abaixoDoMinimo()) <span class="selo selo--alerta">Abaixo do mínimo</span> @endif
        </p>
    </div>
    <div class="cabecalho__acoes">
        <a href="{{ route('painel.estoque.editar', $item) }}" class="botao botao--escuro"><x-icone nome="editar" :tamanho="18" /> Editar</a>
        <form method="post" action="{{ route('painel.estoque.excluir', $item) }}" onsubmit="return confirm('Remover este item do estoque?')">@csrf @method('DELETE')
            <button class="botao botao--perigo botao--pequeno">Excluir</button>
        </form>
    </div>
</div>

<section class="cartao">
    <dl class="dados">
        <div><dt>Estoque atual</dt><dd><b>{{ rtrim(rtrim(number_format($item->estoque_atual, 3, ',', '.'), '0'), ',') }} {{ $item->unidade }}</b></dd></div>
        @if ($item->estoque_minimo !== null)<div><dt>Estoque mínimo</dt><dd>{{ rtrim(rtrim(number_format($item->estoque_minimo, 3, ',', '.'), '0'), ',') }} {{ $item->unidade }}</dd></div>@endif
        @if ($item->custo_unitario)<div><dt>Custo unitário</dt><dd>R$ {{ number_format($item->custo_unitario, 2, ',', '.') }}</dd></div>@endif
        @if ($item->localizacao)<div><dt>Localização</dt><dd>{{ $item->localizacao }}</dd></div>@endif
        @if ($item->observacoes)<div><dt>Observações</dt><dd>{{ $item->observacoes }}</dd></div>@endif
    </dl>
</section>

<section class="cartao">
    <h2>Registrar movimentação</h2>
    <form method="post" action="{{ route('painel.estoque.movimentar', $item) }}" class="formulario">
        @csrf
        <div class="campo-grupo">
            <div class="campo-linha campo-linha--curto">
                <label class="campo-label">Tipo *</label>
                <select name="tipo" class="campo" required>
                    <option value="entrada" @selected(old('tipo') === 'entrada')>Entrada</option>
                    <option value="saida" @selected(old('tipo') === 'saida')>Saída</option>
                    <option value="ajuste" @selected(old('tipo') === 'ajuste')>Ajuste (definir saldo)</option>
                </select>
            </div>
            <div class="campo-linha campo-linha--curto">
                <label class="campo-label">Quantidade *</label>
                <input type="number" name="quantidade" value="{{ old('quantidade') }}" class="campo" step="any" min="0.001" required>
            </div>
            <div class="campo-linha campo-linha--curto">
                <label class="campo-label">Data *</label>
                <input type="date" name="data_movimentacao" value="{{ old('data_movimentacao', date('Y-m-d')) }}" class="campo" required>
            </div>
        </div>
        <div class="campo-grupo">
            <div class="campo-linha campo-linha--curto">
                <label class="campo-label">Custo unitário (R$)</label>
                <input type="number" name="custo_unitario" value="{{ old('custo_unitario') }}" class="campo" step="0.01" min="0">
            </div>
            <div class="campo-linha">
                <label class="campo-label">Motivo</label>
                <input type="text" name="motivo" value="{{ old('motivo') }}" class="campo" maxlength="255" placeholder="Compra, uso, perda…">
            </div>
        </div>
        <div class="campo-linha">
            <label class="campo-label">Observações</label>
            <textarea name="observacoes" class="campo" rows="2" maxlength="2000">{{ old('observacoes') }}</textarea>
        </div>
        <button class="botao botao--primario">Registrar movimentação</button>
    </form>
</section>

@if ($movimentacoes->isNotEmpty())
<section class="cartao">
    <h2>Histórico de movimentações</h2>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Data</th><th>Tipo</th><th class="num">Quantidade</th><th class="num">Custo un.</th><th>Motivo</th></tr></thead>
        <tbody>
        @foreach ($movimentacoes as $m)
            <tr>
                <td>{{ \Carbon\Carbon::parse($m->data_movimentacao)->format('d/m/Y') }}</td>
                <td><span class="selo selo--{{ $m->tipo === 'entrada' ? 'ativo' : ($m->tipo === 'saida' ? 'encerrado' : 'teste') }}">{{ \App\Models\MovimentacaoEstoque::TIPOS[$m->tipo] }}</span></td>
                <td class="num">{{ rtrim(rtrim(number_format($m->quantidade, 3, ',', '.'), '0'), ',') }} {{ $item->unidade }}</td>
                <td class="num">{{ $m->custo_unitario ? 'R$ '.number_format($m->custo_unitario, 2, ',', '.') : '—' }}</td>
                <td>{{ $m->motivo ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif

<a href="{{ route('painel.estoque') }}" class="botao botao--texto">&larr; Voltar ao estoque</a>
@endsection
