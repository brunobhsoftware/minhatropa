<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Cobricao;
use App\Models\Profissional;
use App\Support\Auditoria;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CobricoesController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $query = Cobricao::where('empresa_id', $empresa->id)->whereNull('excluido_em');

        if ($resultado = $request->input('resultado')) {
            $query->where('resultado', $resultado);
        }
        if ($busca = $request->input('busca')) {
            $query->whereHas('egua', fn ($q) => $q->where('nome', 'like', "%{$busca}%"));
        }

        return view('painel.cobricoes.index', [
            'cobricoes' => $query->with(['egua:id,nome', 'garanhao:id,nome'])->orderByDesc('data_cobricao')->get(),
        ]);
    }

    public function create(Request $request)
    {
        Licenca::exigir($request->attributes->get('haras'));
        $empresa = $request->attributes->get('haras');

        return view('painel.cobricoes.form', [
            'cobricao' => new Cobricao,
            'eguas' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('sexo', 'femea')->orderBy('nome')->get(['id', 'nome']),
            'garanhoes' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('sexo', 'macho')->orderBy('nome')->get(['id', 'nome']),
            'veterinarios' => $this->veterinarios($empresa->id),
        ]);
    }

    public function store(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        $dados = $this->validar($request);
        $cobricao = Cobricao::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        Auditoria::registrar('cobricao.criar', $cobricao);

        return redirect()->route('painel.cobricoes')->with('ok', 'Cobrição registrada.');
    }

    public function show(Request $request, Cobricao $cobricao)
    {
        $this->doHaras($request, $cobricao);

        return view('painel.cobricoes.show', ['cobricao' => $cobricao->load(['egua', 'garanhao', 'receptora', 'veterinario', 'nascimento.animal'])]);
    }

    public function edit(Request $request, Cobricao $cobricao)
    {
        $this->doHaras($request, $cobricao);
        $empresa = $request->attributes->get('haras');

        return view('painel.cobricoes.form', [
            'cobricao' => $cobricao,
            'eguas' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('sexo', 'femea')->orderBy('nome')->get(['id', 'nome']),
            'garanhoes' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('sexo', 'macho')->orderBy('nome')->get(['id', 'nome']),
            'veterinarios' => $this->veterinarios($empresa->id),
        ]);
    }

    public function update(Request $request, Cobricao $cobricao)
    {
        $this->doHaras($request, $cobricao);
        $antes = $cobricao->getAttributes();
        $cobricao->update($this->validar($request));
        Auditoria::registrar('cobricao.editar', $cobricao, $antes);

        return redirect()->route('painel.cobricoes.show', $cobricao)->with('ok', 'Cobrição atualizada.');
    }

    private function doHaras(Request $request, Cobricao $cobricao): void
    {
        abort_unless($cobricao->empresa_id === $request->attributes->get('haras')->id && ! $cobricao->excluido_em, 404);
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
            'egua_id' => ['required', 'exists:animal,id'],
            'garanhao_id' => ['nullable', 'exists:animal,id'],
            'garanhao_nome' => ['nullable', 'string', 'max:150'],
            'receptora_id' => ['nullable', 'exists:animal,id'],
            'tipo' => ['required', Rule::in(array_keys(Cobricao::TIPOS))],
            'subtipo' => ['nullable', Rule::in(array_keys(Cobricao::SUBTIPOS))],
            'data_cobricao' => ['required', 'date', 'before_or_equal:today'],
            'data_confirmacao' => ['nullable', 'date'],
            'data_prevista_parto' => ['nullable', 'date'],
            'veterinario_id' => ['nullable', 'exists:profissional,id'],
            'local' => ['nullable', 'string', 'max:150'],
            'resultado' => ['nullable', Rule::in(array_keys(Cobricao::RESULTADOS))],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
