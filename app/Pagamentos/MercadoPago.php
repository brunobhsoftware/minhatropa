<?php

namespace App\Pagamentos;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente do Mercado Pago: OAuth, API de Orders, conferência de webhooks.
 * Credenciais da aplicação ficam criptografadas em parametro_sistema ou no .env.
 */
class MercadoPago
{
    public const CHAVES = [
        'client_id' => ['parametro' => 'mp_client_id', 'secreto' => false, 'env' => 'MP_CLIENT_ID'],
        'client_secret' => ['parametro' => 'mp_client_secret', 'secreto' => true, 'env' => 'MP_CLIENT_SECRET'],
        'webhook_secret' => ['parametro' => 'mp_webhook_secret', 'secreto' => true, 'env' => 'MP_WEBHOOK_SECRET'],
        'access_token' => ['parametro' => 'mp_access_token', 'secreto' => true, 'env' => 'MP_ACCESS_TOKEN'],
        'public_key' => ['parametro' => 'mp_public_key', 'secreto' => false, 'env' => 'MP_PUBLIC_KEY'],
        'teste_public_key' => ['parametro' => 'mp_teste_public_key', 'secreto' => false, 'env' => 'MP_TESTE_PUBLIC_KEY'],
        'teste_access_token' => ['parametro' => 'mp_teste_access_token', 'secreto' => true, 'env' => 'MP_TESTE_ACCESS_TOKEN'],
        'teste_webhook_secret' => ['parametro' => 'mp_teste_webhook_secret', 'secreto' => true, 'env' => 'MP_TESTE_WEBHOOK_SECRET'],
        'teste_client_id' => ['parametro' => 'mp_teste_client_id', 'secreto' => false, 'env' => 'MP_TESTE_CLIENT_ID'],
        'teste_client_secret' => ['parametro' => 'mp_teste_client_secret', 'secreto' => true, 'env' => 'MP_TESTE_CLIENT_SECRET'],
    ];

    private static array $cache = [];

    public static function credencial(string $nome): ?string
    {
        if (! array_key_exists($nome, self::$cache)) {
            $def = self::CHAVES[$nome];
            $valor = null;
            try {
                $bruto = \Illuminate\Support\Facades\DB::table('parametro_sistema')->where('chave', $def['parametro'])->value('valor');
                if (filled($bruto)) {
                    $valor = $def['secreto'] ? decrypt($bruto, false) : $bruto;
                }
            } catch (\Throwable) {
                $valor = null;
            }
            self::$cache[$nome] = $valor ?: (config('pagamentos.mercadopago.'.$nome) ?: env($def['env']));
        }

        return self::$cache[$nome];
    }

    public static function salvarCredenciais(array $valores): void
    {
        foreach (self::CHAVES as $nome => $def) {
            $v = trim((string) ($valores[$nome] ?? ''));
            if ($v === '') {
                continue;
            }
            \Illuminate\Support\Facades\DB::table('parametro_sistema')->updateOrInsert(['chave' => $def['parametro']], [
                'valor' => $def['secreto'] ? encrypt($v, false) : $v,
                'descricao' => 'Mercado Pago: '.$def['env'].($def['secreto'] ? ' (criptografado)' : ''),
            ]);
        }
        self::$cache = [];
    }

    public static function situacaoCredenciais(): array
    {
        $r = [];
        foreach (self::CHAVES as $nome => $def) {
            $v = self::credencial($nome);
            $r[$nome] = ['definida' => filled($v), 'dica' => filled($v) ? ($def['secreto'] ? '••••'.substr($v, -4) : $v) : null];
        }

        return $r;
    }

