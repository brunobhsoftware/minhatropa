<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria as RegistroAuditoria;
use App\Models\Empresa;
use App\Models\EmpresaUsuario;
use App\Models\Plano;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Cadastro e acompanhamento dos Haras clientes da plataforma. */
class HarasController extends Controller
{
    private const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    public function index(Request $request)
    {
        $busca = trim((string) $request->query('busca'));
        $situacao = $request->query('situacao');
        $digitos = Documento::digitos($busca);

        $haras = Empresa::naoExcluidas()->withCount('usuarios')
            ->when($busca !== '', fn ($q) => $q->where(function ($q) use ($busca, $digitos) {
                $q->where('nome', 'like', "%{$busca}%")->orWhere('razao_social', 'like', "%{$busca}%")
                    ->orWhere('cidade', 'like', "%{$busca}%")->orWhere('responsavel_nome', 'like', "%{$busca}%");
                if (strlen($digitos) >= 3) {
                    $q->orWhere('documento', 'like', "%{$digitos}%");
                }
            }))
            ->when(isset(Empresa::SITUACOES[$situacao]), fn ($q) => $q->where('situacao', $situacao))
            ->orderBy('nome')->paginate(25)->withQueryString();

        return view('admin.haras.index', compact('haras', 'busca', 'situacao'));
    }

    public function create()
    {
        return view('admin.haras.form', ['empresa' => new Empresa(['situacao' => 'teste']), 'ufs' => self::UFS, 'planos' => Plano::where('ativo', 1)->orderBy('ordem')->get()]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['situacao'] = $request->validate(['situacao' => ['required', Rule::in(array_keys(Empresa::SITUACOES))]])['situacao'];
        $empresa = Empresa::create($dados + ['criado_por' => $request->user()->id]);
        Auditoria::registrar('haras.criar', $empresa);

        return redirect()->route('admin.haras.show', $empresa)->with('ok', "Haras {$empresa->nome} cadastrado.");
    }

    public function show(Empresa $empresa)
    {
        abort_if($empresa->excluido_em, 404);

        return view('admin.haras.show', [
            'empresa' => $empresa->load('plano'),
            'vinculos' => $empresa->usuarios()->orderBy('nome')->get(),
            'auditoria' => RegistroAuditoria::with('usuario')->where('empresa_id', $empresa->id)->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function edit(Empresa $empresa)
    {
        abort_if($empresa->excluido_em, 404);

        return view('admin.haras.form', ['empresa' => $empresa, 'ufs' => self::UFS, 'planos' => Plano::where('ativo', 1)->orderBy('ordem')->get()]);
    }

    public function update(Request $request, Empresa $empresa)
    {
        abort_if($empresa->excluido_em, 404);
        $antes = $empresa->getAttributes();
        $empresa->update($this->validar($request, $empresa));
        Auditoria::registrar('haras.editar', $empresa, $antes);

        return redirect()->route('admin.haras.show', $empresa)->with('ok', 'Dados do Haras salvos.');
    }

    public function situacao(Request $request, Empresa $empresa)
    {
        $dados = $request->validate([
            'situacao' => ['required', Rule::in(array_keys(Empresa::SITUACOES))],
            'situacao_motivo' => ['nullable', 'required_if:situacao,suspenso,encerrado', 'string', 'max:255'],
        ], ['situacao_motivo.required_if' => 'Informe o motivo para suspender ou encerrar.']);
        $antes = $empresa->only(['situacao', 'situacao_motivo']);
        $empresa->update(['situacao' => $dados['situacao'], 'situacao_motivo' => $dados['situacao_motivo'] ?? null]);
        Auditoria::registrar('haras.situacao', $empresa, $antes, $empresa->only(['situacao', 'situacao_motivo']));

        return back()->with('ok', 'Situação alterada para "'.$empresa->situacaoTexto().'".');
    }

    /** Dá acesso a uma pessoa a este Haras (cria o usuário se ainda não existir). */
    public function vincularUsuario(Request $request, Empresa $empresa)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:filter', 'max:190'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'papel' => ['required', Rule::in(array_keys(EmpresaUsuario::PAPEIS))],
            'senha' => ['nullable', Password::min(10)->letters()->numbers()],
        ]);
        $email = mb_strtolower(trim($dados['email']));
        $usuario = Usuario::firstOrNew(['email' => $email]);
        if (! $usuario->exists) {
            $usuario->forceFill(['nome' => $dados['nome'], 'telefone' => $dados['telefone'] ?? null, 'ativo' => true]);
        }
        if (filled($dados['senha'] ?? null)) {
            $usuario->senha_hash = Hash::make($dados['senha']);
        }
        $usuario->save();

        if (EmpresaUsuario::where('empresa_id', $empresa->id)->where('usuario_id', $usuario->id)->exists()) {
            return back()->withInput($request->except('senha'))->withErrors(['email' => "{$usuario->nome} já tem acesso a este Haras."]);
        }
        $vinculo = EmpresaUsuario::create(['empresa_id' => $empresa->id, 'usuario_id' => $usuario->id, 'papel' => $dados['papel'], 'status' => 'ativo']);
        Auditoria::registrar('haras.usuario_vincular', $vinculo, null, ['usuario' => $email, 'papel' => $dados['papel']]);

        return back()->with('ok', "{$usuario->nome} agora tem acesso ao Haras como ".EmpresaUsuario::PAPEIS[$dados['papel']].'.');
    }

    public function alterarVinculo(Request $request, Empresa $empresa, int $vinculo)
    {
        $registro = EmpresaUsuario::where('empresa_id', $empresa->id)->findOrFail($vinculo);
        $dados = $request->validate([
            'papel' => ['required', Rule::in(array_keys(EmpresaUsuario::PAPEIS))],
            'status' => ['required', Rule::in(['ativo', 'desativado'])],
        ]);
        $antes = $registro->getAttributes();
        $registro->update($dados);
        Auditoria::registrar('haras.usuario_alterar', $registro, $antes);

        return back()->with('ok', 'Acesso atualizado.');
    }

    private function validar(Request $request, ?Empresa $empresa = null): array
    {
        $request->merge([
            'documento' => Documento::digitos($request->input('documento')) ?: null,
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null,
            'uf' => $request->filled('uf') ? mb_strtoupper($request->input('uf')) : null,
            'area_hectares' => $request->filled('area_hectares') ? str_replace(',', '.', str_replace('.', '', (string) $request->input('area_hectares'))) : null,
        ]);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'razao_social' => ['nullable', 'string', 'max:190'],
            'documento' => ['nullable', 'string', function ($attr, $valor, $falha) {
                if (! Documento::valido($valor)) {
                    $falha('CPF ou CNPJ inválido. Confira os números.');
                }
            }, Rule::unique('empresa', 'documento')->ignore($empresa?->id)->whereNull('excluido_em')],
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:filter', 'max:190'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'responsavel_nome' => ['nullable', 'string', 'max:150'],
            'responsavel_email' => ['nullable', 'email:filter', 'max:190'],
            'responsavel_telefone' => ['nullable', 'string', 'max:30'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:190'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'uf' => ['nullable', Rule::in(self::UFS)],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'plano_id' => ['nullable', Rule::exists('plano', 'id')],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ], ['documento.unique' => 'Já existe um Haras cadastrado com esse CPF/CNPJ.']);

        $dados['tipo_documento'] = Documento::tipo($dados['documento'] ?? null);

        return $dados;
    }
}
