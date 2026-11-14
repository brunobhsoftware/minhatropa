<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\EventoSaude;
use App\Support\Auditoria;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicaController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $query = EventoSaude::where('evento_saude.empresa_id', $empresa->id)
            ->whereNull('evento_saude.excluido_em')
            ->join('animal', 'animal.id', '=', 'evento_saude.animal_id')
            ->select('evento_saude.*', 'animal.nome as animal_nome');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('evento_saude.titulo', 'like', "%{$busca}%")
                  ->orWhere('animal.nome', 'like', "%{$busca}%")
                  ->orWhere('evento_saude.medicamento', 'like', "%{$busca}%");
            });
        }
        if ($tipo = $request->input('tipo')) {
            $query->where('evento_saude.tipo', $tipo);
        }
        if ($animal = $request->input('animal_id')) {
            $query->where('evento_saude.animal_id', $animal);
        }
        if ($de = $request->input('de')) {
            $query->where('evento_saude.data_evento', '>=', $de);
        }
        if ($ate = $request->input('ate')) {
            $query->where('evento_saude.data_evento', '<=', $ate);
        }

        $eventos = $query->orderByDesc('evento_saude.data_evento')->orderByDesc('evento_saude.id')->limit(200)->get();

        $animais = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(['id', 'nome']);

        $totais = (object) [
            'total' => $eventos->count(),
            'vacinas' => $eventos->where('tipo', 'vacina')->count(),
            'vermifugacoes' => $eventos->where('tipo', 'vermifugacao')->count(),
            'tratamentos' => $eventos->where('tipo', 'tratamento')->count(),
        ];

        return view('painel.clinica.index', compact('eventos', 'animais', 'totais'));
    }

    public function criar(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        return view('painel.clinica.form', [
            'evento' => new EventoSaude(['data_evento' => now()->toDateString(), 'tipo' => 'outro']),
            'animais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function salvar(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $dados = $this->validar($request);
        $evento = EventoSaude::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        Auditoria::registrar('clinica.criar', $evento);

        return redirect()->route('painel.clinica')->with('ok', "Registro \"{$evento->titulo}\" criado.");
    }

    public function ver(Request $request, EventoSaude $evento)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($evento->empresa_id === $empresa->id && ! $evento->excluido_em, 404);

        $evento->load('animal:id,nome', 'criadoPor:id,nome');

        return view('painel.clinica.show', compact('evento'));
    }

    public function editar(Request $request, EventoSaude $evento)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($evento->empresa_id === $empresa->id && ! $evento->excluido_em, 404);

        return view('painel.clinica.form', [
            'evento' => $evento,
            'animais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function atualizar(Request $request, EventoSaude $evento)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($evento->empresa_id === $empresa->id && ! $evento->excluido_em, 404);

        $antes = $evento->getAttributes();
        $evento->update($this->validar($request));
        Auditoria::registrar('clinica.editar', $evento, $antes);

        return redirect()->route('painel.clinica')->with('ok', "Registro \"{$evento->titulo}\" atualizado.");
    }

    public function excluir(Request $request, EventoSaude $evento)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($evento->empresa_id === $empresa->id && ! $evento->excluido_em, 404);

        $evento->update(['excluido_em' => now()]);
        Auditoria::registrar('clinica.excluir', $evento);

        return redirect()->route('painel.clinica')->with('ok', "Registro \"{$evento->titulo}\" excluído.");
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'animal_id'         => ['required', 'integer', 'exists:animal,id'],
            'tipo'              => ['required', Rule::in(array_keys(EventoSaude::TIPOS))],
            'titulo'            => ['required', 'string', 'max:200'],
            'descricao'         => ['nullable', 'string', 'max:5000'],
            'data_evento'       => ['required', 'date'],
            'data_proxima'      => ['nullable', 'date', 'after_or_equal:data_evento'],
            'medicamento'       => ['nullable', 'string', 'max:200'],
            'dosagem'           => ['nullable', 'string', 'max:100'],
            'via_administracao' => ['nullable', 'string', 'max:100'],
            'lote'              => ['nullable', 'string', 'max:100'],
            'peso_animal'       => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'temperatura'       => ['nullable', 'numeric', 'min:30', 'max:45'],
            'custo'             => ['nullable', 'numeric', 'min:0'],
            'observacoes'       => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
