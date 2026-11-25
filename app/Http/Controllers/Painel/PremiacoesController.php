<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Premiacao;
use App\Support\Auditoria;
use Illuminate\Http\Request;

/** Premiações do animal (cadastro simples: evento, data, local). Gerenciadas na ficha/consulta do animal. */
class PremiacoesController extends Controller
{
    public function store(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);
        $dados = $request->validate([
            'evento' => ['required', 'string', 'max:150'],
            'data' => ['nullable', 'date', 'before_or_equal:today'],
            'local' => ['nullable', 'string', 'max:150'],
            'colocacao' => ['nullable', 'string', 'max:100'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ]);

        $premiacao = Premiacao::create($dados + [
            'empresa_id' => $animal->empresa_id,
            'animal_id' => $animal->id,
            'criado_por' => $request->user()->id,
        ]);
        Auditoria::registrar('animal.premiacao.criar', $premiacao);

        return redirect()->to(route('painel.consulta.show', $animal).'?aba=premiacoes')->with('ok', 'Premiação adicionada.');
    }

    public function destroy(Request $request, Animal $animal, Premiacao $premiacao)
    {
        $this->doHaras($request, $animal);
        abort_unless($premiacao->animal_id === $animal->id && ! $premiacao->excluido_em, 404);

        $premiacao->update(['excluido_em' => now()]);
        Auditoria::registrar('animal.premiacao.excluir', $premiacao);

        return redirect()->to(route('painel.consulta.show', $animal).'?aba=premiacoes')->with('ok', 'Premiação removida.');
    }

    private function doHaras(Request $request, Animal $animal): void
    {
        abort_unless($animal->empresa_id === $request->attributes->get('haras')->id && ! $animal->excluido_em, 404);
    }
}
