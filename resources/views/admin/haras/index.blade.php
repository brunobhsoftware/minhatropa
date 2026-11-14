@extends('layouts.admin')
@section('titulo', 'Haras / Fazendas')
@section('conteudo')
<div class="cabecalho">
    <div><h1>Haras / Fazendas</h1><p>Todas as propriedades que usam a plataforma, identificadas por CPF ou CNPJ.</p></div>
    <a href="{{ route('admin.haras.novo') }}" class="botao botao--primario"><x-icone nome="mais" :tamanho="18" /> Novo Haras / Fazenda</a>
</div>
<form class="filtros" method="get">
    <input type="search" name="busca" value="{{ $busca }}" placeholder="Nome, CPF/CNPJ ou responsável">
    <span class="filtro-geo"><x-uf-municipio :uf="$uf" :municipio-id="$municipioId ?: null" :cidade="$cidadeFiltro" filtro /></span>
    <select name="situacao"><option value="">Todas as situações</option>
        @foreach (\App\Models\Empresa::SITUACOES as $valor => $texto)<option value="{{ $valor }}" @selected($situacao === $valor)>{{ $texto }}</option>@endforeach
    </select>
    <button class="botao botao--contorno"><x-icone nome="busca" :tamanho="16" /> Filtrar</button>
</form>
@if ($documentosRepetidos->isNotEmpty())
<div class="alerta alerta--erro"><p><b>CPF/CNPJ repetido:</b> {{ $documentosRepetidos->map(fn ($d) => \App\Support\Documento::formatar($d))->implode(', ') }}. Corrija os cadastros e aplique o V006 de novo em "Atualizações do banco" para criar a trava de documento único.</p></div>
@endif
@if ($semDocumento > 0)
<div class="alerta alerta--aviso"><p>{{ $semDocumento }} cadastro(s) antigo(s) ainda sem CPF/CNPJ. O documento agora é obrigatório: complete ao editar.</p></div>
@endif
<section class="cartao">
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Haras / Fazenda</th><th>CPF/CNPJ</th><th>Cidade/UF</th><th>Responsável</th><th class="num">Usuários</th><th>Situação</th></tr></thead>
        <tbody>
        @forelse ($haras as $h)
            <tr class="clicavel" data-href="{{ route('admin.haras.show', $h) }}">
                <td><a href="{{ route('admin.haras.show', $h) }}"><b>{{ $h->nome }}</b></a> <small class="suave">{{ $h->rotulo() }}</small>@if ($h->razao_social)<br><small class="suave">{{ $h->razao_social }}</small>@endif</td>
                <td>{{ $h->documentoFormatado() }}</td>
                <td>{{ $h->cidadeUf() }}</td>
                <td>{{ $h->responsavel_nome ?: '—' }}</td>
                <td class="num">{{ $h->usuarios_count }}</td>
                <td><span class="selo selo--{{ $h->situacao }}">{{ $h->situacaoTexto() }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="vazio"><p>{{ $busca || $situacao || $uf ? 'Nenhum Haras / Fazenda encontrado com esse filtro.' : 'Nenhum Haras / Fazenda cadastrado ainda.' }}</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
{{ $haras->links() }}
@endsection
