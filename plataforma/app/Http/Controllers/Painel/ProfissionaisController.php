<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\EmpresaUsuario;
use App\Models\Profissional;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\CadastroProfissional;
use App\Support\Documento;
use App\Support\Modulos;
use App\Support\Permissao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Equipe / criador da propriedade. Tudo começa pela busca do CPF na base global de Profissionais:
 * encontrado, mostra os dados e vincula (como criador/proprietário ou com outras funções);
 * não encontrado, oferece o cadastro rápido na base de Profissionais e já conclui o vínculo.
 * O acesso externo (login próprio, com permissão por papel) faz parte do módulo "Gestão de Equipe e Acesso Externo".
 */
class ProfissionaisController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $vinculos = DB::table('empresa_profissional as ep')
            ->join('profissional as p', 'p.id', '=', 'ep.profissional_id')
            ->leftJoin('usuario as u', 'u.id', '=', 'ep.usuario_id')
            ->where('ep.empresa_id', $empresa->id)
            ->whereNull('p.excluido_em')
            ->select('p.id', 'p.uuid', 'p.nome', 'p.cpf', 'p.telefone', 'p.whatsapp', 'p.email', 'p.cidade', 'p.uf',
                'ep.id as vinculo_id', 'ep.funcao', 'ep.funcoes', 'ep.titular', 'ep.acesso_sistema', 'ep.papel_acesso', 'ep.usuario_id',
                'ep.situacao as vinculo_situacao', 'ep.data_inicio', 'u.email as login_email')
            ->orderByDesc('ep.titular')->orderBy('p.nome')->get();

        return view('painel.profissionais.index', [
            'vinculos' => $vinculos,
            'moduloEquipe' => Modulos::ativo($empresa, Modulos::EQUIPE),
            'proprietario' => $request->attributes->get('papel') === 'proprietario',
        ]);
    }

    /** Passo 1: só o CPF. Com o CPF informado, mostra o profissional encontrado ou o cadastro rápido. */
    public function create(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $cpf = Documento::digitos($request->query('cpf'));
        $dados = ['cpf' => null, 'encontrado' => null, 'erroCpf' => null, 'jaVinculado' => false,
            'proprietario' => $request->attributes->get('papel') === 'proprietario'];
        if ($request->has('cpf')) {
            if (strlen($cpf) !== 11 || ! Documento::valido($cpf)) {
                $dados['erroCpf'] = 'CPF inválido. Confira os números.';
            } else {
                $dados['cpf'] = $cpf;
                $profissional = Profissional::porCpf($cpf);
                if ($profissional && ! $profissional->ativo()) {
                    $dados['cpf'] = null;
                    $dados['erroCpf'] = 'O cadastro desse profissional não está ativo na plataforma. Fale com o atendimento.';
                } elseif ($profissional) {
                    $dados['encontrado'] = $profissional;
                    $dados['jaVinculado'] = DB::table('empresa_profissional')->where('empresa_id', $empresa->id)->where('profissional_id', $profissional->id)->exists();
                }
            }
        }

        return view('painel.profissionais.form', $dados);
    }

    public function store(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $cpf = Documento::digitos($request->input('cpf'));
        abort_unless(strlen($cpf) === 11 && Documento::valido($cpf), 422, 'CPF inválido.');

        $vinculo = $request->validate([
            'funcoes' => ['required', 'array', 'min:1'],
            'funcoes.*' => ['string', Rule::in(array_keys(Profissional::ESPECIALIDADES))],
            'titular' => ['nullable', 'boolean'],
            'data_inicio' => ['nullable', 'date'],
        ], ['funcoes.required' => 'Escolha pelo menos uma função nesta propriedade.']);

        $profissional = Profissional::porCpf($cpf);
        $novo = false;
        if ($profissional) {
            abort_unless($profissional->ativo(), 422, 'Profissional inativo.');
            if (DB::table('empresa_profissional')->where('empresa_id', $empresa->id)->where('profissional_id', $profissional->id)->exists()) {
                return redirect()->route('painel.profissionais')->withErrors(['cpf' => "{$profissional->nome} já está vinculado a esta propriedade."]);
            }
        } else {
            // Cadastro rápido na base global de Profissionais, antes de concluir o vínculo.
            $request->merge(['especialidades' => $vinculo['funcoes']]);
            $profissional = CadastroProfissional::criar(CadastroProfissional::validar($request), 'haras', $request->user()->id);
            Auditoria::registrar('profissional.criar', $profissional, null, null, $empresa->id);
            $novo = true;
        }

        // Só o proprietário define quem é o criador/proprietário titular.
        $titular = $request->boolean('titular') && $request->attributes->get('papel') === 'proprietario';
        $funcoes = array_values(array_unique($vinculo['funcoes'] + ($titular ? [99 => Profissional::PROPRIETARIO] : [])));
        DB::transaction(function () use ($empresa, $profissional, $funcoes, $titular, $vinculo) {
            if ($titular) {
                DB::table('empresa_profissional')->where('empresa_id', $empresa->id)->update(['titular' => 0]);
                $empresa->update(['titular_profissional_id' => $profissional->id]);
            }
            DB::table('empresa_profissional')->insert([
                'empresa_id' => $empresa->id, 'profissional_id' => $profissional->id,
                'funcao' => $this->funcoesTexto($funcoes), 'funcoes' => implode(',', $funcoes), 'titular' => $titular ? 1 : 0,
                'data_inicio' => $vinculo['data_inicio'] ?? now()->toDateString(), 'situacao' => 'ativo', 'criado_em' => now(),
            ]);
            foreach ($funcoes as $f) {
                $profissional->acrescentarEspecialidade($f);
            }
        });
        Auditoria::registrar('profissional.vincular', $profissional, null, ['haras' => $empresa->nome, 'funcoes' => $funcoes, 'titular' => $titular], $empresa->id);

        return redirect()->route('painel.profissionais')->with('ok', $profissional->nome.($novo ? ' cadastrado(a) na base de Profissionais e' : '').' vinculado(a) '.($titular ? 'como criador / proprietário titular.' : 'à equipe.'));
    }

    public function editar(Request $request, int $vinculo)
    {
        $empresa = $request->attributes->get('haras');
        $registro = DB::table('empresa_profissional')->where('id', $vinculo)->where('empresa_id', $empresa->id)->first();
        abort_unless($registro, 404);

        $dados = $request->validate([
            'funcoes' => ['required', 'array', 'min:1'],
            'funcoes.*' => ['string', Rule::in(array_keys(Profissional::ESPECIALIDADES))],
            'vinculo_situacao' => ['required', Rule::in(['ativo', 'desativado'])],
        ], ['funcoes.required' => 'Escolha pelo menos uma função.']);
        if ($registro->titular && $dados['vinculo_situacao'] === 'desativado') {
            return back()->withErrors(['vinculo_situacao' => 'O criador / proprietário titular não pode ser desativado. Defina outro titular antes.']);
        }
        $funcoes = array_values(array_unique($dados['funcoes']));
        DB::transaction(function () use ($registro, $dados, $funcoes, $empresa) {
            DB::table('empresa_profissional')->where('id', $registro->id)->update([
                'funcao' => $this->funcoesTexto($funcoes), 'funcoes' => implode(',', $funcoes),
                'situacao' => $dados['vinculo_situacao'],
                'data_fim' => $dados['vinculo_situacao'] === 'desativado' ? now()->toDateString() : null,
                'atualizado_em' => now(),
            ]);
            // Vínculo desativado perde também o acesso externo.
            if ($dados['vinculo_situacao'] === 'desativado' && $registro->usuario_id) {
                EmpresaUsuario::where('empresa_id', $empresa->id)->where('usuario_id', $registro->usuario_id)->where('papel', '!=', 'proprietario')->update(['status' => 'desativado']);
                DB::table('empresa_profissional')->where('id', $registro->id)->update(['acesso_sistema' => 0]);
            }
        });
        Auditoria::registrar('profissional.vinculo_editar', Profissional::find($registro->profissional_id), null, ['funcoes' => $funcoes, 'situacao' => $dados['vinculo_situacao']], $empresa->id);

        return back()->with('ok', 'Vínculo atualizado.');
    }

    /**
     * Acesso externo (módulo de equipe): o proprietário dá ou retira o login próprio de um profissional vinculado,
     * com um papel (RBAC). O profissional nunca vê a assinatura nem os dados financeiros do proprietário.
     */
    public function acesso(Request $request, int $vinculo)
    {
        $empresa = $request->attributes->get('haras');
        abort_unless($request->attributes->get('papel') === 'proprietario', 403, 'Só o proprietário pode dar ou retirar acessos.');
        if (! Modulos::ativo($empresa, Modulos::EQUIPE)) {
            return redirect()->route('painel.modulos.show', Modulos::EQUIPE);
        }
        $registro = DB::table('empresa_profissional')->where('id', $vinculo)->where('empresa_id', $empresa->id)->first();
        abort_unless($registro, 404);
        abort_if($registro->titular || $registro->usuario_id === $request->user()->id, 422, 'O acesso do titular não é alterado por aqui.');
        $profissional = Profissional::findOrFail($registro->profissional_id);

        if (! $request->boolean('conceder')) {
            if ($registro->usuario_id) {
                EmpresaUsuario::where('empresa_id', $empresa->id)->where('usuario_id', $registro->usuario_id)->where('papel', '!=', 'proprietario')->update(['status' => 'desativado']);
            }
            DB::table('empresa_profissional')->where('id', $registro->id)->update(['acesso_sistema' => 0, 'atualizado_em' => now()]);
            Auditoria::registrar('profissional.acesso_retirar', $profissional, null, ['haras' => $empresa->nome], $empresa->id);

            return back()->with('ok', "Acesso de {$profissional->nome} retirado.");
        }

        abort_unless($registro->situacao === 'ativo', 422, 'Reative o vínculo antes de dar acesso.');
        $usuario = $registro->usuario_id ? Usuario::find($registro->usuario_id) : ($profissional->usuario_id ? Usuario::find($profissional->usuario_id) : null);
        $dados = $request->validate([
            'papel' => ['required', Rule::in(array_keys(Permissao::PAPEIS_EQUIPE))],
            'email' => [$usuario || $profissional->email ? 'nullable' : 'required', 'email:filter', 'max:190'],
            'senha' => ['nullable', Password::min(8)->letters()->numbers()],
        ], ['email.required' => 'Informe o e-mail que o profissional vai usar para entrar.'], ['senha' => 'senha provisória']);

        if (! $usuario) {
            $email = mb_strtolower(trim($profissional->email ?: $dados['email']));
            $usuario = Usuario::where('email', $email)->first();
            if (! $usuario) {
                if (blank($dados['senha'] ?? null)) {
                    return back()->withInput($request->except('senha'))->withErrors(['senha' => 'Crie uma senha provisória para o primeiro acesso de '.$profissional->nome.'.']);
                }
                $usuario = Usuario::create(['nome' => $profissional->nome, 'email' => $email, 'telefone' => $profissional->telefone ?: $profissional->whatsapp,
                    'senha_hash' => Hash::make($dados['senha']), 'ativo' => true]);
            }
            $profissional->update(['usuario_id' => $profissional->usuario_id ?: $usuario->id, 'email' => $profissional->email ?: $email]);
        }
        $existente = EmpresaUsuario::where('empresa_id', $empresa->id)->where('usuario_id', $usuario->id)->first();
        abort_if($existente && $existente->papel === 'proprietario', 422, 'Essa pessoa já é proprietária desta propriedade.');
        EmpresaUsuario::updateOrCreate(['empresa_id' => $empresa->id, 'usuario_id' => $usuario->id], ['papel' => $dados['papel'], 'status' => 'ativo']);
        DB::table('empresa_profissional')->where('id', $registro->id)->update(['acesso_sistema' => 1, 'usuario_id' => $usuario->id, 'papel_acesso' => $dados['papel'], 'atualizado_em' => now()]);
        Auditoria::registrar('profissional.acesso_conceder', $profissional, null, ['haras' => $empresa->nome, 'papel' => $dados['papel'], 'login' => $usuario->email], $empresa->id);

        return back()->with('ok', "{$profissional->nome} agora entra com o e-mail {$usuario->email} como ".EmpresaUsuario::PAPEIS[$dados['papel']].'.');
    }

    private function funcoesTexto(array $funcoes): string
    {
        return mb_substr(collect($funcoes)->map(fn ($f) => Profissional::ESPECIALIDADES[$f] ?? $f)->implode(', '), 0, 100);
    }
}
