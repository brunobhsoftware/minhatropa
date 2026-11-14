@extends('layouts.painel')
@section('titulo', 'Nova transferência')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.transferencias') }}">Transferências</a> ›</p><h1>Nova transferência</h1></div></div>
@php $v = fn ($c) => old($c, $transferencia->{$c}); @endphp
<form method="post" action="{{ route('painel.transferencias.criar') }}" class="formulario">
    @csrf
    <section class="cartao"><h2>Dados da transferência</h2>
        <div class="grade-3">
            <label class="campo"><span>Animal *</span>
                <select name="animal_id" required>
                    <option value="">Selecione…</option>
                    @foreach ($animais as $a)
                        <option value="{{ $a->id }}" @selected($v('animal_id') == $a->id)>{{ $a->nome }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Tipo *</span>
                <select name="tipo" required>
                    @foreach (\App\Models\Transferencia::TIPOS as $k => $t)
                        <option value="{{ $k }}" @selected(($v('tipo') ?? 'venda') === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Data *</span><input type="date" name="data_transferencia" value="{{ old('data_transferencia', $transferencia->data_transferencia?->format('Y-m-d') ?? date('Y-m-d')) }}" required></label>
            <label class="campo"><span>Valor (R$)</span><input type="number" step="0.01" name="valor" value="{{ $v('valor') }}" min="0"></label>
            <label class="campo"><span>Situação *</span>
                <select name="situacao" required>
                    @foreach (\App\Models\Transferencia::SITUACOES as $k => $t)
                        <option value="{{ $k }}" @selected(($v('situacao') ?? 'concluida') === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="cartao"><h2>Destino</h2>
        <div style="margin-bottom:1rem">
            <label class="opcao-check"><input type="radio" name="destino_tipo" value="plataforma" @checked(old('destino_tipo', 'plataforma') === 'plataforma') onchange="toggleDestino()"> Haras da plataforma</label>
            <label class="opcao-check" style="margin-left:1.5rem"><input type="radio" name="destino_tipo" value="externo" @checked(old('destino_tipo') === 'externo') onchange="toggleDestino()"> Destino externo</label>
        </div>
        <div id="destinoPlataforma">
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input id="buscaHaras" placeholder="Pesquisar Haras por nome…" style="flex:1;min-width:200px" autocomplete="off">
                <button type="button" id="btnBuscarHaras" class="botao botao--contorno botao--pequeno">Pesquisar</button>
            </div>
            <div id="resultadoHaras" style="margin-top:.5rem"></div>
            <input type="hidden" name="empresa_destino_id" id="empresaDestinoId" value="{{ $v('empresa_destino_id') }}">
            <p id="harasSelecionado" class="suave" style="margin-top:.5rem"></p>
        </div>
        <div id="destinoExterno" style="display:none">
            <label class="campo"><span>Nome do destino</span><input name="destino_externo" value="{{ $v('destino_externo') }}" maxlength="200" placeholder="Nome do comprador, Haras, fazenda…"></label>
        </div>
    </section>

    <section class="cartao"><h2>Observações</h2><label class="campo"><textarea name="observacoes" rows="3" maxlength="5000">{{ $v('observacoes') }}</textarea></label></section>
    <div class="acoes-form"><a href="{{ route('painel.transferencias') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">Registrar transferência</button></div>
</form>
<script>
function toggleDestino() {
    var tipo = document.querySelector('input[name=destino_tipo]:checked').value;
    document.getElementById('destinoPlataforma').style.display = tipo === 'plataforma' ? '' : 'none';
    document.getElementById('destinoExterno').style.display = tipo === 'externo' ? '' : 'none';
}
toggleDestino();

document.getElementById('btnBuscarHaras').addEventListener('click', function() {
    var q = document.getElementById('buscaHaras').value.trim();
    if (q.length < 2) return;
    var div = document.getElementById('resultadoHaras');
    div.innerHTML = '<p class="suave">Pesquisando…</p>';
    fetch('{{ route("painel.transferencias.buscar-haras") }}?q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(dados) {
            if (!dados.length) { div.innerHTML = '<p class="suave">Nenhum Haras encontrado.</p>'; return; }
            var html = '';
            dados.forEach(function(h) {
                html += '<button type="button" class="botao botao--contorno botao--pequeno" style="margin:2px" onclick="selecionarHaras(' + h.id + ',\'' + h.nome.replace(/'/g, "\\'") + '\')">' + h.nome + ' — ' + h.cidade_uf + '</button> ';
            });
            div.innerHTML = html;
        });
});
document.getElementById('buscaHaras').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnBuscarHaras').click(); }
});
function selecionarHaras(id, nome) {
    document.getElementById('empresaDestinoId').value = id;
    document.getElementById('harasSelecionado').textContent = 'Selecionado: ' + nome;
    document.getElementById('resultadoHaras').innerHTML = '';
}
</script>
@endsection
