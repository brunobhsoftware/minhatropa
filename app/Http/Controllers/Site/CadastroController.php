<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaUsuario;
use App\Models\Profissional;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\CadastroProfissional;
use App\Support\Documento;
use App\Support\Esquema;
use App\Support\Geografia;
use App\Support\HarasAtual;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * "Cadastrar meu Haras / Fazenda": a própria propriedade cria a conta pelo site.
 *
 * Fluxo: tipo de propriedade → titularidade (Pessoa Física ou Jurídica) → CPF ou CNPJ, que é obrigatório e
 * único (não existem duas propriedades com o mesmo documento) → localização por UF e município (IBGE).
 * Pessoa Física só cadastra a propriedade se o CPF já tiver cadastro ATIVO na base de Profissionais: o sistema
 * puxa os dados dessa pessoa e a vincula como proprietário titular. Sem cadastro, o avanço é bloqueado e a pessoa
 * é levada a concluir primeiro o cadastro de Profissional.
 */
class CadastroController extends Controller
{
    /** Guarda na sessão o profissional que a própria pessoa acabou de cadastrar (aí os dados podem aparecer inteiros). */
    private const SESSAO_PROFISSIONAL = 'cadastro_profissional_id';

    public function create(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('painel');
        }
        $cpf = Documento::digitos($request->query('cpf'));

