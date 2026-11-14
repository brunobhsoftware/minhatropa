<?php

namespace App\Http\Controllers\Geo;

use App\Http\Controllers\Controller;
use App\Support\Esquema;
use App\Support\Geografia;

/** Lista de municípios de uma UF para o seletor em cascata (dados públicos do IBGE). */
class MunicipiosController extends Controller
{
    public function __invoke(string $uf)
    {
        abort_unless(Esquema::temV007(), 503);
        $lista = Geografia::municipiosDaUf($uf);
        abort_if($lista === [], 404);

        return response()->json($lista)->header('Cache-Control', 'public, max-age=86400');
    }
}
