<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Empresa;
use App\Models\Transferencia;
use App\Support\Auditoria;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransferenciasController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $transferencias = Transferencia::where('empresa_origem_id', $empresa->id)
            ->orWhere('empresa_destino_id', $empresa->id)
            ->with(['animal:id,nome', 'origem:id,nome', 'destino:id,nome'])
            ->orderByDesc('data_transferencia')->get();

        return view('painel.transferencias.index', ['transferencias' => $transferencias, 'empresa' => $empresa]);
    }

    public function create(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        return view('painel.transferencias.form', [
            'transferencia' => new Transferencia,
            'animais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('situacao', 'ativo')->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function store(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        $dados = $this->validar($request);
        $dados['empresa_origem_id'] = $empresa->id;

        if ($dados['destino_tipo'] === 'plataforma' && $dados['empresa_destino_id'] ?? false) {
            $dados['destino_externo'] = null;
        } else {
            $dados['empresa_destino_id'] = null;
        }
        unset($dados['destino_tipo']);

        $transferencia = Transferencia::create($dados + ['criado_por' => $request->user()->id]);

        if ($transferencia->situacao === 'concluida') {
            $this->concluirTransferencia($transferencia);
        }

        Auditoria::registrar('transferencia.criar', $transferencia);

        return redirect()->route('painel.transferencias')->with('ok', 'Transferência registrada.');
    }

    public function show(Request $request, Transferencia $transferencia)
    {
        $empresa = $request->attributes->get('haras');
        abort_unless($transferencia->empresa_origem_id === $empresa->id || $transferencia->empresa_destino_id === $empresa->id, 404);

        return view('painel.transferencias.show', ['transferencia' => $transferencia->load(['animal', 'origem', 'destino'])]);
    }

    public function responder(Request $request, Transferencia $transferencia)
    {
        $empresa = $request->attributes->get('haras');
        abort_unless($transferencia->empresa_destino_id === $empresa->id && $transferencia->situacao === 'solicitada', 404);

        $request->validate(['resposta' => ['required', Rule::in(['aceita', 'recusada'])], 'motivo_recusa' => ['nullable', 'string', 'max:255']]);

        $antes = $transferencia->getAttributes();
        $transferencia->update([
            'situacao' => $request->input('resposta'),
            'respondido_por' => $request->user()->id,
            'respondido_em' => now(),
            'motivo_recusa' => $request->input('resposta') === 'recusada' ? $request->input('motivo_recusa') : null,
        ]);

        if ($transferencia->situacao === 'aceita') {
            $this->concluirTransferencia($transferencia);
            $transferencia->update(['situacao' => 'concluida']);
        }

        Auditoria::registrar('transferencia.responder', $transferencia, $antes);

        return redirect()->route('painel.transferencias.show', $transferencia)->with('ok', 'Resposta registrada.');
    }

    public function buscarHaras(Request $request)
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }
        $empresa = $request->attributes->get('haras');

        return response()->json(
            Empresa::where('id', '!=', $empresa->id)
                ->where('situacao', 'ativo')
                ->where(function ($qb) use ($q) {
                    $qb->where('nome', 'like', "%{$q}%")->orWhere('documento', 'like', "%{$q}%");
                })
                ->limit(10)->get(['id', 'nome', 'cidade', 'uf'])
                ->map(fn ($e) => ['id' => $e->id, 'nome' => $e->nome, 'cidade_uf' => trim("{$e->cidade}/{$e->uf}", '/')])
        );
    }

    private function concluirTransferencia(Transferencia $transferencia): void
    {
        $animal = Animal::find($transferencia->animal_id);
        if (! $animal) {
            return;
        }

        if ($transferencia->empresa_destino_id) {
            $animal->update([
                'empresa_id' => $transferencia->empresa_destino_id,
                'situacao' => 'ativo',
                'situacao_data' => $transferencia->data_transferencia,
                'situacao_motivo' => "Transferido de {$transferencia->origem?->nome}",
            ]);
        } else {
            $animal->update([
                'situacao' => 'transferido',
                'situacao_data' => $transferencia->data_transferencia,
                'situacao_motivo' => "Transferido para {$transferencia->destino_externo}",
            ]);
        }
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'animal_id' => ['required', 'exists:animal,id'],
            'destino_tipo' => ['required', Rule::in(['plataforma', 'externo'])],
            'empresa_destino_id' => ['nullable', 'exists:empresa,id'],
            'destino_externo' => ['nullable', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(array_keys(Transferencia::TIPOS))],
            'valor' => ['nullable', 'numeric', 'min:0'],
            'data_transferencia' => ['required', 'date', 'before_or_equal:today'],
            'situacao' => ['required', Rule::in(array_keys(Transferencia::SITUACOES))],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
