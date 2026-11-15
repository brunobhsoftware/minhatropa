<?php

namespace App\Http\Controllers;

use App\Pagamentos\MercadoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function mercadoPago(Request $request)
    {
        return $this->registrar($request, fn ($a, $r, $d) => MercadoPago::origemAssinatura($a, $r, $d));
    }

    private function registrar(Request $request, callable $conferir, ?int $contaId = null)
    {
        $corpo = $request->json()->all() ?: $request->all();
        $dataId = (string) ($request->query('data_id') ?? data_get($corpo, 'data.id') ?? $request->query('id') ?? '');
        $requestId = $request->header('x-request-id');
        $origem = $conferir($request->header('x-signature'), $requestId, $dataId);
        $valida = $origem !== null;
        $idExterno = (string) (data_get($corpo, 'id') ?: ($requestId ?: sha1($request->getContent())));

        $cabecalhos = collect($request->headers->all())->only(['x-signature', 'x-request-id', 'user-agent', 'content-type'])
            ->map(fn ($v) => $v[0] ?? null);

        DB::table('webhook_evento')->insertOrIgnore([
            'gateway_id' => DB::table('gateway')->where('codigo', 'mercadopago')->value('id'),
            'conta_pagamento_id' => $contaId,
            'id_evento_externo' => mb_substr($idExterno, 0, 150),
            'tipo' => mb_substr((string) (data_get($corpo, 'type') ?? $request->query('type') ?? $request->query('topic') ?? ''), 0, 60) ?: null,
            'acao' => mb_substr((string) data_get($corpo, 'action', ''), 0, 60) ?: null,
            'recurso_id' => $dataId !== '' ? mb_substr($dataId, 0, 100) : null,
            'request_id' => $requestId ? mb_substr($requestId, 0, 100) : null,
            'cabecalhos' => json_encode($cabecalhos),
            'corpo' => json_encode($corpo ?: new \stdClass),
            'assinatura_valida' => $valida ? 1 : 0,
            'status' => $valida ? 'recebido' : 'ignorado',
            'erro' => $valida ? ($origem === 'teste' ? 'Assinatura de teste' : null) : 'Assinatura ausente ou inválida',
        ]);

        if ($valida) {
            $this->processar($dataId, (string) (data_get($corpo, 'type') ?? $request->query('type') ?? ''), $idExterno);
        }

        return response()->json(['ok' => true]);
    }

    private function processar(string $recursoId, string $tipo, string $idExterno): void
    {
        if ($recursoId === '') {
            return;
        }
        try {
            $pg = DB::table('pagamento')->where(fn ($q) => $q->where('gateway_pedido_id', $recursoId)->orWhere('gateway_pagamento_id', $recursoId))
                ->orderByDesc('id')->first();
            if ($pg) {
                DB::table('webhook_evento')->where('id_evento_externo', mb_substr($idExterno, 0, 150))
                    ->update(['status' => 'processado', 'processado_em' => now(), 'pagamento_id' => $pg->id]);
            }
        } catch (\Throwable $e) {
            report($e);
            DB::table('webhook_evento')->where('id_evento_externo', mb_substr($idExterno, 0, 150))->update(['status' => 'erro', 'erro' => mb_substr($e->getMessage(), 0, 255)]);
        }
    }
}
