@extends('layouts.admin')
@section('titulo', $empresa->nome)
@section('conteudo')
<div class="cabecalho">
    <div><p class="migalha"><a href="{{ route('admin.haras') }}">Haras</a> ›</p><h1>{{ $empresa->nome }} <span class="selo selo--{{ $empresa->situacao }}">{{ $empresa->situacaoTexto() }}</span></h1>
        @if ($empresa->situacao_motivo)<p>Motivo: {{ $empresa->situacao_motivo }}</p>@endif</div>
    <a href="{{ route('admin.haras.editar', $empresa) }}" class="botao botao--primario"><x-icone nome="editar" :tamanho="16" /> Editar dados</a>
</div>

<div class="grade-2">
    <section class="cartao">
        <h2>Dados do Haras</h2>
        <dl class="dados">
            @if ($v003 && $empresa->tipo_propriedade)<div><dt>Tipo</dt><dd>{{ $empresa->tipoPropriedadeTexto() }}</dd></div>@endif
            <div><dt>Razão social</dt><dd>{{ $empresa->razao_social ?: '—' }}</dd></div>
            <div><dt>{{ strtoupper($empresa->tipo_documento ?? 'CPF/CNPJ') }}</dt><dd>{{ $empresa->documentoFormatado() }}</dd></div>
            <div><dt>Inscrição estadual</dt><dd>{{ $empresa->inscricao_estadual ?: '—' }}</dd></div>
            <div><dt>Área</dt><dd>{{ $empresa->area_hectares !== null ? numero($empresa->area_hectares, 2).' ha' : '—' }}</dd></div>
            <div><dt>Plano</dt><dd>{{ $empresa->plano?->nome ?? 'Sem plano' }}</dd></div>
            <div><dt>Cadastrado em</dt><dd>{{ data_local($empresa->criado_em) }}</dd></div>
        </dl>
    </section>
    <section class="cartao">
        <h2>Contato e endereço</h2>
        <dl class="dados">
            <div><dt>E-mail</dt><dd>{{ $empresa->email ?: '—' }}</dd></div>
            <div><dt>Telefone</dt><dd>{{ $empresa->telefone ?: '—' }}</dd></div>
            <div><dt>WhatsApp</dt><dd>@if ($empresa->whatsapp)<a href="https://wa.me/55{{ preg_replace('/\D/', '', $empresa->whatsapp) }}" target="_blank" rel="noopener">{{ $empresa->whatsapp }}</a>@else — @endif</dd></div>
            <div><dt>Responsável</dt><dd>{{ $empresa->responsavel_nome ?: '—' }}@if ($empresa->responsavel_telefone)<br><small>{{ $empresa->responsavel_telefone }}</small>@endif @if ($empresa->responsavel_email)<br><small>{{ $empresa->responsavel_email }}</small>@endif</dd></div>
            @if ($v003)
                @if ($empresa->telefone_residencial)<div><dt>Tel. residencial</dt><dd>{{ $empresa->telefone_residencial }}</dd></div>@endif
                @if ($empresa->telefone_celular)<div><dt>Celular</dt><dd>{{ $empresa->telefone_celular }}</dd></div>@endif
                @if ($empresa->telefone_comercial)<div><dt>Tel. comercial</dt><dd>{{ $empresa->telefone_comercial }}</dd></div>@endif
            @endif
            <div><dt>Endereço da propriedade</dt><dd>{{ collect([$empresa->logradouro, $empresa->numero, $empresa->complemento, $empresa->bairro])->filter()->implode(', ') ?: '—' }}<br>{{ $empresa->cidadeUf() }} @if ($empresa->cep)· CEP {{ substr($empresa->cep, 0, 5) }}-{{ substr($empresa->cep, 5) }}@endif</dd></div>
            @if ($v003 && ($empresa->latitude || $empresa->longitude))
            <div><dt>Coordenadas</dt><dd>{{ $empresa->latitude }}, {{ $empresa->longitude }}
                <br><a href="https://maps.google.com/?q={{ $empresa->latitude }},{{ $empresa->longitude }}" target="_blank" rel="noopener">Ver no mapa</a></dd></div>
            @endif
            @if ($v003 && $empresa->como_chegar)
            <div><dt>Como chegar</dt><dd>{{ $empresa->como_chegar }}</dd></div>
            @endif
        </dl>
    </section>
