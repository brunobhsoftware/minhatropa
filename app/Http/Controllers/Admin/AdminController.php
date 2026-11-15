<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Contato;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Pagamentos\MercadoPago;
use App\Support\AtualizacaoBanco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function inicio()
    {
        $haras = Empresa::naoExcluidas();

        return view('admin.inicio', [
            'totalHaras' => (clone $haras)->count(),
            'porSituacao' => (clone $haras)->selectRaw('situacao, COUNT(*) total')->groupBy('situacao')->pluck('total', 'situacao'),
            'totalUsuarios' => Usuario::where('ativo', 1)->count(),
            'contatosNovos' => Contato::whereNull('lido_em')->count(),
            'bancoPendente' => AtualizacaoBanco::pendentes(),
            'ultimosHaras' => (clone $haras)->latest('criado_em')->limit(5)->get(),
            'ultimosContatos' => Contato::latest('criado_em')->limit(5)->get(),
            'mpConfigurado' => \App\Support\Esquema::temV015() ? MercadoPago::configurado() : false,
        ]);
    }

    public function auditoria(Request $request)
    {
        $busca = trim((string) $request->query('busca'));
        $registros = Auditoria::with('usuario')
            ->when($busca !== '', fn ($q) => $q->where(fn ($q) => $q->where('acao', 'like', "%{$busca}%")->orWhere('tabela', 'like', "%{$busca}%")))
            ->orderByDesc('id')->paginate(50)->withQueryString();

        return view('admin.auditoria', compact('registros', 'busca'));
    }

    // ---------- Mercado Pago ----------

    public function mercadoPago()
    {
        return view('admin.mercadopago', [
            'credenciais' => MercadoPago::situacaoCredenciais(),
            'configurado' => MercadoPago::configurado(),
            'pkce' => MercadoPago::usaPkce(),
            'avisos' => DB::table('webhook_evento')->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function salvarMercadoPago(Request $request)
    {
        $dados = $request->validate([
            'client_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'client_secret' => ['nullable', 'string', 'max:200', 'regex:/^\S+$/'],
            'webhook_secret' => ['nullable', 'string', 'max:200', 'regex:/^\S+$/'],
            'teste_access_token' => ['nullable', 'string', 'max:300', 'regex:/^(APP_USR|TEST)-\S+$/'],
            'access_token' => ['nullable', 'string', 'max:300', 'regex:/^APP_USR-\S+$/'],
            'teste_webhook_secret' => ['nullable', 'string', 'max:200', 'regex:/^\S+$/'],
            'public_key' => ['nullable', 'string', 'max:255', 'regex:/^APP_USR-\S+$/'],
            'teste_public_key' => ['nullable', 'string', 'max:255', 'regex:/^(APP_USR|TEST)-\S+$/'],
            'teste_client_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'teste_client_secret' => ['nullable', 'string', 'max:200', 'regex:/^\S+$/'],
        ], [
            'access_token.regex' => 'O Access Token de produção começa com APP_USR- e não tem espaços.',
            'teste_access_token.regex' => 'O Access Token de teste começa com APP_USR- (ou TEST-) e não tem espaços.',
            'public_key.regex' => 'A Public Key de produção começa com APP_USR- e não tem espaços.',
            'teste_public_key.regex' => 'A Public Key de teste começa com APP_USR- (ou TEST-) e não tem espaços.',
            'client_id.regex' => 'O Client ID tem só letras, números, "-" e "_".',
            'client_secret.regex' => 'O Client Secret não pode ter espaços.',
            'webhook_secret.regex' => 'A assinatura secreta não pode ter espaços.',
        ]);

        $pkce = $request->boolean('pkce');
        if ($pkce !== MercadoPago::usaPkce()) {
            MercadoPago::definirPkce($pkce);
            \App\Support\Auditoria::registrar('plataforma.mercadopago_pkce', $request->user(), null, ['pkce' => $pkce]);
        }
        $alteradas = array_keys(array_filter($dados, 'filled'));
        if (! $alteradas && $request->has('so_pkce')) {
            return redirect()->route('admin.mercadopago')->with('ok', 'PKCE '.($pkce ? 'ativado' : 'desativado').'.');
        }
        if (! $alteradas) {
            return back()->withErrors(['client_id' => 'Preencha pelo menos um campo. Campos em branco mantêm o valor atual.']);
        }
        MercadoPago::salvarCredenciais($dados);
        \App\Support\Auditoria::registrar('plataforma.mercadopago_credenciais', $request->user(), null, ['alteradas' => $alteradas]);

        return redirect()->route('admin.mercadopago')->with('ok', 'Chaves do Mercado Pago salvas. Os segredos ficam criptografados no banco.');
    }

    public function testarPagamento(Request $request)
    {
        $d = $request->validate([
            'valor' => ['required', 'numeric', 'min:1', 'max:100'],
            'ambiente' => ['required', 'in:teste,producao'],
            'email' => ['required', 'email:filter', 'max:150'],
            'nome' => ['required', 'string', 'max:150'],
            'cpf' => ['required', 'string', 'regex:/^\D*(\d\D*){11}$/'],
        ], ['cpf.regex' => 'Informe os 11 números do CPF.'],
            ['valor' => 'valor', 'email' => 'e-mail do comprador', 'nome' => 'nome completo do comprador']);

        $partes = preg_split('/\s+/', trim($d['nome']), 2);
        [$primeiro, $sobrenome] = [$partes[0], $partes[1] ?? $partes[0]];
        $valor = number_format((float) $d['valor'], 2, '.', '');
        $teste = $d['ambiente'] === 'teste';
        try {
            $token = $teste ? MercadoPago::tokenTeste() : MercadoPago::tokenAplicacao();
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['valor' => $e->getMessage()]);
        }
        if (! $token) {
            return back()->withInput()->withErrors(['valor' => $teste
                ? 'Cadastre primeiro as chaves de teste (Access Token, ou Client ID e Client Secret de teste).'
                : 'Cadastre primeiro as chaves de produção.']);
        }

        $referencia = 'teste-'.\Illuminate\Support\Str::uuid();
        $volta = fn (string $r) => route('admin.mercadopago.teste.retorno', ['ref' => $referencia, 'resultado' => $r]);
        $dados = [
            'type' => 'online',
            'processing_mode' => 'manual',
            'total_amount' => $valor,
            'external_reference' => $referencia,
            'description' => 'Teste de integração MinhaTropa',
            'payer' => [
                'email' => $d['email'],
                'first_name' => $primeiro,
                'last_name' => $sobrenome,
                'identification' => ['type' => 'CPF', 'number' => preg_replace('/\D/', '', $d['cpf'])],
            ],
            'items' => [[
                'external_code' => 'teste-mt',
                'title' => 'Teste de pagamento MinhaTropa',
                'description' => 'Pagamento de teste para validar a integração',
                'category_id' => 'services',
                'quantity' => 1,
                'unit_price' => $valor,
            ]],
            'config' => [
                'statement_descriptor' => 'MINHATROPA',
                'online' => ['success_url' => $volta('sucesso'), 'failure_url' => $volta('falha'), 'pending_url' => $volta('pendente')],
            ],
        ];
        try {
            $order = MercadoPago::criarOrder($token, $dados);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Cache::forget('mp_token_aplicacao_'.md5((string) MercadoPago::credencial('client_id')));

            return back()->withInput()->withErrors(['valor' => $e->getMessage()]);
        }
        \Illuminate\Support\Facades\Cache::put('mp_teste_'.$referencia, ['order' => $order['id'], 'ambiente' => $d['ambiente']], now()->addHours(6));
        \App\Support\Auditoria::registrar('plataforma.mercadopago_teste', $request->user(), null,
            ['valor' => $valor, 'ambiente' => $d['ambiente'], 'referencia' => $referencia, 'order' => $order['id']]);

        return view('admin.teste-link', [
            'link' => $order['checkout_url'] ?? '',
            'valor' => (float) $valor, 'teste' => $teste, 'referencia' => $referencia, 'orderId' => $order['id'],
        ]);
    }

    public function retornoTeste(Request $request)
    {
        $ref = (string) $request->query('ref', '');
        $info = $ref !== '' ? \Illuminate\Support\Facades\Cache::get('mp_teste_'.$ref) : null;
        $admin = (bool) $request->user()?->admin_plataforma;
        $order = [];
        if ($admin && $info) {
            try {
                $token = $info['ambiente'] === 'teste' ? MercadoPago::tokenTeste() : MercadoPago::tokenAplicacao();
                $order = $token ? MercadoPago::order($token, $info['order']) : [];
            } catch (\Throwable) {
                $order = [];
            }
        }

        return view('admin.teste-retorno', [
            'ref' => $ref, 'resultado' => (string) $request->query('resultado', ''), 'info' => $info,
            'order' => $order, 'extras' => $request->except(['ref', 'resultado']), 'admin' => $admin,
        ]);
    }

    public function verificarTokens(Request $request)
    {
        $ambiente = $request->input('ambiente') === 'teste' ? 'teste' : 'producao';
        $info = function (?string $token) {
            if (! $token) {
                return null;
            }
            $u = MercadoPago::usuario($token);
            if (! $u) {
                return ['erro' => 'O Mercado Pago não aceitou este token.'];
            }
            $tags = (array) ($u['tags'] ?? []);

            return [
                'id' => $u['id'] ?? null, 'apelido' => $u['nickname'] ?? null, 'email' => $u['email'] ?? null,
                'teste' => in_array('test_user', $tags, true) || str_contains((string) ($u['email'] ?? ''), '@testuser.com')
                    || str_starts_with(strtoupper((string) ($u['nickname'] ?? '')), 'TEST'),
            ];
        };
        try {
            $resultado = $ambiente === 'teste' ? $info(MercadoPago::tokenTeste())
                : (MercadoPago::credencial('access_token') || MercadoPago::configurado() ? $info(MercadoPago::tokenAplicacao()) : null);
        } catch (\Throwable $e) {
            $resultado = ['erro' => $e->getMessage()];
        }

        return redirect()->to(route('admin.mercadopago').'#ambiente-'.$ambiente)->with('verificacao', [$ambiente => $resultado]);
    }

    // ---------- E-mails enviados ----------

    public function emails(Request $request)
    {
        $busca = trim((string) $request->query('busca'));
        $envios = DB::table('mensagem_envio')->where('canal', 'email')
            ->when($busca !== '', fn ($q) => $q->where('destinatario', 'like', '%'.$busca.'%'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('id')->limit(100)->get(['id', 'tipo', 'destinatario', 'assunto', 'status', 'erro', 'criado_em', 'enviado_em']);

        return view('admin.emails', compact('envios', 'busca'));
    }
}