        return view('site.cadastro', [
            'disponivel' => Esquema::atual(),
            'gratis' => Esquema::atual() ? Licenca::faixas()->first() : null,
            'cpfInicial' => strlen($cpf) === 11 ? Documento::formatar($cpf) : null,
        ]);
    }

    /** Consulta do CPF na base de Profissionais (JSON). Em tela pública os dados voltam mascarados. */
    public function consultarCpf(Request $request)
    {
        abort_unless(Esquema::atual(), 503);
        $cpf = Documento::digitos($request->input('cpf'));
        if (strlen($cpf) !== 11 || ! Documento::valido($cpf)) {
            return response()->json(['situacao' => 'invalido', 'mensagem' => 'CPF inválido. Confira os números.']);
        }
        if (Empresa::where('documento', $cpf)->exists()) {
            return response()->json(['situacao' => 'ja_cadastrado', 'mensagem' => 'Já existe um Haras / Fazenda cadastrado com esse CPF. Entre com a sua senha ou fale com o atendimento.']);
        }
        $profissional = Profissional::porCpf($cpf);
        if (! $profissional) {
            return response()->json(['situacao' => 'nao_encontrado', 'url' => route('cadastro.profissional', ['cpf' => $cpf]),
                'mensagem' => 'Esse CPF ainda não tem cadastro de Profissional. Para cadastrar um Haras / Fazenda como Pessoa Física, conclua primeiro o seu cadastro de Profissional.']);
        }
        if (! $profissional->ativo()) {
            return response()->json(['situacao' => 'inativo', 'mensagem' => 'O cadastro de Profissional desse CPF não está ativo. Fale com o atendimento: atendimento@minhatropa.com.br.']);
        }
        $meu = (int) session(self::SESSAO_PROFISSIONAL) === $profissional->id;

        return response()->json([
            'situacao' => 'encontrado',
            'nome' => $meu ? $profissional->nome : $profissional->nomeMascarado(),
            'email' => $meu ? $profissional->email : $profissional->emailMascarado(),
            'email_completo' => $meu ? $profissional->email : null,
            'telefone' => $meu ? ($profissional->whatsapp ?: $profissional->telefone) : null,
            'cidade_uf' => $profissional->cidadeUf(),
            'funcoes' => $profissional->especialidadesTexto(),
            'mensagem' => 'Cadastro de Profissional encontrado. Os dados serão usados no cadastro e essa pessoa ficará como proprietário titular.',
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Esquema::atual(), 503);
        if (filled($request->input('site'))) {
            return redirect()->route('inicio');
        }
        $titularidade = $request->input('titularidade');
        $request->merge(['documento' => Documento::digitos($request->input('documento')) ?: null]);
        $pf = $titularidade === 'cpf';
        $voltar = fn (array $erros) => back()->withInput($request->except('senha', 'senha_confirmation'))->withErrors($erros);

        $dados = $request->validate([
            'tipo_propriedade' => ['required', Rule::in(array_keys(Empresa::TIPOS_CADASTRO))],
            'titularidade' => ['required', Rule::in(array_keys(Empresa::TITULARIDADES))],
            'documento' => ['required', 'string', function ($a, $v, $falha) use ($pf) {
                if (strlen($v) !== ($pf ? 11 : 14) || ! Documento::valido($v)) {
                    $falha($pf ? 'CPF inválido. Confira os números.' : 'CNPJ inválido. Confira os números.');
                }
            }, Rule::unique('empresa', 'documento')],
            'haras' => ['required', 'string', 'max:150'],
            'razao_social' => [$pf ? 'nullable' : 'required', 'string', 'max:190'],
            'quantidade_animais' => ['required', Rule::in(Empresa::QUANTIDADES)],
            'nome' => [$pf ? 'nullable' : 'required', 'string', 'max:150'],
            'email' => ['required', 'email:filter', 'max:190'],
            'telefone' => ['required', 'string', 'max:30'],
            'senha' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'aceite' => ['accepted'],
        ] + Geografia::regras($request, '', true), [
            'tipo_propriedade.required' => 'Escolha se a propriedade é um Haras ou uma Fazenda.',
            'titularidade.required' => 'Escolha a titularidade: Pessoa Física (CPF) ou Pessoa Jurídica (CNPJ).',
            'documento.required' => $pf ? 'Informe o CPF do proprietário.' : 'Informe o CNPJ.',
            'documento.unique' => 'Já existe um Haras / Fazenda cadastrado com esse '.($pf ? 'CPF' : 'CNPJ').'. Entre com a sua senha ou fale com o atendimento.',
            'aceite.accepted' => 'Confirme que leu e concorda com as condições do plano gratuito.',
        ] + Geografia::mensagens(), ['haras' => 'nome do Haras / Fazenda', 'razao_social' => 'razão social', 'nome' => 'seu nome', 'senha' => 'senha', 'telefone' => 'WhatsApp']);

        $email = mb_strtolower(trim($dados['email']));

        // Pessoa Física: o CPF precisa ter cadastro ativo de Profissional. Os dados vêm de lá (sem redigitação).
        $profissional = null;
        if ($pf) {
            $profissional = Profissional::porCpf($dados['documento']);
            if (! $profissional) {
                return $voltar(['documento' => 'Esse CPF ainda não tem cadastro de Profissional. Conclua primeiro o seu cadastro de Profissional para cadastrar o Haras / Fazenda.'])
                    ->with('cadastrar_profissional', route('cadastro.profissional', ['cpf' => $dados['documento']]));
            }
            if (! $profissional->ativo()) {
                return $voltar(['documento' => 'O cadastro de Profissional desse CPF não está ativo. Fale com o atendimento: atendimento@minhatropa.com.br.']);
            }
            $meu = (int) session(self::SESSAO_PROFISSIONAL) === $profissional->id;
            // Quem não acabou de fazer o cadastro confirma o e-mail que está no cadastro de Profissional.
            if (! $meu && $profissional->email && mb_strtolower($profissional->email) !== $email) {
                return $voltar(['email' => 'O e-mail não confere com o do cadastro de Profissional desse CPF ('.$profissional->emailMascarado().'). Use o mesmo e-mail ou fale com o atendimento.']);
            }
            if ($profissional->usuario_id) {
                return $voltar(['documento' => 'Esse CPF já tem acesso ao MinhaTropa. Entre com a sua senha; para incluir a propriedade na sua conta, fale com o atendimento.']);
            }
        }
        if (Usuario::where('email', $email)->exists()) {
            return $voltar(['email' => 'Já existe uma conta com esse e-mail. Entre com sua senha para acessar.']);
        }

        $nomePessoa = $pf ? $profissional->nome : $dados['nome'];
        $local = Geografia::colunas(['uf' => $dados['uf'], 'municipio_id' => $dados['municipio_id']]);

        [$usuario, $empresa] = DB::transaction(function () use ($dados, $email, $pf, $profissional, $nomePessoa, $local) {
            $usuario = Usuario::create(['nome' => $nomePessoa, 'email' => $email, 'telefone' => $dados['telefone'],
                'senha_hash' => Hash::make($dados['senha']), 'ativo' => true]);
            $empresa = Empresa::create($local + [
                'nome' => $dados['haras'], 'tipo_propriedade' => $dados['tipo_propriedade'],
                'tipo_documento' => $dados['titularidade'], 'documento' => $dados['documento'],
                'razao_social' => $pf ? $profissional->nome : $dados['razao_social'],
                'titular_profissional_id' => $profissional?->id,
                'quantidade_animais_estimada' => $dados['quantidade_animais'],
                'responsavel_nome' => $nomePessoa, 'responsavel_email' => $email, 'responsavel_telefone' => $dados['telefone'],
                'email' => $email, 'whatsapp' => $dados['telefone'],
                'situacao' => 'teste', 'origem' => 'site', 'criado_por' => $usuario->id,
            ]);
            EmpresaUsuario::create(['empresa_id' => $empresa->id, 'usuario_id' => $usuario->id, 'papel' => 'proprietario', 'status' => 'ativo']);

            if ($profissional) {
                $profissional->update(['usuario_id' => $usuario->id, 'email' => $profissional->email ?: $email,
                    'whatsapp' => $profissional->whatsapp ?: $dados['telefone']]);
                $profissional->acrescentarEspecialidade(Profissional::PROPRIETARIO);
                DB::table('empresa_profissional')->insert([
                    'empresa_id' => $empresa->id, 'profissional_id' => $profissional->id,
                    'funcao' => Profissional::ESPECIALIDADES[Profissional::PROPRIETARIO], 'funcoes' => Profissional::PROPRIETARIO, 'titular' => 1,
                    'acesso_sistema' => 1, 'papel_acesso' => 'proprietario', 'usuario_id' => $usuario->id,
                    'data_inicio' => now()->toDateString(), 'situacao' => 'ativo', 'criado_em' => now(),
                ]);
            }

            return [$usuario, $empresa];
        });

        Auth::login($usuario);
        $request->session()->regenerate();
        $request->session()->forget(self::SESSAO_PROFISSIONAL);
        session([HarasAtual::CHAVE => $empresa->id]);
        $usuario->forceFill(['ultimo_login_em' => now()])->save();
        Auditoria::registrar('haras.autocadastro', $empresa);

        $gratis = Licenca::faixas()->first();
        $limite = $gratis?->limite_max ? "até {$gratis->limite_max} animais grátis" : 'seus animais';

        return redirect()->route('painel')->with('ok', "Bem-vindo ao MinhaTropa! {$empresa->rotulo()} {$empresa->nome} cadastrado. Complete os dados e cadastre {$limite}.");
    }

    // ---------- Cadastro de Profissional pelo site (pré-requisito da Pessoa Física) ----------

    public function profissional(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('painel');
        }
        $cpf = Documento::digitos($request->query('cpf'));

        return view('site.cadastro-profissional', [
            'disponivel' => Esquema::atual(),
            'cpfInicial' => strlen($cpf) === 11 ? Documento::formatar($cpf) : null,
        ]);
    }

    public function salvarProfissional(Request $request)
    {
        abort_unless(Esquema::atual(), 503);
        if (filled($request->input('site'))) {
            return redirect()->route('inicio');
        }
        $existente = Profissional::porCpf($request->input('cpf'));
        if ($existente) {
            return redirect()->route('cadastro', ['cpf' => $existente->cpf])
                ->with('ok', 'Esse CPF já tem cadastro de Profissional. Continue o cadastro do seu Haras / Fazenda.');
        }
        $dados = CadastroProfissional::validar($request, contatoObrigatorio: true);
        $dados['whatsapp'] = $dados['whatsapp'] ?? $dados['telefone'];
        // Quem se cadastra por aqui vai ser titular de uma propriedade: a função "Proprietário" entra sempre.
        $dados['especialidades'] = array_values(array_unique(array_merge((array) ($dados['especialidades'] ?? []), [Profissional::PROPRIETARIO])));
        $profissional = CadastroProfissional::criar($dados, 'site');
        Auditoria::registrar('profissional.autocadastro', $profissional);
        session([self::SESSAO_PROFISSIONAL => $profissional->id]);

        return redirect()->route('cadastro', ['cpf' => $profissional->cpf])
            ->with('ok', 'Cadastro de Profissional concluído. Agora cadastre o seu Haras / Fazenda: seus dados já foram puxados pelo CPF.');
    }
}