</div>

@if ($v003 && ($empresa->corresp_logradouro || $empresa->corresp_cidade))
<section class="cartao">
    <h2>Endereço do proprietário (correspondência)</h2>
    <dl class="dados">
        <div><dt>Endereço</dt><dd>{{ collect([$empresa->corresp_logradouro, $empresa->corresp_numero, $empresa->corresp_complemento, $empresa->corresp_bairro])->filter()->implode(', ') }}<br>{{ $empresa->correspCidadeUf() }} @if ($empresa->corresp_cep)· CEP {{ substr($empresa->corresp_cep, 0, 5) }}-{{ substr($empresa->corresp_cep, 5) }}@endif</dd></div>
    </dl>
</section>
@endif

@if ($v003 && (count($racas) || $empresa->preferencias_marcha || $empresa->finalidades_criacao))
<div class="grade-3-flex">
    @if (count($racas))
    <section class="cartao">
        <h2>Raças criadas</h2>
        <div class="lista-tags">@foreach ($racas as $r)<span class="tag">{{ $r }}</span>@endforeach</div>
    </section>
    @endif
    @if ($empresa->preferencias_marcha)
    <section class="cartao">
        <h2>Preferências de marcha</h2>
        <div class="lista-tags">@foreach ($empresa->marchasArray() as $m)<span class="tag">{{ \App\Models\Empresa::MARCHAS[$m] ?? $m }}</span>@endforeach</div>
    </section>
    @endif
    @if ($empresa->finalidades_criacao)
    <section class="cartao">
        <h2>Finalidade da criação</h2>
        <div class="lista-tags">@foreach ($empresa->finalidadesArray() as $f)<span class="tag">{{ \App\Models\Empresa::FINALIDADES[$f] ?? $f }}</span>@endforeach</div>
    </section>
    @endif
</div>
@endif

@if ($v003 && ($empresa->marca_descricao || $empresa->marca_arquivo || $empresa->site_url || $empresa->subsite_slug))
<section class="cartao">
    <h2>Marca e subsite</h2>
    <div style="display:flex;gap:2rem;align-items:start;flex-wrap:wrap">
        @if ($empresa->marca_arquivo)
        <div><img src="{{ asset($empresa->marca_arquivo) }}" alt="Marca do criador" style="max-height:100px;border:1px solid #ddd;border-radius:8px;padding:6px"></div>
        @endif
        <dl class="dados" style="flex:1">
            @if ($empresa->marca_descricao)<div><dt>Descrição da marca</dt><dd>{{ $empresa->marca_descricao }}</dd></div>@endif
            @if ($empresa->site_url)<div><dt>Site próprio</dt><dd><a href="{{ $empresa->site_url }}" target="_blank" rel="noopener">{{ $empresa->site_url }}</a></dd></div>@endif
            @if ($empresa->subsite_slug)<div><dt>Subsite</dt><dd>minhatropa.com.br/h/{{ $empresa->subsite_slug }}</dd></div>@endif
        </dl>
    </div>
</section>
@endif

@if ($v002)
<section class="cartao cartao--licenca">
    <div class="cartao__titulo"><h2>Licença</h2>
        <span class="selo selo--{{ $empresa->licenca_ativa ? 'ativo' : 'teste' }}">{{ $empresa->licenca_ativa ? 'Licença ativa' : 'Período gratuito' }}</span></div>
    <p class="suave">{{ $totalAnimais }} animal(is) cadastrado(s).
        @if ($empresa->origem === 'site') Cadastrado pelo próprio Haras no site. @endif
        Quantidade informada: {{ $empresa->quantidade_animais_estimada ?: '—' }}.
        @if ($empresa->licenca_ativa && $empresa->licenca_liberada_em) Liberada em {{ data_local($empresa->licenca_liberada_em) }}. @endif</p>
    <form method="post" action="{{ route('admin.haras.licenca', $empresa) }}" data-confirmar="{{ $empresa->licenca_ativa ? 'Retirar a licença deste Haras?' : 'Liberar a licença (todas as ferramentas) para este Haras?' }}">
        @csrf @method('put')
        <input type="hidden" name="licenca_ativa" value="{{ $empresa->licenca_ativa ? 0 : 1 }}">
        <button class="botao {{ $empresa->licenca_ativa ? 'botao--contorno' : 'botao--primario' }} botao--pequeno">{{ $empresa->licenca_ativa ? 'Retirar licença' : 'Liberar licença' }}</button>
    </form>
