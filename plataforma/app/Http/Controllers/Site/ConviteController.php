<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Profissional;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\Esquema;
use App\Support\Sociedade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Convite do sócio de um animal. O link (com o token do lançamento) abre um cadastro simples:
 * o CPF e o nome já vêm preenchidos e a pessoa só informa e-mail, WhatsApp e senha. Depois disso ela entra
 * no painel profissional e vê o animal em sociedade. Sócio em CNPJ é levado ao cadastro do Haras / Fazenda.
 */
class ConviteController extends Controller
{
    public function mostrar(Request $request, string $token)
    {
        $convite = $this->convite($token);
        $cpf = strlen($convite->documento) === 11;
        $profissional = $cpf ? Profissional::porCpf($convite->documento) : null;
        $propriedade = $cpf ? null : Empresa::naoExcluidas()->where('documento', $convite->documento)->first();

        return view('site.convite-socio', [
            'convite' => $convite,
            'cpf' => $cpf,
            // já tem como entrar: profissional com conta, ou Haras / Fazenda cadastrado com esse CNPJ
            'jaCadastrado' => $cpf ? (bool) $profissional?->usuario_id : (bool) $propriedade,
            'nome' => $profissional?->nome ?? $propriedade?->nome ?? $convite->nome,
            'emailMascarado' => $profissional && ! $profissional->usuario_id ? $profissional->emailMascarado() : null,
        ]);
    }

    public function aceitar(Request $request, string $token)
    {
        $convite = $this->convite($token);
        abort_unless(strlen($convite->documento) === 11, 404);
        if (filled($request->input('site'))) {
            return redirect()->route('inicio');
        }
        $voltar = fn (array $erros) => back()->withInput($request->except('senha', 'senha_confirmation'))->withErrors($erros);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:filter', 'max:190'],
            'telefone' => ['required', 'string', 'max:30'],
            'senha' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['nome' => 'nome completo', 'telefone' => 'WhatsApp', 'senha' => 'senha']);
        $email = mb_strtolower(trim($dados['email']));

        $profissional = Profissional::porCpf($convite->documento);
        if ($profissional?->usuario_id) {
            return redirect()->route('entrar', ['ambiente' => 'profissional'])->with('ok', 'Esse CPF já tem cadastro. Entre com o seu e-mail e a sua senha.');
        }
        if ($profissional && ! $profissional->ativo()) {
            return $voltar(['nome' => 'O cadastro desse CPF não está ativo. Fale com o atendimento: atendimento@minhatropa.com.br.']);
        }
        // CPF já lançado por alguém com e-mail: quem ativa precisa usar o mesmo e-mail.
        if ($profissional && $profissional->email && mb_strtolower($profissional->email) !== $email) {
            return $voltar(['email' => 'Esse CPF já está cadastrado com outro e-mail ('.$profissional->emailMascarado().'). Use o mesmo e-mail.']);
        }
        if (Usuario::where('email', $email)->exists()) {
            return $voltar(['email' => 'Já existe uma conta com esse e-mail. Entre com a sua senha.']);
        }

        [$usuario, $profissional] = DB::transaction(function () use ($dados, $email, $convite, $profissional) {
            $profissional ??= Profissional::create(['nome' => $dados['nome'], 'cpf' => $convite->documento, 'email' => $email,
                'telefone' => $dados['telefone'], 'whatsapp' => $dados['telefone'], 'situacao' => 'ativo', 'origem' => 'site']);
            $usuario = Usuario::create(['nome' => $profissional->nome, 'email' => $email, 'telefone' => $dados['telefone'],
                'senha_hash' => Hash::make($dados['senha']), 'ativo' => true]);
            $profissional->update(['usuario_id' => $usuario->id, 'email' => $profissional->email ?: $email,
                'whatsapp' => $profissional->whatsapp ?: $dados['telefone']]);

            return [$usuario, $profissional];
        });
        Auth::login($usuario);
        $request->session()->regenerate();
        $usuario->forceFill(['ultimo_login_em' => now()])->save();
        Auditoria::registrar('profissional.convite_socio', $profissional, null, ['animal' => $convite->animal, 'propriedade' => $convite->propriedade]);

        return redirect()->route('profissional.inicio')->with('ok', "Cadastro concluído. Você é sócio(a) de {$convite->animal} (".Sociedade::pct($convite->percentual).") em {$convite->propriedade}.");
    }

    private function convite(string $token): object
    {
        abort_unless(Esquema::atual() && Sociedade::disponivel() && strlen($token) === 40, 404);
        $convite = DB::table('animal_socio as s')->join('animal as a', 'a.id', '=', 's.animal_id')->join('empresa as e', 'e.id', '=', 'a.empresa_id')
            ->where('s.convite_token', $token)->whereNull('a.excluido_em')
            ->first(['s.documento', 's.nome', 's.percentual', 'a.nome as animal', 'e.nome as propriedade']);
        abort_unless($convite, 404);

        return $convite;
    }
}
