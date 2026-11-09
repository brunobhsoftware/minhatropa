<?php

namespace App\Http\Middleware;

use App\Support\Esquema;
use App\Support\HarasAtual;
use Closure;
use Illuminate\Http\Request;

/** Área do Haras: define o Haras atual (ou manda escolher) e o compartilha com as telas. */
class IdentificarHaras
{
    public function handle(Request $request, Closure $next)
    {
        if (! Esquema::temV002()) {
            return response()->view('painel.manutencao', [], 503);
        }
        $empresa = HarasAtual::obter($request->user());
        if (! $empresa) {
            if (HarasAtual::disponiveis($request->user())->isEmpty()) {
                return response()->view('painel.sem-haras', [], 403);
            }

            return redirect()->route('painel.escolher');
        }
        $request->attributes->set('haras', $empresa);
        view()->share('haras', $empresa);

        return $next($request);
    }
}
