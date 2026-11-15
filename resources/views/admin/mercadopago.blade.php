@extends('layouts.admin')
@section('titulo', 'Mercado Pago')

@php
    $retorno = \App\Pagamentos\MercadoPago::retornoUrl();
    $webhook = url(config('pagamentos.mercadopago.webhook'));
@endphp

@section('conteudo')
<div class="cabecalho">
    <div><h1>Mercado Pago</h1><p>Credenciais da aplicação da BH Software para cobranças e assinaturas do MinhaTropa.</p></div>
    <div>@if ($configurado)<span class="selo selo--verde">Configurada</span>@else<span class="selo selo--amarelo">Pendente</span>@endif</div>
</div>

<section class="cartao" style="padding:20px">
    <h2 style="margin-top:0">Configuração da aplicação</h2>
    <div class="form-grade" style="margin-bottom:14px">
        <div class="campo col-6"><label>URL de redirecionamento (OAuth)</label>
            <div class="copiar"><input id="adm-retorno" value="{{ $retorno }}" readonly><button type="button" class="botao botao--contorno botao--pequeno" data-copiar="adm-retorno">Copiar</button></div></div>
        <div class="campo col-6"><label>Webhook (evento Pagamentos)</label>
            <div class="copiar"><input id="adm-webhook" value="{{ $webhook }}" readonly><button type="button" class="botao botao--contorno botao--pequeno" data-copiar="adm-webhook">Copiar</button></div></div>
    </div>
    <p><a href="https://www.mercadopago.com.br/developers/panel/app" target="_blank" rel="noopener" class="botao botao--escuro botao--pequeno">Abrir painel de desenvolvedores ↗</a></p>
</section>

@php
    $ambientes = [
        'teste' => ['n' => 1, 'titulo' => 'Ambiente de TESTE', 'classe' => 'chaves--teste', 'selo' => ['selo--azul', 'Sem dinheiro real'],
            'ajuda' => 'Credenciais de teste do Mercado Pago. Usado para validar a integração.'],
        'producao' => ['n' => 2, 'titulo' => 'Ambiente de PRODUÇÃO', 'classe' => 'chaves--producao', 'selo' => ['selo--verde', 'Cobranças reais'],
            'ajuda' => 'Credenciais de produção do Mercado Pago. Usadas nas cobranças reais dos Haras.'],
    ];
    $prefixo = ['teste' => 'teste_', 'producao' => ''];
    $nomesApp = ['client_id' => ['Client ID', 'text'], 'client_secret' => ['Client Secret', 'password']];
    $nomes = ['access_token' => ['Access Token', 'password'], 'public_key' => ['Public Key', 'text'], 'webhook_secret' => ['Assinatura secreta dos webhooks', 'password']];
    $v = session('verificacao');
@endphp

@foreach ($ambientes as $amb => $a)
<section class="cartao" style="padding:20px;margin-top:18px" id="ambiente-{{ $amb }}">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px"><h2 style="margin:0">{{ $a['n'] }}. {{ $a['titulo'] }}</h2><span class="selo {{ $a['selo'][0] }}">{{ $a['selo'][1] }}</span></div>
    <p class="suave">{{ $a['ajuda'] }}</p>

    <form method="post" action="{{ route('admin.mercadopago.salvar') }}" autocomplete="off">
        @csrf
        <div class="form-grade">
            @if ($amb === 'producao')
                @foreach ($nomesApp as $campo => [$rotulo, $tipo])
                    <div class="campo col-6">
                        <label for="adm-{{ $campo }}">{{ $rotulo }}</label>
                        <input id="adm-{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" autocomplete="off" spellcheck="false"
                            placeholder="{{ $credenciais[$campo]['definida'] ? 'Atual: '.$credenciais[$campo]['dica'].' — em branco mantém' : 'Cole aqui' }}">
                        @error($campo)<small class="erro">{{ $message }}</small>@enderror
                    </div>
                @endforeach
            @else
                @foreach (['teste_client_id' => ['Client ID de teste', 'text'], 'teste_client_secret' => ['Client Secret de teste', 'password']] as $campo => [$rotulo, $tipo])
                    <div class="campo col-6">
                        <label for="adm-{{ $campo }}">{{ $rotulo }}</label>
                        <input id="adm-{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" autocomplete="off" spellcheck="false"
                            placeholder="{{ $credenciais[$campo]['definida'] ? 'Atual: '.$credenciais[$campo]['dica'].' — em branco mantém' : 'Cole aqui' }}">
                        @error($campo)<small class="erro">{{ $message }}</small>@enderror
                    </div>
                @endforeach
            @endif
            @foreach ($nomes as $base => [$rotulo, $tipo])
                @php $campo = $prefixo[$amb].$base; @endphp
                <div class="campo col-6">
                    <label for="adm-{{ $campo }}">{{ $rotulo }}{{ $amb === 'teste' ? ' de teste' : '' }}</label>
                    <input id="adm-{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" autocomplete="off" spellcheck="false"
                        placeholder="{{ $credenciais[$campo]['definida'] ? 'Atual: '.$credenciais[$campo]['dica'].' — em branco mantém' : 'Cole aqui' }}">
                    @error($campo)<small class="erro">{{ $message }}</small>@enderror
                </div>
            @endforeach
        </div>
        <p class="suave" style="margin:4px 0 10px"><small>Os segredos ficam criptografados no banco. Campo em branco mantém o valor atual.</small></p>
        <div class="acoes-form"><button class="botao botao--primario">Salvar chaves de {{ $amb === 'teste' ? 'teste' : 'produção' }}</button></div>
    </form>

    <div class="teste-pagamento" style="margin-top:14px;display:flex;align-items:center;gap:10px">
        <form method="post" action="{{ route('admin.mercadopago.verificar') }}">@csrf
            <input type="hidden" name="ambiente" value="{{ $amb }}">
            <button class="botao botao--contorno botao--pequeno">Verificar de quem é o token</button></form>
        @if ($v && isset($v[$amb]))
            @php $i = $v[$amb]; @endphp
            <span>
                @if (! $i) <span class="suave">Token não cadastrado.</span>
                @elseif (isset($i['erro'])) <span class="erro">{{ $i['erro'] }}</span>
                @else Conta <b>{{ $i['id'] }}</b> · {{ $i['apelido'] }} <small>{{ $i['email'] }}</small>
                    @if ($i['teste'])<span class="selo selo--azul">Conta de teste</span>@else<span class="selo selo--cinza">Conta real</span>@endif
                @endif
            </span>
        @endif
    </div>
