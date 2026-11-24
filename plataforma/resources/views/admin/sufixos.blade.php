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

{{-- Vinculações: propriedades com sufixo --}}
@if ($vinculacoes->isNotEmpty())
<section class="cartao" style="margin-top:1.5rem">
    <h2>Vinculações <small class="suave">({{ $vinculacoes->count() }} propriedade(s) com sufixo)</small></h2>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Propriedade</th><th>Sufixo</th><th>Código</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($vinculacoes as $v)
            <tr>
                <td><a href="{{ route('admin.haras.editar', $v->uuid) }}"><b>{{ $v->nome }}</b></a></td>
                <td>{{ $v->sufixo }}</td>
                <td>{{ $v->sufixo_codigo ?: '—' }}</td>
                <td>
                    @if ($v->sufixo_confirmado)
                        <span class="selo selo--ativo">Confirmado</span>
                    @elseif ($v->sufixo_codigo)
                        <span class="selo selo--solicitado">Pendente</span>
                    @else
                        <span class="selo">Não verificado</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
</section>
@endif

<section class="cartao" style="margin-top:1.5rem">
    <h2>Consultar base <small class="suave">({{ number_format($total, 0, ',', '.') }} registros)</small></h2>
    <form method="get" action="{{ route('admin.sufixos') }}" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:1rem">
        <input name="busca" value="{{ request('busca') }}" placeholder="Pesquisar por código, sufixo, proprietário…" style="flex:1;min-width:200px">
        <button class="botao botao--primario botao--pequeno">Pesquisar</button>
        @if (request('busca'))<a href="{{ route('admin.sufixos') }}" class="botao botao--contorno botao--pequeno">Limpar</a>@endif
    </form>
    @if ($registros->isEmpty())
        <p class="suave">{{ request('busca') ? 'Nenhum sufixo encontrado para "'.request('busca').'".' : 'Nenhum sufixo importado ainda.' }}</p>
    @else
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Código</th><th>Sufixo</th><th>Proprietário</th><th>Cidade/UF</th><th>CEP</th><th>Celular</th><th>Vinculado a</th></tr></thead>
        <tbody>
            @foreach ($registros as $s)
            <tr>
                <td>{{ $s->codigo }}</td>
                <td><b>{{ $s->sufixo }}</b></td>
                <td>{{ $s->proprietario_nome ?: '—' }}</td>
                <td>{{ $s->cidade ?: '' }}{{ $s->uf ? '/'.$s->uf : '' }}</td>
                <td>{{ $s->cep ?: '—' }}</td>
                <td>{{ $s->proprietario_celular ?: '—' }}</td>
                <td>
                    @if ($s->empresa_nome)
                        <a href="{{ route('admin.haras.editar', $s->empresa_uuid) }}">{{ $s->empresa_nome }}</a>
                        @if ($s->empresa_confirmado) <span class="selo selo--ativo" style="margin-left:4px">✓</span> @endif
                    @else
                        <span class="suave">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
    <div style="margin-top:.75rem">{{ $registros->withQueryString()->links() }}</div>
    @endif
</section>
@endsection