    public static function usaPkce(): bool
    {
        try {
            return \Illuminate\Support\Facades\DB::table('parametro_sistema')->where('chave', 'mp_pkce')->value('valor') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    public static function definirPkce(bool $ligado): void
    {
        \Illuminate\Support\Facades\DB::table('parametro_sistema')->updateOrInsert(['chave' => 'mp_pkce'],
            ['valor' => $ligado ? '1' : '0', 'descricao' => 'Mercado Pago: usar PKCE no OAuth']);
    }

    public static function configurado(): bool
    {
        return filled(self::credencial('client_id')) && filled(self::credencial('client_secret'));
    }

    public static function retornoUrl(): string
    {
        return url(config('pagamentos.mercadopago.retorno_oauth'));
    }

    public static function urlAutorizacao(string $state, string $codeVerifier): string
    {
        $params = [
            'client_id' => self::credencial('client_id'),
            'response_type' => 'code',
            'platform_id' => 'mp',
            'state' => $state,
            'redirect_uri' => self::retornoUrl(),
        ];
        if (self::usaPkce()) {
            $params['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
            $params['code_challenge_method'] = 'S256';
        }

        return config('pagamentos.mercadopago.autorizacao_url').'?'.http_build_query($params);
    }

    public static function trocarCodigo(string $code, string $codeVerifier): array
    {
        return self::token([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => self::retornoUrl(),
        ] + (self::usaPkce() ? ['code_verifier' => $codeVerifier] : []));
    }

    public static function renovarToken(string $refreshToken): array
    {
        return self::token(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]);
    }

    public static function tokenAplicacao(): string
    {
        if ($t = self::credencial('access_token')) {
            return $t;
        }

        return \Illuminate\Support\Facades\Cache::remember('mp_token_aplicacao_'.md5((string) self::credencial('client_id')), now()->addHours(5),
            fn () => self::token(['grant_type' => 'client_credentials'])['access_token']);
    }

    public static function tokenTeste(): ?string
    {
        if ($t = self::credencial('teste_access_token')) {
            return $t;
        }
        $id = self::credencial('teste_client_id');
        $segredo = self::credencial('teste_client_secret');
        if (! $id || ! $segredo) {
            return null;
        }

        return \Illuminate\Support\Facades\Cache::remember('mp_token_teste_'.md5($id), now()->addHours(5), function () use ($id, $segredo) {
            $r = Http::asForm()->acceptJson()->timeout(20)->post(self::apiUrl().'/oauth/token',
                ['grant_type' => 'client_credentials', 'client_id' => $id, 'client_secret' => $segredo]);
            if (! $r->successful() || ! $r->json('access_token')) {
                throw new RuntimeException('O Mercado Pago recusou o Client ID/Secret de teste (HTTP '.$r->status().').');
            }

            return $r->json('access_token');
        });
    }

    public static function criarOrder(string $accessToken, array $dados): array
    {
        $r = Http::withToken($accessToken)->acceptJson()->asJson()->timeout(20)
            ->withHeaders(['X-Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()])
            ->post(self::apiUrl().'/v1/orders', $dados);
        if (! $r->successful() || ! $r->json('id')) {
            $j = $r->json() ?? [];
            $msg = $j['message'] ?? '';
            $erros = collect((array) ($j['errors'] ?? []))->pluck('message')->filter()->implode('; ');
            if ($erros) {
                $msg .= ' — '.$erros;
            }
            throw new RuntimeException('O Mercado Pago recusou a criação da order (HTTP '.$r->status().($msg ? ': '.mb_substr($msg, 0, 500) : '').').');
        }

        return $r->json();
    }

    public static function reembolsarOrder(string $accessToken, string $id, ?array $pedido = null): array
    {
        $r = Http::withToken($accessToken)->acceptJson()->asJson()->timeout(20)
            ->withHeaders(['X-Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()])
            ->post(self::apiUrl().'/v1/orders/'.$id.'/refund', $pedido ?? []);
        if (! $r->successful()) {
            $msg = $r->json('message') ?? '';
            throw new RuntimeException('O Mercado Pago recusou a devolução (HTTP '.$r->status().($msg ? ': '.mb_substr((string) $msg, 0, 300) : '').').');
        }

        return $r->json();
    }

    public static function order(string $accessToken, string $id): array
    {
        try {
            $r = Http::withToken($accessToken)->acceptJson()->timeout(15)->get(self::apiUrl().'/v1/orders/'.$id);

            return $r->successful() ? $r->json() : [];
        } catch (\Throwable) {
            return [];
        }
    }

    public static function pagamento(string $accessToken, string $id): array
    {
        $r = Http::withToken($accessToken)->acceptJson()->timeout(15)->get(self::apiUrl().'/v1/payments/'.rawurlencode($id));

        return $r->successful() ? $r->json() : [];
    }

    public static function usuario(string $accessToken): array
    {
        $r = Http::withToken($accessToken)->acceptJson()->timeout(15)->get(self::apiUrl().'/users/me');

        return $r->successful() ? $r->json() : [];
    }

    public static function assinaturaValida(?string $assinatura, ?string $requestId, ?string $dataId): bool
    {
        return self::origemAssinatura($assinatura, $requestId, $dataId) !== null;
    }

    public static function origemAssinatura(?string $assinatura, ?string $requestId, ?string $dataId): ?string
    {
        foreach (['producao' => 'webhook_secret', 'teste' => 'teste_webhook_secret'] as $origem => $chave) {
            $segredo = self::credencial($chave);
            if ($segredo && self::confereAssinatura($segredo, $assinatura, $requestId, $dataId)) {
                return $origem;
            }
        }

        return null;
    }

    public static function confereAssinatura(string $segredo, ?string $assinatura, ?string $requestId, ?string $dataId): bool
    {
        if (! $assinatura) {
            return false;
        }
        $partes = [];
        foreach (explode(',', $assinatura) as $par) {
            [$k, $v] = array_pad(explode('=', trim($par), 2), 2, null);
            $partes[$k] = $v;
        }
        if (empty($partes['ts']) || empty($partes['v1'])) {
            return false;
        }
        $manifesto = '';
        if ($dataId !== null && $dataId !== '') {
            $manifesto .= 'id:'.(ctype_alnum($dataId) ? strtolower($dataId) : $dataId).';';
        }
        if ($requestId) {
            $manifesto .= 'request-id:'.$requestId.';';
        }
        $manifesto .= 'ts:'.$partes['ts'].';';

        return hash_equals(hash_hmac('sha256', $manifesto, $segredo), $partes['v1']);
    }

    private static function apiUrl(): string
    {
        return rtrim((string) config('pagamentos.mercadopago.api_url', 'https://api.mercadopago.com'), '/');
    }

    private static function token(array $dados): array
    {
        $r = Http::asForm()->acceptJson()->timeout(20)->post(self::apiUrl().'/oauth/token', $dados + [
            'client_id' => self::credencial('client_id'),
            'client_secret' => self::credencial('client_secret'),
        ]);
        if (! $r->successful() || ! $r->json('access_token')) {
            throw new RuntimeException('O Mercado Pago recusou a autorização (HTTP '.$r->status().($r->json('message') ? ': '.mb_substr((string) $r->json('message'), 0, 200) : '').').');
        }

        return $r->json();
    }
}
