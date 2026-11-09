<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaUsuario;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\Documento;
use App\Support\Esquema;
use App\Support\HarasAtual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** "Cadastrar meu Haras": o próprio Haras cria a conta pelo site (grátis até o 1º animal). */
class CadastroController extends Controller
{
    private const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    public function create()
    {
        if (auth()->check()) {
            return redirect()->route('painel');
        }

        return view('site.cadastro', ['ufs' => self::UFS, 'disponivel' => Esquema::temV002()]);
    }

    public function store(Request $request)
    {
        abort_unless(Esquema::temV002(), 503);
        if (filled($request->input('site'))) {
            return redirect()->route('inicio');
        }
        $request->merge(['documento' => Documento::digitos($request->input('documento')) ?: null]);

        $dados = $request->validate([
            'haras' => ['required', 'string', 'max:150'],
            'cidade' => ['required', 'string', 'max:100'],
            'uf' => ['required', Rule::in(self::UFS)],
            'quantidade_animais' => ['required', Rule::in(Empresa::QUANTIDADES)],
            'documento' => ['nullable', function ($a, $v, $falha) {
                if (! Documento::valido($v)) {
                    $falha('CPF ou CNPJ inválido. Confira os números ou deixe em branco.');
                }
            }],
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:filter', 'max:190'],
            'telefone' => ['required', 'string', 'max:30'],
            'senha' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'aceite' => ['accepted'],
        ], [
            'aceite.accepted' => 'Confirme que leu e concorda com as condições do período gratuito.',
        ], ['haras' => 'nome do Haras', 'senha' => 'senha']);

        $email = mb_strtolower(trim($dados['email']));
        if (Usuario::where('email', $email)->exists()) {
            return back()->withInput($request->except('senha', 'senha_confirmation'))
                ->withErrors(['email' => 'Já existe uma conta com esse e-mail. Entre com sua senha para acessar.']);
        }

        [$usuario, $empresa] = DB::transaction(function () use ($dados, $email) {
            $usuario = Usuario::create(['nome' => $dados['nome'], 'email' => $email, 'telefone' => $dados['telefone'],
                'senha_hash' => Hash::make($dados['senha']), 'ativo' => true]);
            $empresa = Empresa::create([
                'nome' => $dados['haras'], 'cidade' => $dados['cidade'], 'uf' => $dados['uf'],
                'documento' => $dados['documento'] ?? null, 'tipo_documento' => Documento::tipo($dados['documento'] ?? null),
                'quantidade_animais_estimada' => $dados['quantidade_animais'],
                'responsavel_nome' => $dados['nome'], 'responsavel_email' => $email, 'responsavel_telefone' => $dados['telefone'],
                'email' => $email, 'whatsapp' => $dados['telefone'],
                'situacao' => 'teste', 'origem' => 'site', 'criado_por' => $usuario->id,
            ]);
            EmpresaUsuario::create(['empresa_id' => $empresa->id, 'usuario_id' => $usuario->id, 'papel' => 'proprietario', 'status' => 'ativo']);

            return [$usuario, $empresa];
        });

        Auth::login($usuario);
        $request->session()->regenerate();
        session([HarasAtual::CHAVE => $empresa->id]);
        $usuario->forceFill(['ultimo_login_em' => now()])->save();
        Auditoria::registrar('haras.autocadastro', $empresa);

        return redirect()->route('painel')->with('ok', "Bem-vindo ao MinhaTropa! O {$empresa->nome} foi cadastrado. Complete os dados e cadastre seu primeiro animal grátis.");
    }
}
