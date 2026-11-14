<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Cobricao;
use App\Models\Nascimento;
use App\Support\Auditoria;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NascimentosController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        return view('painel.nascimentos.index', [
            'nascimentos' => Nascimento::where('empresa_id', $empresa->id)->whereNull('excluido_em')
                ->with(['mae:id,nome', 'pai:id,nome', 'animal:id,nome'])
                ->orderByDesc('data_nascimento')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        return view('painel.nascimentos.form', [
            'nascimento' => new Nascimento,
            'maes' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('sexo', 'femea')->orderBy('nome')->get(['id', 'nome']),
            'pais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('sexo', 'macho')->orderBy('nome')->get(['id', 'nome']),
            'cobricoes' => Cobricao::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('resultado', 'confirmada')->orderByDesc('data_cobricao')->get(['id', 'egua_id', 'garanhao_id', 'data_cobricao']),
            'veterinarios' => $this->veterinarios($empresa->id),
        ]);
    }

    public function store(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        $dados = $this->validar($request);

        $nascimento = Nascimento::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);

        if ($request->boolean('criar_animal') && $dados['nome_produto']) {
            $animal = Animal::create([
                'empresa_id' => $empresa->id,
                'nome' => $dados['nome_produto'],
                'sexo' => $dados['sexo'] === 'indefinido' ? null : $dados['sexo'],
                'data_nascimento' => $dados['data_nascimento'],
                'pai_id' => $dados['pai_id'] ?? null,
                'mae_id' => $dados['mae_id'],
                'forma_aquisicao' => 'nascimento',
                'especie' => 'equino',
                'situacao' => $dados['vivo'] ? 'ativo' : 'morto',
                'criado_por' => $request->user()->id,
            ]);
            $nascimento->update(['animal_id' => $animal->id]);
            Auditoria::registrar('animal.criar', $animal);
        }

        if ($nascimento->cobricao_id) {
            Cobricao::where('id', $nascimento->cobricao_id)->update(['resultado' => 'nascimento']);
        }

        Auditoria::registrar('nascimento.criar', $nascimento);

        return redirect()->route('painel.nascimentos')->with('ok', 'Nascimento registrado.');
    }

    public function show(Request $request, Nascimento $nascimento)
    {
        $this->doHaras($request, $nascimento);

        return view('painel.nascimentos.show', ['nascimento' => $nascimento->load(['mae', 'pai', 'animal', 'cobricao.egua', 'veterinario'])]);
    }

    private function doHaras(Request $request, Nascimento $nascimento): void
    {
        abort_unless($nascimento->empresa_id === $request->attributes->get('haras')->id && ! $nascimento->excluido_em, 404);
    }

    private function veterinarios(int $empresaId)
    {
        return \DB::table('empresa_profissional')
            ->join('profissional', 'profissional.id', '=', 'empresa_profissional.profissional_id')
            ->join('profissional_especialidade', 'profissional_especialidade.profissional_id', '=', 'profissional.id')
            ->where('empresa_profissional.empresa_id', $empresaId)
            ->where('empresa_profissional.situacao', 'ativo')
            ->where('profissional.situacao', 'ativo')
            ->where('profissional_especialidade.especialidade', 'veterinario')
            ->select('profissional.id', 'profissional.nome')
            ->distinct()->orderBy('profissional.nome')->get();
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'cobricao_id' => ['nullable', 'exists:cobricao,id'],
            'mae_id' => ['required', 'exists:animal,id'],
            'pai_id' => ['nullable', 'exists:animal,id'],
            'pai_nome' => ['nullable', 'string', 'max:150'],
            'data_nascimento' => ['required', 'date', 'before_or_equal:today'],
            'tipo_parto' => ['required', Rule::in(array_keys(Nascimento::TIPOS_PARTO))],
            'sexo' => ['nullable', Rule::in(['macho', 'femea', 'indefinido'])],
            'nome_produto' => ['nullable', 'string', 'max:120'],
            'peso_nascimento' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'vivo' => ['nullable', 'boolean'],
            'veterinario_id' => ['nullable', 'exists:profissional,id'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
