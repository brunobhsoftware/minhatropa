@extends('layouts.admin')
@section('titulo', 'Planos e módulos')
@section('conteudo')
@php $dinheiro = fn ($v) => $v === null ? '' : number_format((float) $v, 2, ',', '.'); @endphp
<div class="cabecalho"><div><h1>Planos e módulos</h1><p>O plano base de cada Haras / Fazenda é a faixa de plantel (animais ativos). Os módulos são contratados à parte.</p></div></div>

@if ($pedidosFaixa->isNotEmpty() || $pedidosModulo->isNotEmpty())
<section class="cartao cartao--destaque">
    <h2>Pedidos aguardando liberação</h2>
    @foreach ($pedidosFaixa as $p)
        <a class="linha-lista" href="{{ route('admin.haras.show', $p->uuid) }}#plano"><span><b>{{ $p->nome }}</b><small>Upgrade de faixa: {{ $p->faixa }}</small></span><small>{{ data_local($p->solicitado_em) }}</small></a>
    @endforeach
    @foreach ($pedidosModulo as $p)
        <a class="linha-lista" href="{{ route('admin.haras.show', $p->uuid) }}#modulos"><span><b>{{ $p->nome }}</b><small>Módulo: {{ $p->modulo }}</small></span><small>{{ data_local($p->solicitado_em) }}</small></a>
    @endforeach
</section>
@endif

<section class="cartao">
    <h2>Faixas de plantel</h2>
    <p class="suave" style="margin-bottom:1rem">Valor em branco aparece para o cliente como "Sob consulta"; valor 0 é gratuito. Último animal em branco = sem limite. A primeira faixa ativa é a que todo cadastro novo recebe.</p>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Nome</th><th>Do animal</th><th>Até o animal</th><th>Valor mensal (R$)</th><th>Ativa</th><th>Em uso</th><th></th></tr></thead>
        <tbody>
        @foreach ($faixas as $f)
            @php $fid = 'faixa-'.$f->id; @endphp
            <tr data-faixa="{{ $f->codigo }}">
                <td><input form="{{ $fid }}" name="nome" value="{{ $f->nome }}" required maxlength="100" aria-label="Nome"><input form="{{ $fid }}" type="hidden" name="descricao" value="{{ $f->descricao }}"></td>
                <td><input form="{{ $fid }}" name="limite_min" type="number" min="0" value="{{ $f->limite_min }}" required style="width:90px" aria-label="Primeiro animal"></td>
                <td><input form="{{ $fid }}" name="limite_max" type="number" min="0" value="{{ $f->limite_max }}" placeholder="sem limite" style="width:110px" aria-label="Último animal"></td>
                <td><input form="{{ $fid }}" name="valor_mensal" value="{{ $dinheiro($f->valor_mensal) }}" placeholder="a definir" inputmode="decimal" style="width:120px" aria-label="Valor mensal"></td>
                <td><input form="{{ $fid }}" type="checkbox" name="ativo" value="1" @checked($f->ativo) aria-label="Ativa"></td>
                <td>{{ $usoFaixas[$f->id] ?? 0 }}</td>
                <td><form id="{{ $fid }}" method="post" action="{{ route('admin.planos.faixas.salvar', $f->id) }}">@csrf @method('put')<button class="botao botao--contorno botao--pequeno">Salvar</button></form></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <details class="recolhivel">
        <summary>+ Nova faixa</summary>
        <form method="post" action="{{ route('admin.planos.faixas.criar') }}" class="formulario">
            @csrf
            <div class="grade-3">
                <label class="campo"><span>Nome *</span><input name="nome" value="{{ old('nome') }}" required maxlength="100" placeholder="Ex.: Faixa 5"></label>
                <label class="campo"><span>Do animal *</span><input name="limite_min" type="number" min="0" value="{{ old('limite_min') }}" required placeholder="Ex.: 101"></label>
                <label class="campo"><span>Até o animal</span><input name="limite_max" type="number" min="0" value="{{ old('limite_max') }}" placeholder="Em branco = sem limite"></label>
                <label class="campo"><span>Valor mensal (R$)</span><input name="valor_mensal" value="{{ old('valor_mensal') }}" inputmode="decimal" placeholder="Em branco = a definir"></label>
                <label class="campo campo--duplo"><span>Descrição</span><input name="descricao" value="{{ old('descricao') }}" maxlength="255"></label>
            </div>
            <button class="botao botao--primario botao--pequeno">Criar faixa</button>
        </form>
    </details>
</section>

<section class="cartao">
    <h2>Módulos adicionais</h2>
    <p class="suave" style="margin-bottom:1rem">Módulo inativo some do menu dos clientes. A liberação por propriedade é feita na tela de cada Haras / Fazenda.</p>
    @foreach ($modulos as $m)
    <details class="recolhivel" style="margin-top:0">
        <summary>{{ $m->nome }} — {{ $m->valorTexto() }} · {{ $usoModulos[$m->id] ?? 0 }} propriedade(s) com o módulo{{ $m->ativo ? '' : ' · inativo' }}</summary>
        <form method="post" action="{{ route('admin.planos.modulos.salvar', $m) }}" class="formulario">@csrf @method('put')
            <div class="grade-3">
                <label class="campo campo--duplo"><span>Nome *</span><input name="nome" value="{{ $m->nome }}" required maxlength="100"></label>
                <label class="campo"><span>Valor mensal (R$)</span><input name="valor_mensal" value="{{ $dinheiro($m->valor_mensal) }}" inputmode="decimal" placeholder="Em branco = a definir"></label>
            </div>
            <label class="campo"><span>Resumo</span><input name="resumo" value="{{ $m->resumo }}" maxlength="255"></label>
            <label class="campo"><span>Benefícios (um por linha, aparecem na tela explicativa)</span><textarea name="beneficios" rows="4" maxlength="3000">{{ $m->beneficios }}</textarea></label>
            <label class="opcao"><input type="checkbox" name="ativo" value="1" @checked($m->ativo)> Oferecer este módulo aos clientes</label>
            <div><button class="botao botao--contorno botao--pequeno">Salvar módulo</button></div>
        </form>
    </details>
    @endforeach
</section>
@endsection
