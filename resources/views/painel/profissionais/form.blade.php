@extends('layouts.painel')
@section('titulo', 'Vincular profissional')
@section('conteudo')
<div class="cabecalho"><div><p class="migalha"><a href="{{ route('painel.profissionais') }}">Equipe</a> ›</p><h1>Vincular profissional</h1></div></div>

<section class="cartao">
    <h2>Pesquisar profissional existente</h2>
    <p class="suave" style="margin-bottom:1rem">Digite o nome ou CPF para verificar se já está cadastrado na plataforma.</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <input id="buscaProfissional" placeholder="Nome ou CPF…" style="flex:1;min-width:200px" autocomplete="off">
        <button type="button" id="btnBuscarProf" class="botao botao--primario botao--pequeno">Pesquisar</button>
    </div>
    <div id="resultadoBusca" style="margin-top:1rem"></div>
</section>

<section class="cartao" id="formCadastro">
    <h2>Cadastrar e vincular novo profissional</h2>
    <p class="suave" style="margin-bottom:1rem">Se não encontrou na pesquisa, preencha os dados abaixo.</p>
    <form method="post" action="{{ route('painel.profissionais.criar') }}" class="formulario">
        @csrf
        <div class="grade-3">
            <label class="campo"><span>Nome completo *</span><input name="nome" value="{{ old('nome') }}" required maxlength="150"></label>
            <label class="campo"><span>CPF *</span><input name="cpf" value="{{ old('cpf') }}" required inputmode="numeric" data-mascara="cpf" maxlength="14"></label>
            <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" maxlength="190"></label>
            <label class="campo"><span>Celular</span><input name="telefone" value="{{ old('telefone') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>WhatsApp</span><input name="whatsapp" value="{{ old('whatsapp') }}" data-mascara="telefone" maxlength="30"></label>
            <label class="campo"><span>CRMV (se veterinário)</span><input name="crmv" value="{{ old('crmv') }}" maxlength="30"></label>
            <label class="campo"><span>Função neste Haras</span><input name="funcao" value="{{ old('funcao') }}" maxlength="100" placeholder="Ex.: Veterinário, Tratador"></label>
            <label class="campo"><span>Data de início</span><input type="date" name="data_inicio" value="{{ old('data_inicio', date('Y-m-d')) }}"></label>
        </div>
        <div style="margin-top:1rem">
            <h3>Especialidades</h3>
            <div class="grade-opcoes" style="margin-top:.5rem">
                @foreach (\App\Models\Profissional::ESPECIALIDADES as $valor => $texto)
                <label class="opcao-check"><input type="checkbox" name="especialidades[]" value="{{ $valor }}" @checked(in_array($valor, (array) old('especialidades', $especialidadesSelecionadas)))> {{ $texto }}</label>
                @endforeach
            </div>
        </div>
        <div class="acoes-form" style="margin-top:1.5rem"><a href="{{ route('painel.profissionais') }}" class="botao botao--contorno">Cancelar</a><button class="botao botao--primario">Cadastrar e vincular</button></div>
    </form>
</section>

<script>
document.getElementById('btnBuscarProf').addEventListener('click', function() {
    var q = document.getElementById('buscaProfissional').value.trim();
    if (q.length < 2) return;
    var div = document.getElementById('resultadoBusca');
    div.innerHTML = '<p class="suave">Pesquisando…</p>';
    fetch('{{ route("painel.profissionais.buscar") }}?q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(dados) {
            if (!dados.length) { div.innerHTML = '<p class="suave">Nenhum profissional encontrado. Preencha o formulário abaixo.</p>'; return; }
            var html = '<div class="tabela-rolagem"><table class="tabela"><thead><tr><th>Nome</th><th>CPF</th><th>Cidade</th><th>Especialidades</th><th></th></tr></thead><tbody>';
            dados.forEach(function(p) {
                html += '<tr><td><b>' + p.nome + '</b></td><td>' + p.cpf + '</td><td>' + p.cidade_uf + '</td><td>' + p.especialidades + '</td>';
                html += '<td><form method="post" action="{{ route("painel.profissionais.criar") }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="cpf" value="' + p.cpf.replace(/\D/g,'') + '"><input type="hidden" name="nome" value="' + p.nome + '"><input name="funcao" placeholder="Função" maxlength="100" style="width:120px"><button class="botao botao--primario botao--pequeno">Vincular</button></form></td></tr>';
            });
            html += '</tbody></table></div>';
            div.innerHTML = html;
        });
});
document.getElementById('buscaProfissional').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnBuscarProf').click(); }
});
</script>
@endsection