</section>
@endif

@if ($empresa->observacoes)
<section class="cartao"><h2>Observações internas</h2><p class="pre">{{ $empresa->observacoes }}</p></section>
@endif

<section class="cartao">
    <h2>Quem acessa este Haras</h2>
    @if ($vinculos->isNotEmpty())
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Situação</th><th class="num"></th></tr></thead>
        <tbody>
        @foreach ($vinculos as $u)
            <tr><td><b>{{ $u->nome }}</b></td><td>{{ $u->email }}</td>
                <td colspan="3">
                    <form method="post" action="{{ route('admin.haras.usuarios.alterar', [$empresa, $u->pivot->id]) }}" class="linha-form">@csrf @method('put')
                        <select name="papel">@foreach (\App\Models\EmpresaUsuario::PAPEIS as $valor => $texto)<option value="{{ $valor }}" @selected($u->pivot->papel === $valor)>{{ $texto }}</option>@endforeach</select>
                        <select name="status"><option value="ativo" @selected($u->pivot->status === 'ativo')>Ativo</option><option value="desativado" @selected($u->pivot->status === 'desativado')>Desativado</option></select>
                        <button class="botao botao--contorno botao--pequeno">Salvar</button>
                    </form>
                </td></tr>
        @endforeach
        </tbody>
    </table></div>
    @endif
    <details class="recolhivel" @if ($errors->has('email') || $vinculos->isEmpty()) open @endif>
        <summary>+ Dar acesso a uma pessoa</summary>
        <form method="post" action="{{ route('admin.haras.usuarios.vincular', $empresa) }}" class="formulario">
            @csrf
            <div class="grade-3">
                <label class="campo"><span>Nome</span><input name="nome" value="{{ old('nome') }}" required maxlength="150"></label>
                <label class="campo"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" required maxlength="190"></label>
                <label class="campo"><span>Telefone</span><input name="telefone" value="{{ old('telefone') }}" data-mascara="telefone" maxlength="30"></label>
                <label class="campo"><span>Papel</span><select name="papel">@foreach (\App\Models\EmpresaUsuario::PAPEIS as $valor => $texto)<option value="{{ $valor }}" @selected(old('papel', 'gerente') === $valor)>{{ $texto }}</option>@endforeach</select></label>
                <label class="campo"><span>Senha provisória (opcional)</span><input type="text" name="senha" minlength="10" autocomplete="off"><small>Se a pessoa já tem cadastro, deixe em branco.</small></label>
            </div>
            <button class="botao botao--primario botao--pequeno">Dar acesso</button>
        </form>
    </details>
</section>

<div class="grade-2">
    <section class="cartao">
        <h2>Situação</h2>
        <form method="post" action="{{ route('admin.haras.situacao', $empresa) }}" class="formulario">
            @csrf @method('put')
            <div class="opcoes-situacao">
                @foreach (\App\Models\Empresa::SITUACOES as $valor => $texto)
                    <label class="opcao-cartao"><input type="radio" name="situacao" value="{{ $valor }}" @checked($empresa->situacao === $valor)><span class="selo selo--{{ $valor }}">{{ $texto }}</span></label>
                @endforeach
            </div>
            <label class="campo"><span>Motivo (obrigatório para suspender ou encerrar)</span><input name="situacao_motivo" value="{{ old('situacao_motivo', $empresa->situacao_motivo) }}" maxlength="255"></label>
            <button class="botao botao--contorno botao--pequeno">Alterar situação</button>
        </form>
    </section>
    <section class="cartao">
        <h2>Histórico</h2>
        @forelse ($auditoria as $a)
            <div class="linha-lista"><span><code>{{ $a->acao }}</code><small>{{ $a->usuario?->nome ?? '—' }}</small></span><small>{{ data_local($a->criado_em) }}</small></div>
        @empty
            <p class="suave">Sem registros.</p>
        @endforelse
    </section>
</div>
@endsection
