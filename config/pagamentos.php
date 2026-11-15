<?php

return [
    'mercadopago' => [
        'ambiente' => env('MP_AMBIENTE', 'sandbox'),
        'conexao_direta' => (bool) env('MP_CONEXAO_DIRETA', false),
        'client_id' => env('MP_CLIENT_ID'),
        'client_secret' => env('MP_CLIENT_SECRET'),
        'webhook_secret' => env('MP_WEBHOOK_SECRET'),
        'autorizacao_url' => env('MP_AUTORIZACAO_URL', 'https://auth.mercadopago.com/authorization'),
        'api_url' => env('MP_API_URL', 'https://api.mercadopago.com'),
        'retorno_oauth' => '/painel/financeiro/mercadopago/retorno',
        'webhook' => '/webhooks/mercadopago',
    ],
];
