<?php

namespace App\Http\Middleware;

use App\Support\Esquema;
use App\Support\HarasAtual;
use App\Support\Modulos;
use App\Support\Permissao;
use Closure;
use Illuminate\Http\Request;

/** Área do Haras / Fazenda: define a propriedade atual (ou manda escolher), o papel da pessoa nela e os módulos liberados. */
class IdentificarHaras
{
    public function handle(Request $request, Closure $next)
    {
        if (! Esquema::atual()) {
            return response()->view('painel.manutencao', [], 503);
        }
        $empresa = HarasAtual::obter($request->user());
        if (! $empresa) {
            if (HarasAtual::disponiveis($request->user())->isEmpty()) {
                // Sem propriedade para abrir: a pessoa vai para o ambiente do profissional (de lá cadastra ou é vinculada).
                return redirect()->route('profissional.inicio')->with('aviso', 'Você não tem acesso a nenhum Haras / Fazenda no momento.');
            }

            return redirect()->route('painel.escolher');
        }
        $request->attributes->set('haras', $empresa);
        $papel = Permissao::papel($request->user(), $empresa);
        $request->attributes->set('papel', $papel);
        view()->share('haras', $empresa);
        view()->share('papel', $papel);
        view()->share('modulosHaras', Modulos::daEmpresa($empresa));

        return $next($request);
    }
}
