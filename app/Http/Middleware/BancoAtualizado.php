<?php

namespace App\Http\Middleware;

use App\Support\Esquema;
use Closure;
use Illuminate\Http\Request;

/** Telas da Administração que dependem dos scripts mais novos: sem eles, manda aplicar em vez de dar erro. */
class BancoAtualizado
{
    public function handle(Request $request, Closure $next)
    {
        if (! Esquema::atual()) {
            return redirect()->route('admin.banco')->withErrors(['banco' => 'Aplique as atualizações do banco pendentes (até o V007) para usar esta tela.']);
        }

        return $next($request);
    }
}
