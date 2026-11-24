<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Support\Esquema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultaController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->input('busca'));
        $especie = $request->input('especie');
        $sexo = $request->input('sexo');
        $animais = collect();

        if ($busca !== '' || $especie || $sexo) {
            $query = Animal::whereNull('excluido_em');

            if ($busca !== '') {
                $query->where(function ($q) use ($busca) {
                    $q->where('nome', 'like', "%{$busca}%")
                      ->orWhere('nome_registro', 'like', "%{$busca}%")
                      ->orWhere('apelido', 'like', "%{$busca}%")
                      ->orWhere('registro', 'like', "%{$busca}%")
                      ->orWhere('microchip', 'like', "%{$busca}%");
                });
            }
            if ($especie) {
                $query->where('especie', $especie);
            }
            if ($sexo) {
                $query->where('sexo', $sexo);
            }

            $animais = $query->orderBy('nome')->limit(100)->get()->load('empresa');
        }

        return view('painel.consulta.index', compact('busca', 'especie', 'sexo', 'animais'));
    }

    public function show(Request $request, Animal $animal)
    {
        abort_if($animal->excluido_em, 404);

        $empresa = $request->attributes->get('haras');
        $proprio = $animal->empresa_id === $empresa->id;
        $animal->load('empresa', 'paiAnimal', 'maeAnimal');
        $aba = $request->input('aba', 'dados');

        $cobricoes = collect();
        $nascimentos = collect();
        $transferencias = collect();
        $filhos = collect();
        $socios = collect();

        if (Esquema::temV005()) {
            $cobricoes = DB::table('cobricao')
                ->whereNull('excluido_em')
                ->where(fn ($q) => $q->where('egua_id', $animal->id)->orWhere('garanhao_id', $animal->id))
                ->orderByDesc('data_cobricao')
                ->limit(50)
                ->get();

            $nascimentos = DB::table('nascimento')
                ->whereNull('excluido_em')
                ->where(fn ($q) => $q->where('mae_id', $animal->id)->orWhere('pai_id', $animal->id)->orWhere('animal_id', $animal->id))
                ->orderByDesc('data_nascimento')
                ->limit(50)
                ->get();

            $transferencias = DB::table('transferencia')
                ->where('animal_id', $animal->id)
                ->orderByDesc('data_transferencia')
                ->limit(50)
                ->get();

            $filhos = Animal::whereNull('excluido_em')
                ->where(fn ($q) => $q->where('pai_id', $animal->id)->orWhere('mae_id', $animal->id))
                ->orderBy('nome')
                ->get();
        }

        if (Esquema::temV011()) {
            $socios = \App\Support\Sociedade::doAnimal($animal);
        }

        $arvore = $animal->arvoreGenealogica(3);

        return view('painel.consulta.show', compact(
            'animal', 'proprio', 'aba', 'cobricoes', 'nascimentos',
            'transferencias', 'filhos', 'socios', 'arvore'
        ));
    }
}