</section>
@endforeach

<section class="cartao" style="padding:20px;margin-top:18px" id="pagamento-teste">
    <h2 style="margin-top:0">3. Pagamento de teste</h2>
    <form method="post" action="{{ route('admin.mercadopago.teste') }}" class="form-grade">
        @csrf
        <div class="campo col-12"><div style="display:flex;gap:12px">
            <label style="flex:1;border:1px solid var(--borda);border-radius:6px;padding:10px;cursor:pointer">
                <input type="radio" name="ambiente" value="teste" @checked(old('ambiente', 'teste') === 'teste')>
                <b>Teste</b> <small class="suave">sem dinheiro real</small></label>
            <label style="flex:1;border:1px solid var(--borda);border-radius:6px;padding:10px;cursor:pointer">
                <input type="radio" name="ambiente" value="producao" @checked(old('ambiente') === 'producao')>
                <b>Produção</b> <small class="suave">cobrança real</small></label>
        </div></div>
        <div class="campo col-4"><label>CPF</label><input name="cpf" required value="{{ old('cpf', '123.456.789-09') }}">@error('cpf')<small class="erro">{{ $message }}</small>@enderror</div>
        <div class="campo col-8"><label>Nome completo</label><input name="nome" required value="{{ old('nome', 'APRO Teste') }}">@error('nome')<small class="erro">{{ $message }}</small>@enderror</div>
        <div class="campo col-6"><label>E-mail do comprador</label><input name="email" type="email" required value="{{ old('email') }}" placeholder="e-mail da conta compradora de teste">@error('email')<small class="erro">{{ $message }}</small>@enderror</div>
        <div class="campo col-3"><label>Valor (R$)</label>
            <input name="valor" type="number" step="0.01" min="1" max="100" value="{{ old('valor', '5.00') }}" required></div>
        <div class="col-12">@error('valor')<p class="erro" style="color:var(--vermelho);margin:0 0 8px">{{ $message }}</p>@enderror
            <button class="botao botao--primario">Gerar pagamento</button></div>
    </form>
</section>

<form method="post" action="{{ route('admin.mercadopago.salvar') }}" class="cartao" style="padding:20px;margin-top:18px">
    @csrf
    <input type="hidden" name="so_pkce" value="1">
    <h2 style="margin-top:0">Segurança da conexão (PKCE)</h2>
    <p class="suave">Opcional. Ative nos dois lados (aqui e na aplicação do Mercado Pago).</p>
    <label style="display:flex;gap:8px;align-items:center"><input type="hidden" name="pkce" value="0"><input type="checkbox" name="pkce" value="1" @checked($pkce)> Usar PKCE</label>
    <div class="acoes-form"><button class="botao botao--escuro botao--pequeno">Salvar</button></div>
</form>

<section class="cartao" style="padding:20px;margin-top:18px">
    <h2 style="margin-top:0">Últimos avisos (webhooks)</h2>
    @if ($avisos->isEmpty())
        <p class="suave">Nenhum aviso recebido ainda.</p>
    @else
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr><th>Recebido</th><th>Tipo / ação</th><th>ID do recurso</th><th>Assinatura</th><th>Situação</th></tr></thead>
            <tbody>
            @foreach ($avisos as $a)
                <tr>
                    <td>{{ data_local($a->criado_em) }}</td>
                    <td>{{ $a->tipo ?? '—' }}@if ($a->acao)<br><small>{{ $a->acao }}</small>@endif</td>
                    <td>{{ $a->recurso_id ?? '—' }}</td>
                    <td>@if ($a->assinatura_valida)<span class="selo selo--verde">Válida{{ $a->erro === 'Assinatura de teste' ? ' (teste)' : '' }}</span>@else<span class="selo selo--cinza">Inválida</span>@endif</td>
                    <td>{{ $a->status }}@if ($a->erro && ! $a->assinatura_valida)<br><small>{{ $a->erro }}</small>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
</section>
@endsection
