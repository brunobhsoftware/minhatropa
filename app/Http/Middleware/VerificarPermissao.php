<?php

namespace App\Http\Middleware;

use App\Support\Permissao;
use Closure;
use Illuminate\Http\Request;

/** RBAC da área do Haras / Fazenda: confere se o papel da pessoa alcança a área da rota (ler em GET, escrever nos demais). */
class VerificarPermissao
{
    public function handle(Request $request, Closure $next, string $area)
    {
        $papel = (string) $request->attributes->get('papel', 'consulta');
        $escrita = ! in_array($request->method(), ['GET', 'HEAD'], true);
        abort_unless(Permissao::pode($papel, $area, $escrita), 403,
            $escrita ? 'Seu acesso a esta propriedade não permite alterar esta área.' : 'Seu acesso a esta propriedade não inclui esta área.');

        return $next($request);
    }
}
