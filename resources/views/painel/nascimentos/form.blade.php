@extends('layouts.painel')
@section('titulo', 'Registrar nascimento')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.nascimentos') }}">Nascimentos</a> ›</p><h1>Registrar nascimento</h1></div></div>
@php $v = fn ($c) => old($c, $nascimento->{$c}); @endphp
<form method="post" action="{{ route('painel.nascimentos.criar') }}" class="formulario">
    @csrf
    <section class="cartao"><h2>Dados do nascimento</h2>
        <div class="grade-3">
            <label class="campo"><span>Cobrição vinculada</span>
                <select name="cobricao_id" id="cobricaoSelect">
                    <option value="">— Nenhuma —</option>
                    @foreach ($cobricoes as $c)
                        <option value="{{ $c->id }}" data-egua="{{ $c->egua_id }}" data-garanhao="{{ $c->garanhao_id }}" @selected($v('cobricao_id') == $c->id)>Cobrição {{ $c->data_cobricao->format('d/m/Y') }} (Égua #{{ $c->egua_id }})</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Mãe *</span>
                <select name="mae_id" id="maeSelect" required>
                    <option value="">Selecione…</option>
                    @foreach ($maes as $m)
                        <option value="{{ $m->id }}" @selected($v('mae_id') == $m->id)>{{ $m->nome }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Pai (cadastrado)</span>
                <select name="pai_id" id="paiSelect">
                    <option value="">— Não selecionado —</option>
                    @foreach ($pais as $p)
                        <option value="{{ $p->id }}" @selected($v('pai_id') == $p->id)>{{ $p->nome }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Pai (nome, se não cadastrado)</span><input name="pai_nome" value="{{ $v('pai_nome') }}" maxlength="150" placeholder="Nome do pai"></label>
            <label class="campo"><span>Data do nascimento *</span><input type="date" name="data_nascimento" value="{{ old('data_nascimento', $nascimento->data_nascimento?->format('Y-m-d')) }}" required></label>
            <label class="campo"><span>Tipo de parto *</span>
                <select name="tipo_parto" required>
                    @foreach (\App\Models\Nascimento::TIPOS_PARTO as $k => $t)
                        <option value="{{ $k }}" @selected(($v('tipo_parto') ?? 'normal') === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </label>
            <label class="campo"><span>Sexo</span>
                <select name="sexo">
                    <option value="">—</option>
                    <option value="macho" @selected($v('sexo') === 'macho')>Macho</option>
                    <option value="femea" @selected($v('sexo') === 'femea')>Fêmea</option>
                    <option value="indefinido" @selected($v('sexo') === 'indefinido')>Indefinido</option>
                </select>
            </label>
            <label class="campo"><span>Nome do produto</span><input name="nome_produto" value="{{ $v('nome_produto') }}" maxlength="120"></label>
            <label class="campo"><span>Peso ao nascer (kg)</span><input type="number" step="0.1" name="peso_nascimento" value="{{ $v('peso_nascimento') }}" min="0" max="200"></label>
            <label class="campo"><span>Veterinário</span>
                <select name="veterinario_id">
                    <option value="">—</option>
                    @foreach ($veterinarios as $vet)
                        <option value="{{ $vet->id }}" @selected($v('veterinario_id') == $vet->id)>{{ $vet->nome }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div style="margin-top:1rem">
            <label class="opcao-check"><input type="hidden" name="vivo" value="0"><input type="checkbox" name="vivo" value="1" @checked(old('vivo', $nascimento->vivo ?? true))> Nasceu vivo</label>
            <label class="opcao-check" style="margin-top:.5rem"><input type="checkbox" name="criar_animal" value="1" @checked(old('criar_animal', true))> Cadastrar automaticamente como novo animal</label>
        </div>
    </section>
    <section class="cartao"><h2>Observações</h2><label class="campo"><textarea name="observacoes" rows="3" maxlength="5000">{{ $v('observacoes') }}</textarea></label></section>
    <div class="acoes-form"><a href="{{ route('painel.nascimentos') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">Registrar nascimento</button></div>
</form>
<script>
document.getElementById('cobricaoSelect').addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    if (opt.value) {
        var egua = opt.getAttribute('data-egua');
        var gar = opt.getAttribute('data-garanhao');
        if (egua) document.getElementById('maeSelect').value = egua;
        if (gar) document.getElementById('paiSelect').value = gar;
    }
});
</script>
@endsection
