<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\ExecucaoRotina;
use App\Models\Rotina;
use App\Models\TipoRotina;
use App\Support\Auditoria;
use App\Support\Licenca;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $visao = $request->input('visao', 'semana');
        $dataRef = $request->input('data') ? Carbon::parse($request->input('data')) : Carbon::today();

        if ($visao === 'mes') {
            $inicio = $dataRef->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $fim = $dataRef->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        } else {
            $inicio = $dataRef->copy()->startOfWeek(Carbon::MONDAY);
            $fim = $inicio->copy()->addDays(6);
        }

        $this->gerarExecucoesPendentes($empresa->id, $fim);

        $execucoes = ExecucaoRotina::where('execucao_rotina.empresa_id', $empresa->id)
            ->whereBetween('data_prevista', [$inicio->toDateString(), $fim->toDateString()])
            ->join('rotina', 'rotina.id', '=', 'execucao_rotina.rotina_id')
            ->leftJoin('tipo_rotina', 'tipo_rotina.id', '=', 'rotina.tipo_rotina_id')
            ->leftJoin('animal', 'animal.id', '=', 'rotina.animal_id')
            ->whereNull('rotina.excluido_em')
            ->select(
                'execucao_rotina.*',
                'rotina.titulo', 'rotina.prioridade', 'rotina.hora_prevista',
                'tipo_rotina.nome as tipo_nome', 'tipo_rotina.icone as tipo_icone', 'tipo_rotina.cor as tipo_cor',
                'animal.nome as animal_nome'
            )
            ->orderBy('execucao_rotina.data_prevista')
            ->orderBy('rotina.hora_prevista')
            ->get();

        $this->marcarAtrasadas($execucoes);

        $dias = [];
        $cursor = $inicio->copy();
        while ($cursor->lte($fim)) {
            $chave = $cursor->toDateString();
            $dias[$chave] = [
                'data' => $cursor->copy(),
                'execucoes' => $execucoes->where('data_prevista', $cursor->toDateString()),
            ];
            $cursor->addDay();
        }

        return view('painel.agenda.index', [
            'visao' => $visao,
            'dataRef' => $dataRef,
            'inicio' => $inicio,
            'fim' => $fim,
            'dias' => $dias,
        ]);
    }

    public function hoje(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $hoje = Carbon::today();
        $this->gerarExecucoesPendentes($empresa->id, $hoje);

        $execucoes = ExecucaoRotina::where('execucao_rotina.empresa_id', $empresa->id)
            ->where('data_prevista', $hoje->toDateString())
            ->join('rotina', 'rotina.id', '=', 'execucao_rotina.rotina_id')
            ->leftJoin('tipo_rotina', 'tipo_rotina.id', '=', 'rotina.tipo_rotina_id')
            ->leftJoin('animal', 'animal.id', '=', 'rotina.animal_id')
            ->leftJoin('usuario', 'usuario.id', '=', 'rotina.responsavel_id')
            ->whereNull('rotina.excluido_em')
            ->select(
                'execucao_rotina.*',
                'rotina.titulo', 'rotina.prioridade', 'rotina.hora_prevista',
                'tipo_rotina.nome as tipo_nome', 'tipo_rotina.icone as tipo_icone', 'tipo_rotina.cor as tipo_cor',
                'animal.nome as animal_nome',
                'usuario.nome as responsavel_nome'
            )
            ->orderByRaw("FIELD(rotina.prioridade, 'urgente','alta','normal','baixa')")
            ->orderBy('rotina.hora_prevista')
            ->get();

        $this->marcarAtrasadas($execucoes);

        $atrasadas = ExecucaoRotina::where('execucao_rotina.empresa_id', $empresa->id)
            ->where('data_prevista', '<', $hoje->toDateString())
            ->where('execucao_rotina.situacao', 'pendente')
            ->join('rotina', 'rotina.id', '=', 'execucao_rotina.rotina_id')
            ->leftJoin('tipo_rotina', 'tipo_rotina.id', '=', 'rotina.tipo_rotina_id')
            ->leftJoin('animal', 'animal.id', '=', 'rotina.animal_id')
            ->whereNull('rotina.excluido_em')
            ->select(
                'execucao_rotina.*',
                'rotina.titulo', 'rotina.prioridade', 'rotina.hora_prevista',
                'tipo_rotina.nome as tipo_nome', 'tipo_rotina.icone as tipo_icone', 'tipo_rotina.cor as tipo_cor',
                'animal.nome as animal_nome'
            )
            ->orderBy('data_prevista')
            ->limit(50)
            ->get();

        $atrasadas->each(fn ($e) => $e->situacao = 'atrasada');

        $totais = (object) [
            'total' => $execucoes->count(),
            'concluidas' => $execucoes->where('situacao', 'concluida')->count(),
            'pendentes' => $execucoes->where('situacao', 'pendente')->count(),
            'atrasadas' => $atrasadas->count(),
        ];

        return view('painel.agenda.hoje', [
            'execucoes' => $execucoes,
            'atrasadas' => $atrasadas,
            'totais' => $totais,
            'hoje' => $hoje,
        ]);
    }

    public function rotinas(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $query = Rotina::where('empresa_id', $empresa->id)->whereNull('excluido_em');

        if ($busca = $request->input('busca')) {
            $query->where('titulo', 'like', "%{$busca}%");
        }
        if ($tipo = $request->input('tipo')) {
            $query->where('tipo_rotina_id', $tipo);
        }
        if ($freq = $request->input('frequencia')) {
            $query->where('frequencia', $freq);
        }
        if ($request->has('ativa')) {
            $query->where('ativa', $request->input('ativa'));
        }

        $rotinas = $query->with(['tipoRotina:id,nome,icone', 'animal:id,nome', 'responsavel:id,nome'])
            ->orderByDesc('ativa')
            ->orderBy('titulo')
            ->get();

        $tipos = TipoRotina::disponiveis($empresa->id)->get();

        return view('painel.rotinas.index', [
            'rotinas' => $rotinas,
            'tipos' => $tipos,
        ]);
    }

    public function criarRotina(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        return view('painel.rotinas.form', [
            'rotina' => new Rotina(['data_inicio' => Carbon::today()->toDateString(), 'prioridade' => 'normal', 'frequencia' => 'unica']),
            'tipos' => TipoRotina::disponiveis($empresa->id)->get(),
            'animais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(['id', 'nome']),
            'usuarios' => $this->usuariosHaras($empresa->id),
        ]);
    }

    public function salvarRotina(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);

        $dados = $this->validarRotina($request);
        $rotina = Rotina::create($dados + ['empresa_id' => $empresa->id, 'criado_por' => $request->user()->id]);
        $rotina->gerarExecucoes(Carbon::today()->addDays(30));
        Auditoria::registrar('rotina.criar', $rotina);

        return redirect()->route('painel.rotinas')->with('ok', "Rotina \"{$rotina->titulo}\" criada.");
    }

    public function editarRotina(Request $request, Rotina $rotina)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($rotina->empresa_id === $empresa->id && ! $rotina->excluido_em, 404);

        return view('painel.rotinas.form', [
            'rotina' => $rotina,
            'tipos' => TipoRotina::disponiveis($empresa->id)->get(),
            'animais' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->orderBy('nome')->get(['id', 'nome']),
            'usuarios' => $this->usuariosHaras($empresa->id),
        ]);
    }

    public function atualizarRotina(Request $request, Rotina $rotina)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($rotina->empresa_id === $empresa->id && ! $rotina->excluido_em, 404);

        $antes = $rotina->getAttributes();
        $dados = $this->validarRotina($request);
        $rotina->update($dados);

        if ($antes['frequencia'] !== $rotina->frequencia || $antes['data_inicio'] !== $rotina->data_inicio?->toDateString()) {
            $rotina->execucoes()->where('situacao', 'pendente')->where('data_prevista', '>', Carbon::today())->delete();
            $rotina->gerarExecucoes(Carbon::today()->addDays(30));
        }

        Auditoria::registrar('rotina.editar', $rotina, $antes);

        return redirect()->route('painel.rotinas')->with('ok', "Rotina \"{$rotina->titulo}\" atualizada.");
    }

    public function excluirRotina(Request $request, Rotina $rotina)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($rotina->empresa_id === $empresa->id && ! $rotina->excluido_em, 404);

        $rotina->update(['excluido_em' => now(), 'ativa' => false]);
        $rotina->execucoes()->where('situacao', 'pendente')->update(['situacao' => 'cancelada']);
        Auditoria::registrar('rotina.excluir', $rotina);

        return redirect()->route('painel.rotinas')->with('ok', "Rotina \"{$rotina->titulo}\" excluída.");
    }

    public function registrar(Request $request, ExecucaoRotina $execucao)
    {
        $empresa = $request->attributes->get('haras');
        Licenca::exigir($empresa);
        abort_unless($execucao->empresa_id === $empresa->id, 404);

        $request->validate([
            'situacao' => ['required', Rule::in(['concluida', 'cancelada'])],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ]);

        $antes = $execucao->getAttributes();
        $execucao->update([
            'situacao' => $request->input('situacao'),
            'data_execucao' => now(),
            'executado_por' => $request->user()->id,
            'observacoes' => $request->input('observacoes'),
        ]);

        Auditoria::registrar('execucao.registrar', $execucao, $antes);

        $redirecionar = $request->input('voltar', 'hoje');
        return redirect()->to($redirecionar)->with('ok', 'Execução registrada.');
    }

    private function validarRotina(Request $request): array
    {
        return $request->validate([
            'tipo_rotina_id' => ['nullable', 'integer', 'exists:tipo_rotina,id'],
            'titulo' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'animal_id' => ['nullable', 'integer', 'exists:animal,id'],
            'frequencia' => ['required', Rule::in(array_keys(Rotina::FREQUENCIAS))],
            'frequencia_dias' => ['nullable', 'integer', 'min:1', 'max:365'],
            'dia_semana' => ['nullable', 'integer', 'min:0', 'max:6'],
            'dia_mes' => ['nullable', 'integer', 'min:1', 'max:31'],
            'hora_prevista' => ['nullable', 'date_format:H:i'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'responsavel_id' => ['nullable', 'integer', 'exists:usuario,id'],
            'prioridade' => ['required', Rule::in(array_keys(Rotina::PRIORIDADES))],
            'ativa' => ['nullable', 'boolean'],
        ]);
    }

    private function usuariosHaras(int $empresaId): \Illuminate\Support\Collection
    {
        return DB::table('empresa_usuario')
            ->join('usuario', 'usuario.id', '=', 'empresa_usuario.usuario_id')
            ->where('empresa_usuario.empresa_id', $empresaId)
            ->where('empresa_usuario.status', 'ativo')
            ->orderBy('usuario.nome')
            ->get(['usuario.id', 'usuario.nome']);
    }

    private function gerarExecucoesPendentes(int $empresaId, Carbon $ate): void
    {
        $rotinas = Rotina::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->whereNull('excluido_em')
            ->get();

        foreach ($rotinas as $rotina) {
            $rotina->gerarExecucoes($ate);
        }
    }

    private function marcarAtrasadas($execucoes): void
    {
        $hoje = Carbon::today()->toDateString();
        foreach ($execucoes as $e) {
            if ($e->situacao === 'pendente' && $e->data_prevista->toDateString() < $hoje) {
                $e->situacao = 'atrasada';
            }
        }
    }
}
