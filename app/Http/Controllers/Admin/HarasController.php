<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria as RegistroAuditoria;
use App\Models\Empresa;
use App\Models\EmpresaUsuario;
use App\Models\ModuloSistema;
use App\Models\Plano;
use App\Models\PlanoPlantel;
use App\Models\Profissional;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\CadastroProfissional;
use App\Support\Documento;
use App\Support\Geografia;
use App\Support\Licenca;
use App\Support\Modulos;
use App\Support\PaginaPropriedade;
use App\Support\Titular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Cadastro e acompanhamento dos Haras / Fazendas clientes da plataforma.
 * Toda propriedade é identificada por CPF (Pessoa Física) ou CNPJ (Pessoa Jurídica), obrigatório e único.
 * Pessoa Física: o CPF precisa ser de um profissional ativo, que vira o criador / proprietário titular.
 */
class HarasController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->query('busca'));
        $situacao = $request->query('situacao');
        $digitos = Documento::digitos($busca);
        $uf = strtoupper((string) $request->query('uf'));
        $municipioId = (int) $request->query('municipio_id');

        $haras = Empresa::naoExcluidas()->withCount('usuarios')
            ->when(in_array($uf, Geografia::siglas(), true), fn ($q) => $q->where('uf', $uf))
            ->when($municipioId > 0, fn ($q) => $q->where('municipio_id', $municipioId))
            ->when($busca !== '', fn ($q) => $q->where(function ($q) use ($busca, $digitos) {
                $q->where('nome', 'like', "%{$busca}%")->orWhere('razao_social', 'like', "%{$busca}%")
                    ->orWhere('cidade', 'like', "%{$busca}%")->orWhere('responsavel_nome', 'like', "%{$busca}%");
                if (strlen($digitos) >= 3) {
                    $q->orWhere('documento', 'like', "%{$digitos}%");
                }
            }))
            ->when(isset(Empresa::SITUACOES[$situacao]), fn ($q) => $q->where('situacao', $situacao))
            ->orderBy('nome')->paginate(25)->withQueryString();

        return view('admin.haras.index', compact('haras', 'busca', 'situacao', 'uf', 'municipioId') + [
            'cidadeFiltro' => $municipioId ? \App\Models\Municipio::find($municipioId)?->nome : null,
            'documentosRepetidos' => Empresa::naoExcluidas()->whereNotNull('documento')->groupBy('documento')->havingRaw('COUNT(*) > 1')->pluck('documento'),
            'semDocumento' => Empresa::naoExcluidas()->whereNull('documento')->count(),
        ]);
    }

    public function create()
    {
        return view('admin.haras.form', [
            'empresa' => new Empresa(['situacao' => 'teste', 'tipo_propriedade' => 'haras']),
            'titular' => null,
            'planos' => Plano::where('ativo', 1)->orderBy('ordem')->get(),
            'v003' => true,
            'racasSelecionadas' => [],
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validar($request);
        $dados['situacao'] = $request->validate(['situacao' => ['required', Rule::in(array_keys(Empresa::SITUACOES))]])['situacao'];
        $empresa = Empresa::create($dados + ['criado_por' => $request->user()->id]);
        $this->salvarRacas($empresa, $request->input('racas', []));
        Titular::definir($empresa, $this->titular);
        PaginaPropriedade::salvarImagens($request, $empresa);
        Auditoria::registrar('haras.criar', $empresa);

        return redirect()->route('admin.haras.show', $empresa)->with('ok', "{$empresa->rotulo()} {$empresa->nome} cadastrado.");
    }

    public function show(Empresa $empresa)
    {
        abort_if($empresa->excluido_em, 404);

        return view('admin.haras.show', [
            'empresa' => $empresa->load('plano'),
            'vinculos' => $empresa->usuarios()->orderBy('nome')->get(),
            'v002' => true,
            'v003' => true,
            'totalAnimais' => Licenca::animais($empresa),
            'faixa' => Licenca::faixaAtual($empresa),
            'faixaSolicitada' => Licenca::faixaSolicitada($empresa),
            'faixas' => PlanoPlantel::orderBy('limite_min')->get(),
            'modulos' => ModuloSistema::orderBy('ordem')->get(),
            'modulosHaras' => Modulos::daEmpresa($empresa),
            'titular' => $empresa->titular,
            'equipe' => DB::table('empresa_profissional as ep')->join('profissional as p', 'p.id', '=', 'ep.profissional_id')->where('ep.empresa_id', $empresa->id)
                ->orderByDesc('ep.titular')->orderBy('p.nome')->get(['p.uuid', 'p.nome', 'p.cpf', 'ep.funcao', 'ep.titular', 'ep.acesso_sistema', 'ep.papel_acesso', 'ep.situacao']),
            'racas' => $empresa->racas(),
            'auditoria' => RegistroAuditoria::with('usuario')->where('empresa_id', $empresa->id)->orderByDesc('id')->limit(20)->get(),
        ]);
    }

    public function edit(Empresa $empresa)
    {
        abort_if($empresa->excluido_em, 404);

        return view('admin.haras.form', [
            'empresa' => $empresa,
            'titular' => $empresa->titular,
            'planos' => Plano::where('ativo', 1)->orderBy('ordem')->get(),
            'v003' => true,
            'racasSelecionadas' => $empresa->racas(),
        ]);
    }

    public function update(Request $request, Empresa $empresa)
    {
        abort_if($empresa->excluido_em, 404);
        $antes = $empresa->getAttributes();
        $empresa->update($this->validar($request, $empresa));
        $this->salvarRacas($empresa, $request->input('racas', []));
        Titular::definir($empresa, $this->titular);
        PaginaPropriedade::salvarImagens($request, $empresa);
        Auditoria::registrar('haras.editar', $empresa, $antes);

        return redirect()->route('admin.haras.show', $empresa)->with('ok', "Dados {$empresa->doRotulo()} salvos.");
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

    /** Define a faixa de plantel em vigor (plano base). Faixa paga libera também as ferramentas do plano. */
    public function faixa(Request $request, Empresa $empresa)
    {
        $dados = $request->validate(['faixa' => ['required', 'integer', Rule::exists('plano_plantel', 'id')]]);
        $faixa = PlanoPlantel::findOrFail($dados['faixa']);
        $animais = Licenca::animais($empresa);
        if ($faixa->limite_max !== null && $animais > $faixa->limite_max) {
            return back()->withErrors(['faixa' => "Esta propriedade tem {$animais} animais ativos: não cabe na faixa \"{$faixa->nome}\" ({$faixa->faixaTexto()})."]);
        }
        $antes = ['faixa' => Licenca::faixaAtual($empresa)?->nome];
        Licenca::definirFaixa($empresa, $faixa, $request->user()->id);
        Auditoria::registrar('haras.faixa_definir', $empresa, $antes, ['faixa' => $faixa->nome]);

        return back()->with('ok', "Faixa de plantel definida: {$faixa->nome} ({$faixa->faixaTexto()}).");
    }

    /** Liga ou desliga um módulo adicional para a propriedade. */
    public function modulo(Request $request, Empresa $empresa, ModuloSistema $modulo)
    {
        $ativo = $request->boolean('ativo');
        Modulos::definir($empresa, $modulo, $ativo, $request->user()->id);
        Auditoria::registrar($ativo ? 'haras.modulo_ativar' : 'haras.modulo_desativar', $empresa, null, ['modulo' => $modulo->nome]);

        return back()->with('ok', "Módulo \"{$modulo->nome}\" ".($ativo ? 'ativado' : 'desativado').'.');
    }

    /** Busca de CPF na base de Profissionais para a aba "Criador / proprietário" (JSON). */
    public function consultarCpf(Request $request)
    {
        $cpf = Documento::digitos($request->query('cpf'));
        if (strlen($cpf) !== 11 || ! Documento::valido($cpf)) {
            return response()->json(['situacao' => 'invalido', 'mensagem' => 'CPF inválido. Confira os números.']);
        }
        $profissional = Profissional::porCpf($cpf);
        if (! $profissional) {
            return response()->json(['situacao' => 'nao_encontrado', 'url' => route('admin.profissionais.novo', ['cpf' => $cpf]),
                'mensagem' => 'Esse CPF ainda não está na base de Profissionais. Cadastre o profissional e busque de novo.']);
        }

        return response()->json(['situacao' => $profissional->ativo() ? 'encontrado' : 'inativo',
            'mensagem' => $profissional->ativo() ? null : 'O cadastro desse profissional está '.mb_strtolower($profissional->situacaoTexto()).'.',
            'url' => route('admin.profissionais.show', $profissional)] + CadastroProfissional::resumo($profissional));
    }

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
            return back()->withInput($request->except('senha'))->withErrors(['email' => "{$usuario->nome} já tem acesso a esta propriedade."]);
        }
        $vinculo = EmpresaUsuario::create(['empresa_id' => $empresa->id, 'usuario_id' => $usuario->id, 'papel' => $dados['papel'], 'status' => 'ativo']);
        Auditoria::registrar('haras.usuario_vincular', $vinculo, null, ['usuario' => $email, 'papel' => $dados['papel']]);

        return back()->with('ok', "{$usuario->nome} agora tem acesso {$empresa->doRotulo()} como ".EmpresaUsuario::PAPEIS[$dados['papel']].'.');
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

    /** Upload da marca/logomarca do criador. */
    public function uploadMarca(Request $request, Empresa $empresa)
    {
        abort_if($empresa->excluido_em, 404);

        $request->validate(['marca_arquivo' => ['required', 'image', 'max:2048']]);
        $dir = public_path('uploads/marcas');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $nome = $empresa->uuid.'_'.time().'.'.$request->file('marca_arquivo')->getClientOriginalExtension();
        $request->file('marca_arquivo')->move($dir, $nome);

        if ($empresa->marca_arquivo && file_exists(public_path($empresa->marca_arquivo))) {
            @unlink(public_path($empresa->marca_arquivo));
        }
        $empresa->update(['marca_arquivo' => 'uploads/marcas/'.$nome]);
        Auditoria::registrar('haras.marca_upload', $empresa);

        return back()->with('ok', 'Marca/logomarca enviada.');
    }

    private function salvarRacas(Empresa $empresa, array $racas): void
    {
        $validas = array_intersect($racas, Empresa::RACAS);
        DB::table('empresa_raca')->where('empresa_id', $empresa->id)->delete();
        foreach ($validas as $raca) {
            DB::table('empresa_raca')->insert(['empresa_id' => $empresa->id, 'raca' => $raca]);
        }
    }

    /** Profissional titular resolvido na validação (para vincular depois de salvar). */
    private ?Profissional $titular = null;

    private function validar(Request $request, ?Empresa $empresa = null): array
    {
        $pf = $request->input('tipo_documento') === 'cpf';
        $request->merge([
            'documento' => Documento::digitos($request->input('documento')) ?: null,
            'titular_cpf' => Documento::digitos($request->input('titular_cpf')) ?: null,
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null,
            'corresp_cep' => preg_replace('/\D/', '', (string) $request->input('corresp_cep')) ?: null,
            'area_hectares' => $request->filled('area_hectares') ? str_replace(',', '.', str_replace('.', '', (string) $request->input('area_hectares'))) : null,
            'finalidades_criacao' => $request->has('finalidades') ? implode(',', array_intersect((array) $request->input('finalidades'), array_keys(Empresa::FINALIDADES))) ?: null : null,
            'preferencias_marcha' => $request->has('marchas') ? implode(',', array_intersect((array) $request->input('marchas'), array_keys(Empresa::MARCHAS))) ?: null : null,
        ]);
        // Pessoa Física: o titular é o próprio CPF da propriedade.
        if ($pf) {
            $request->merge(['titular_cpf' => $request->input('documento')]);
        }

        $regras = [
            'nome' => ['required', 'string', 'max:150'],
            'tipo_propriedade' => ['required', Rule::in(array_keys(Empresa::TIPOS_PROPRIEDADE))],
            'tipo_documento' => ['required', Rule::in(array_keys(Empresa::TITULARIDADES))],
            'razao_social' => [$pf ? 'nullable' : 'required', 'string', 'max:190'],
            'documento' => ['required', 'string', function ($attr, $valor, $falha) use ($pf) {
                if (strlen($valor) !== ($pf ? 11 : 14) || ! Documento::valido($valor)) {
                    $falha($pf ? 'CPF inválido. Confira os números.' : 'CNPJ inválido. Confira os números.');
                }
            }, Rule::unique('empresa', 'documento')->ignore($empresa?->id)],
            'titular_cpf' => ['nullable', 'string', function ($attr, $valor, $falha) {
                if (! Documento::valido($valor) || strlen($valor) !== 11) {
                    $falha('CPF do criador / proprietário inválido.');
                }
            }],
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
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'plano_id' => ['nullable', Rule::exists('plano', 'id')],
            'observacoes' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'como_chegar' => ['nullable', 'string', 'max:400'],
            'corresp_cep' => ['nullable', 'digits:8'],
            'corresp_logradouro' => ['nullable', 'string', 'max:190'],
            'corresp_numero' => ['nullable', 'string', 'max:20'],
            'corresp_complemento' => ['nullable', 'string', 'max:100'],
            'corresp_bairro' => ['nullable', 'string', 'max:100'],
            'telefone_residencial' => ['nullable', 'string', 'max:30'],
            'telefone_celular' => ['nullable', 'string', 'max:30'],
            'telefone_comercial' => ['nullable', 'string', 'max:30'],
            'finalidades_criacao' => ['nullable', 'string', 'max:500'],
            'preferencias_marcha' => ['nullable', 'string', 'max:300'],
            'marca_descricao' => ['nullable', 'string', 'max:500'],
            'site_url' => ['nullable', 'url:http,https', 'max:255'],
            'subsite_slug' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9\-]*$/', Rule::unique('empresa', 'subsite_slug')->ignore($empresa?->id)],
        ] + Geografia::regras($request, '', true) + Geografia::regras($request, 'corresp_') + PaginaPropriedade::regras($empresa);

        $dados = $request->validate($regras, [
            'tipo_propriedade.required' => 'Escolha o tipo de propriedade (Haras, Fazenda…).',
            'tipo_documento.required' => 'Escolha a titularidade: Pessoa Física (CPF) ou Pessoa Jurídica (CNPJ).',
            'documento.required' => 'O CPF ou CNPJ é obrigatório: é a identificação única da propriedade.',
            'documento.unique' => 'Já existe um Haras / Fazenda cadastrado com esse CPF/CNPJ.',
            'razao_social.required' => 'Informe a razão social (Pessoa Jurídica).',
            'subsite_slug.unique' => 'Esse endereço de subsite já está em uso.',
            'subsite_slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hifens.',
        ] + Geografia::mensagens() + Geografia::mensagens('corresp_') + PaginaPropriedade::mensagens());
        $dados = PaginaPropriedade::colunas($request, $dados, $empresa);

        // Criador / proprietário: vem da base de Profissionais pelo CPF (obrigatório para Pessoa Física).
        $this->titular = null;
        if (filled($dados['titular_cpf'] ?? null)) {
            $this->titular = Profissional::porCpf($dados['titular_cpf']);
            if (! $this->titular || ! $this->titular->ativo()) {
                throw \Illuminate\Validation\ValidationException::withMessages([($pf ? 'documento' : 'titular_cpf') => $this->titular
                    ? 'O cadastro de Profissional desse CPF não está ativo.'
                    : 'Esse CPF ainda não está na base de Profissionais. Cadastre o profissional (Profissionais → Novo) antes de '.($pf ? 'cadastrar a propriedade como Pessoa Física.' : 'vinculá-lo como criador / proprietário.')]);
            }
            // Puxa os dados do profissional, sem redigitação.
            $dados['responsavel_nome'] = $this->titular->nome;
            $dados['responsavel_email'] = $dados['responsavel_email'] ?? $this->titular->email;
            $dados['responsavel_telefone'] = $dados['responsavel_telefone'] ?? ($this->titular->whatsapp ?: $this->titular->telefone);
            if ($pf) {
                $dados['razao_social'] = $this->titular->nome;
            }
        }
        unset($dados['titular_cpf']);
        $dados['titular_profissional_id'] = $this->titular?->id ?? ($pf ? null : $empresa?->titular_profissional_id);

        return Geografia::colunas(Geografia::colunas($dados), 'corresp_');
    }
}
