<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Support\Auditoria;
use App\Support\Esquema;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnimaisController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $query = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em');

        if ($busca = $request->input('busca')) {
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('registro', 'like', "%{$busca}%")
                  ->orWhere('microchip', 'like', "%{$busca}%")
                  ->orWhere('numero_plantel', 'like', "%{$busca}%");
            });
        }
        if (Esquema::temV005()) {
            if ($especie = $request->input('especie')) {
                $query->where('especie', $especie);
            }
            if ($situacao = $request->input('situacao')) {
                $query->where('situacao', $situacao);
            }
        }
        if ($categoria = $request->input('categoria')) {
            $query->where('categoria', $categoria);
        }

        $animais = $query->orderBy('nome')->get();

        $base = Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em');
        if (Esquema::temV005()) {
            $totais = (clone $base)->selectRaw("COUNT(*) as total, SUM(situacao='ativo') as ativos, SUM(sexo='macho') as machos, SUM(sexo='femea') as femeas")->first();
        } else {
            $totais = (clone $base)->selectRaw("COUNT(*) as total, COUNT(*) as ativos, SUM(sexo='macho') as machos, SUM(sexo='femea') as femeas")->first();
        }

        return view('painel.animais.index', [
            'animais' => $animais,
            'totais' => $totais,
            'podeCadastrar' => Licenca::podeCadastrarAnimal($empresa),
            'ativosPlantel' => Licenca::animais($empresa),
            'limite' => Licenca::limite($empresa),
            'faixa' => Licenca::faixaAtual($empresa),
        ]);
    }

    public function create(Request $request)
    {
        // Trava de limite: no limite da faixa, o cadastro é bloqueado e a tela mostra o aviso com a oferta de upgrade.
        if (! Licenca::podeCadastrarAnimal($request->attributes->get('haras'))) {
            return redirect()->route('painel.animais')->with('limite_plantel', true);
        }

        return view('painel.animais.form', ['animal' => new Animal]);
    }

    public function store(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $dados = $this->validar($request);
        // Só animal que entra no plantel ativo conta para a faixa (vendido, transferido e morto ficam de fora).
        $contaNoPlantel = ! in_array($dados['situacao'] ?? 'ativo', Licenca::FORA_DO_PLANTEL, true);
        if ($contaNoPlantel && ! Licenca::podeCadastrarAnimal($empresa)) {
            return redirect()->route('painel.animais')->with('limite_plantel', true);
        }
        $animal = Animal::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        Auditoria::registrar('animal.criar', $animal);

        return redirect()->route('painel.animais')->with('ok', "{$animal->nome} cadastrado(a).");
    }

    public function show(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);

        return view('painel.animais.show', [
            'animal' => $animal,
            'arvore' => $animal->arvoreGenealogica(3),
        ]);
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
        $dados = $this->validar($request);
        // Voltar um animal ao plantel ativo também respeita o limite da faixa.
        $estavaFora = in_array($animal->situacao, Licenca::FORA_DO_PLANTEL, true);
        $ficaDentro = ! in_array($dados['situacao'] ?? $animal->situacao, Licenca::FORA_DO_PLANTEL, true);
        if ($estavaFora && $ficaDentro && ! Licenca::podeCadastrarAnimal($request->attributes->get('haras'))) {
            return redirect()->route('painel.animais')->with('limite_plantel', true);
        }
        $animal->update($dados);
        Auditoria::registrar('animal.editar', $animal, $antes);

        return redirect()->route('painel.animais.show', $animal)->with('ok', "Dados de {$animal->nome} salvos.");
    }

    public function morte(Request $request, Animal $animal)
    {
        $this->doHaras($request, $animal);
        $request->validate([
            'morte_data' => ['required', 'date', 'before_or_equal:today'],
            'morte_causa' => ['nullable', 'string', 'max:255'],
            'morte_laudo' => ['nullable', 'string', 'max:5000'],
        ]);
        $antes = $animal->getAttributes();
        $animal->update([
            'situacao' => 'morto',
            'situacao_data' => $request->input('morte_data'),
            'morte_data' => $request->input('morte_data'),
            'morte_causa' => $request->input('morte_causa'),
            'morte_laudo' => $request->input('morte_laudo'),
        ]);
        Auditoria::registrar('animal.morte', $animal, $antes);

        return redirect()->route('painel.animais.show', $animal)->with('ok', "Óbito de {$animal->nome} registrado.");
    }

    public function buscarGenitor(Request $request)
    {
        $q = $request->input('q', '');
        $sexo = $request->input('sexo');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $query = Animal::whereNull('excluido_em')
            ->where('situacao', '!=', 'morto')
            ->where(function ($qb) use ($q) {
                $qb->where('nome', 'like', "%{$q}%")
                    ->orWhere('registro', 'like', "%{$q}%");
            });

        if ($sexo) {
            $query->where('sexo', $sexo);
        }

        $animais = $query->with('empresa:id,nome')->limit(20)->get(['id', 'nome', 'raca', 'registro', 'empresa_id']);

        return response()->json($animais->map(fn ($a) => [
            'id' => $a->id,
            'nome' => $a->nome,
            'raca' => $a->raca ?? '',
            'registro' => $a->registro ?? '',
            'haras' => $a->empresa?->nome ?? '',
        ]));
    }

    private function doHaras(Request $request, Animal $animal): void
    {
        abort_unless($animal->empresa_id === $request->attributes->get('haras')->id && ! $animal->excluido_em, 404);
    }

    private function validar(Request $request): array
    {
        if (! Esquema::temV005()) {
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

        return $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'nome_registro' => ['nullable', 'string', 'max:150'],
            'apelido' => ['nullable', 'string', 'max:80'],
            'especie' => ['nullable', Rule::in(array_keys(Animal::ESPECIES))],
            'sexo' => ['nullable', Rule::in(['macho', 'femea'])],
            'categoria' => ['nullable', Rule::in(array_keys(Animal::CATEGORIAS))],
            'raca' => ['nullable', 'string', 'max:80'],
            'pelagem' => ['nullable', Rule::in(array_keys(Animal::PELAGENS))],
            'pelagem_complemento' => ['nullable', 'string', 'max:80'],
            'data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
            'registro' => ['nullable', 'string', 'max:60'],
            'registro_associacao' => ['nullable', Rule::in(array_keys(Animal::ASSOCIACOES))],
            'registro_definitivo' => ['nullable', 'boolean'],
            'microchip' => ['nullable', 'string', 'max:40'],
            'dna_coletado' => ['nullable', 'boolean'],
            'numero_plantel' => ['nullable', 'string', 'max:30'],
            'local_baia' => ['nullable', 'string', 'max:80'],
            'peso_kg' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'altura_cm' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'castrado' => ['nullable', 'boolean'],
            'pai' => ['nullable', 'string', 'max:120'],
            'pai_id' => ['nullable', 'integer', 'exists:animal,id'],
            'mae' => ['nullable', 'string', 'max:120'],
            'mae_id' => ['nullable', 'integer', 'exists:animal,id'],
            'data_aquisicao' => ['nullable', 'date', 'before_or_equal:today'],
            'forma_aquisicao' => ['nullable', Rule::in(array_keys(Animal::FORMAS_AQUISICAO))],
            'valor_aquisicao' => ['nullable', 'numeric', 'min:0'],
            'situacao' => ['nullable', Rule::in(array_keys(Animal::SITUACOES))],
            'situacao_data' => ['nullable', 'date'],
            'situacao_motivo' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
