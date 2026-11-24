@extends('layouts.admin')
@section('titulo', 'Sufixos das associações')
@section('conteudo')
<div class="cabecalho"><h1>Sufixos das associações</h1></div>

@if (session('ok'))<div class="aviso aviso--ok">{{ session('ok') }}</div>@endif
@if ($errors->any())<div class="aviso aviso--erro">{{ $errors->first() }}</div>@endif

<section class="cartao">
    <h2>Importar lista de sufixos</h2>
    <p class="suave" style="margin-bottom:1rem">Envie um arquivo .csv ou .txt com as colunas separadas por tabulação ou ponto-e-vírgula. Cabeçalho: <code>codigo;sufixo;proprietario;uf;cidade;cep;celular;telefone</code>. O sistema atualiza os que já existem pelo código.</p>
    <form method="post" action="{{ route('admin.sufixos.importar') }}" enctype="multipart/form-data" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
        @csrf
        <label class="campo"><span>Arquivo (.csv ou .txt)</span><input type="file" name="arquivo" accept=".csv,.txt" required></label>
        <button class="botao botao--primario">Importar</button>
    </form>
</section>

<section class="cartao" style="margin-top:1.5rem">
    <h2>Base de sufixos <small class="suave">({{ number_format($total, 0, ',', '.') }} registros)</small></h2>
    @if ($ultimos->isEmpty())
        <p class="suave">Nenhum sufixo importado ainda.</p>
    @else
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Código</th><th>Sufixo</th><th>Proprietário</th><th>Cidade/UF</th><th>CEP</th><th>Celular</th></tr></thead>
        <tbody>
            @foreach ($ultimos as $s)
            <tr>
                <td>{{ $s->codigo }}</td>
                <td><b>{{ $s->sufixo }}</b></td>
                <td>{{ $s->proprietario_nome ?: '—' }}</td>
                <td>{{ $s->cidade ?: '' }}{{ $s->uf ? '/'.$s->uf : '' }}</td>
                <td>{{ $s->cep ?: '—' }}</td>
                <td>{{ $s->proprietario_celular ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
    @if ($total > 20)<p class="suave" style="margin-top:.5rem">Mostrando os 20 últimos de {{ number_format($total, 0, ',', '.') }} registros.</p>@endif
    @endif
</section>
@endsection
