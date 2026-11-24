<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Profissional;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\HarasAtual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EntrarController extends Controller
{
    public function create()
    {
        return view('auth.entrar');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'login' => ['required', 'string', 'max:200'],
            'senha' => ['required', 'string'],
        ]);

        $login = trim($dados['login']);
        $usuario = $this->encontrarUsuario($login);
        $erro = ['login' => 'CPF/e-mail ou senha incorretos.'];

        if (! $usuario || ! $usuario->ativo || ! $usuario->senha_hash) {
            return back()->withInput($request->only('login'))->withErrors($erro);
        }
        if ($usuario->bloqueado_ate && $usuario->bloqueado_ate->isFuture()) {
            $min = (int) ceil(now()->diffInSeconds($usuario->bloqueado_ate) / 60);

            return back()->withInput($request->only('login'))->withErrors(['login' => "Acesso bloqueado por excesso de tentativas. Tente de novo em {$min} min."]);
        }
        if (! Hash::check($dados['senha'], $usuario->senha_hash)) {
            $usuario->tentativas_login++;
            if ($usuario->tentativas_login >= 5) {
                $usuario->bloqueado_ate = now()->addMinutes(15);
                $usuario->tentativas_login = 0;
            }
            $usuario->save();

            return back()->withInput($request->only('login'))->withErrors($erro);
        }

        $usuario->forceFill(['tentativas_login' => 0, 'bloqueado_ate' => null, 'ultimo_login_em' => now()])->save();
        Auth::login($usuario);
        $request->session()->regenerate();
        Auditoria::registrar('usuario.entrar', $usuario, null, ['login' => $login]);

        return $this->redirecionar($usuario);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('entrar')->with('ok', 'Você saiu da sua conta.');
    }

    private function encontrarUsuario(string $login): ?Usuario
    {
        $soDigitos = preg_replace('/\D/', '', $login);

        // CPF: 11 dígitos numéricos (com ou sem máscara)
        if (strlen($soDigitos) === 11) {
            $profissional = Profissional::where('cpf', $soDigitos)->first();
            if ($profissional && $profissional->usuario_id) {
                return Usuario::find($profissional->usuario_id);
            }

            return null;
        }

        // E-mail
        return Usuario::where('email', mb_strtolower($login))->first();
    }

    private function redirecionar(Usuario $usuario)
    {
        $temHaras = HarasAtual::disponiveis($usuario)->isNotEmpty();

        if ($temHaras) {
            return redirect()->intended(route('painel'));
        }
        if ($usuario->admin_plataforma) {
            return redirect()->route('admin.inicio');
        }

        return redirect()->route('profissional.inicio');
    }
}
