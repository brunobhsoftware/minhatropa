<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Support\Auditoria;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Cadastro de animais do Haras. Sem licença, só o(s) animal(is) do período gratuito. */
class AnimaisController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');

        return view('painel.animais.index', [
            'animais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(),
            'podeCadastrar' => Licenca::podeCadastrarAnimal($empresa),
        ]);
    }

    public function create(Request $request)
    {
        if (! Licenca::podeCadastrarAnimal($request->attributes->get('haras'))) {
            return redirect()->route('painel.licenca');
        }

        return view('painel.animais.form', ['animal' => new Animal]);
    }

    public function store(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        if (! Licenca::podeCadastrarAnimal($empresa)) {
            return redirect()->route('painel.licenca');
        }
        $animal = Animal::create($this->validar($request) + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        Auditoria::registrar('animal.criar', $animal);

        return redirect()->route('painel.animais')->with('ok', "{$animal->nome} cadastrado(a).");
    }

    public function edit(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);

        return view('painel.animais.form', ['animal' => $animal]);
    }

    public function update(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);
        $antes = $animal->getAttributes();
        $animal->update($this->validar($request));
        Auditoria::registrar('animal.editar', $animal, $antes);

        return redirect()->route('painel.animais')->with('ok', "Dados de {$animal->nome} salvos.");
    }

    private function doHaras(Request $request, Animal $animal): void
    {
        abort_unless($animal->empresa_id === $request->attributes->get('haras')->id && ! $animal->excluido_em, 404);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'sexo' => ['nullable', Rule::in(['macho', 'femea'])],
            'categoria' => ['nullable', Rule::in(array_keys(Animal::CATEGORIAS))],
            'raca' => ['nullable', 'string', 'max:80'],
            'pelagem' => ['nullable', 'string', 'max:80'],
            'data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
            'registro' => ['nullable', 'string', 'max:60'],
            'microchip' => ['nullable', 'string', 'max:40'],
            'pai' => ['nullable', 'string', 'max:120'],
            'mae' => ['nullable', 'string', 'max:120'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
