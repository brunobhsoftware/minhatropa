<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\EmpresaUsuario;
use App\Models\Profissional;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfissionaisController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $vinculos = DB::table('empresa_profissional as ep')
            ->join('profissional as p', 'p.id', '=', 'ep.profissional_id')
            ->where('ep.empresa_id', $empresa->id)
            ->whereNull('p.excluido_em')
            ->select('p.id', 'p.uuid', 'p.nome', 'p.cpf', 'p.telefone', 'p.cidade', 'p.uf',
                'ep.id as vinculo_id', 'ep.funcao', 'ep.acesso_sistema', 'ep.situacao as vinculo_situacao', 'ep.data_inicio')
            ->orderBy('p.nome')->get();

        return view('painel.profissionais.index', compact('vinculos'));
    }

    public function create()
    {
        return view('painel.profissionais.form', [
            'profissional' => new Profissional,
            'especialidadesSelecionadas' => [],
        ]);
    }

    public function store(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $cpf = Documento::digitos($request->input('cpf'));

        $profissional = Profissional::where('cpf', $cpf)->whereNull('excluido_em')->first();
        if ($profissional) {
            $jaVinculado = DB::table('empresa_profissional')
                ->where('empresa_id', $empresa->id)
                ->where('profissional_id', $profissional->id)
                ->exists();
            if ($jaVinculado) {
                return back()->withInput()->withErrors(['cpf' => "{$profissional->nome} já está vinculado a este Haras."]);
            }
        } else {
            $request->merge([
                'cpf' => $cpf,
                'cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null,
            ]);
            $dados = $request->validate([
                'nome' => ['required', 'string', 'max:150'],
                'cpf' => ['required', 'string', 'size:11', function ($attr, $valor, $falha) {
                    if (! Documento::valido($valor)) {
                        $falha('CPF inválido.');
                    }
                }, Rule::unique('profissional', 'cpf')->whereNull('excluido_em')],
                'email' => ['nullable', 'email:filter', 'max:190'],
                'telefone' => ['nullable', 'string', 'max:30'],
                'whatsapp' => ['nullable', 'string', 'max:30'],
                'crmv' => ['nullable', 'string', 'max:30'],
            ], ['cpf.unique' => 'Já existe um profissional com esse CPF.']);
            $profissional = Profissional::create($dados + ['criado_por' => $request->user()->id]);
            $this->salvarEspecialidades($profissional, $request->input('especialidades', []));
        }

        $funcao = $request->input('funcao', '');
        DB::table('empresa_profissional')->insert([
            'empresa_id' => $empresa->id,
            'profissional_id' => $profissional->id,
            'funcao' => $funcao ?: null,
            'data_inicio' => $request->input('data_inicio') ?: now()->toDateString(),
            'situacao' => 'ativo',
            'criado_em' => now(),
        ]);
        Auditoria::registrar('profissional.vincular', $profissional, null, ['haras' => $empresa->nome, 'funcao' => $funcao]);

        return redirect()->route('painel.profissionais')->with('ok', "{$profissional->nome} vinculado ao Haras.");
    }

    public function editar(Request $request, int $vinculo)
    {
        $empresa = $request->attributes->get('haras');
        $registro = DB::table('empresa_profissional')
            ->where('id', $vinculo)->where('empresa_id', $empresa->id)->first();
        abort_unless($registro, 404);

        $dados = $request->validate([
            'funcao' => ['nullable', 'string', 'max:100'],
            'vinculo_situacao' => ['required', Rule::in(['ativo', 'desativado'])],
        ]);
        DB::table('empresa_profissional')->where('id', $vinculo)->update([
            'funcao' => $dados['funcao'],
            'situacao' => $dados['vinculo_situacao'],
            'data_fim' => $dados['vinculo_situacao'] === 'desativado' ? now()->toDateString() : null,
            'atualizado_em' => now(),
        ]);

        return back()->with('ok', 'Vínculo atualizado.');
    }

    /** Busca global de profissionais por nome ou CPF (JSON). */
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
            ->select('id', 'nome', 'cpf', 'cidade', 'uf')
            ->limit(10)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'cpf' => Documento::formatar($p->cpf),
                'cidade_uf' => $p->cidadeUf(),
                'especialidades' => collect(DB::table('profissional_especialidade')
                    ->where('profissional_id', $p->id)->pluck('especialidade'))
                    ->map(fn ($e) => Profissional::ESPECIALIDADES[$e] ?? $e)->implode(', '),
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
}
