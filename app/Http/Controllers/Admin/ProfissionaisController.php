<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profissional;
use App\Support\Auditoria;
use App\Support\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfissionaisController extends Controller
{
    private const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    public function index(Request $request)
    {
        $busca = trim((string) $request->query('busca'));
        $situacao = $request->query('situacao');
        $digitos = Documento::digitos($busca);

        $profissionais = Profissional::naoExcluidos()
            ->when($busca !== '', fn ($q) => $q->where(function ($q) use ($busca, $digitos) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")
                    ->orWhere('cidade', 'like', "%{$busca}%");
                if (strlen($digitos) >= 3) {
                    $q->orWhere('cpf', 'like', "%{$digitos}%");
                }
            }))
            ->when(isset(Profissional::SITUACOES[$situacao]), fn ($q) => $q->where('situacao', $situacao))
            ->orderBy('nome')->paginate(25)->withQueryString();

        return view('admin.profissionais.index', compact('profissionais', 'busca', 'situacao'));
    }

    public function create()
    {
        return view('admin.profissionais.form', [
            'profissional' => new Profissional,
            'ufs' => self::UFS,
            'especialidadesSelecionadas' => [],
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $profissional = Profissional::create($dados + ['criado_por' => $request->user()->id]);
        $this->salvarEspecialidades($profissional, $request->input('especialidades', []));
        Auditoria::registrar('profissional.criar', $profissional);

        return redirect()->route('admin.profissionais.show', $profissional)->with('ok', "Profissional {$profissional->nome} cadastrado.");
    }

    public function show(Profissional $profissional)
    {
        abort_if($profissional->excluido_em, 404);

        return view('admin.profissionais.show', [
            'profissional' => $profissional,
            'especialidades' => $profissional->especialidades(),
            'vinculos' => $profissional->haras()->naoExcluidas()->get(),
        ]);
    }

    public function edit(Profissional $profissional)
    {
        abort_if($profissional->excluido_em, 404);

        return view('admin.profissionais.form', [
            'profissional' => $profissional,
            'ufs' => self::UFS,
            'especialidadesSelecionadas' => $profissional->especialidades(),
        ]);
    }

    public function update(Request $request, Profissional $profissional)
    {
        abort_if($profissional->excluido_em, 404);
        $antes = $profissional->getAttributes();
        $profissional->update($this->validar($request, $profissional));
        $this->salvarEspecialidades($profissional, $request->input('especialidades', []));
        Auditoria::registrar('profissional.editar', $profissional, $antes);

        return redirect()->route('admin.profissionais.show', $profissional)->with('ok', 'Dados do profissional salvos.');
    }

    public function situacao(Request $request, Profissional $profissional)
    {
        $dados = $request->validate([
            'situacao' => ['required', Rule::in(array_keys(Profissional::SITUACOES))],
            'situacao_motivo' => ['nullable', 'required_if:situacao,banido', 'string', 'max:255'],
        ], ['situacao_motivo.required_if' => 'Informe o motivo do banimento.']);
        $antes = $profissional->only(['situacao', 'situacao_motivo']);
        $profissional->update(['situacao' => $dados['situacao'], 'situacao_motivo' => $dados['situacao_motivo'] ?? null]);
        Auditoria::registrar('profissional.situacao', $profissional, $antes, $profissional->only(['situacao', 'situacao_motivo']));

        return back()->with('ok', 'Situação alterada para "'.$profissional->situacaoTexto().'".');
    }

    /** Busca global de profissionais (JSON, usada pelo painel dos Haras). */
    public function buscar(Request $request)
    {
        $termo = trim((string) $request->query('q'));
        if (strlen($termo) < 2) {
            return response()->json([]);
        }
        $digitos = Documento::digitos($termo);

        $resultados = Profissional::naoExcluidos()->where('situacao', 'ativo')
            ->where(function ($q) use ($termo, $digitos) {
                $q->where('nome', 'like', "%{$termo}%");
                if (strlen($digitos) >= 3) {
                    $q->orWhere('cpf', 'like', "%{$digitos}%");
                }
            })
            ->select('id', 'uuid', 'nome', 'cpf', 'cidade', 'uf')
            ->limit(15)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'nome' => $p->nome,
                'cpf' => Documento::formatar($p->cpf),
                'cidade_uf' => $p->cidadeUf(),
            ]);

        return response()->json($resultados);
    }

    private function salvarEspecialidades(Profissional $profissional, array $especialidades): void
    {
        $validas = array_intersect($especialidades, array_keys(Profissional::ESPECIALIDADES));
        DB::table('profissional_especialidade')->where('profissional_id', $profissional->id)->delete();
        foreach ($validas as $esp) {
            DB::table('profissional_especialidade')->insert(['profissional_id' => $profissional->id, 'especialidade' => $esp]);
        }
    }

    private function validar(Request $request, ?Profissional $profissional = null): array
    {
        $request->merge([
            'cpf' => Documento::digitos($request->input('cpf')) ?: null,
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null,
            'uf' => $request->filled('uf') ? mb_strtoupper($request->input('uf')) : null,
        ]);

        return $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'cpf' => ['required', 'string', 'size:11', function ($attr, $valor, $falha) {
                if (! Documento::valido($valor)) {
                    $falha('CPF inválido.');
                }
            }, Rule::unique('profissional', 'cpf')->ignore($profissional?->id)->whereNull('excluido_em')],
            'email' => ['nullable', 'email:filter', 'max:190'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'telefone_fixo' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:190'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'uf' => ['nullable', Rule::in(self::UFS)],
            'crmv' => ['nullable', 'string', 'max:30'],
            'descricao' => ['nullable', 'string', 'max:5000'],
        ], ['cpf.unique' => 'Já existe um profissional cadastrado com esse CPF.']);
    }
}
