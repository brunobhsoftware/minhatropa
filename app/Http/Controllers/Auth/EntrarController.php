<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\HarasAtual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Login (/entrar). A pessoa escolhe o ambiente: o painel do Haras / Fazenda ou o ambiente do profissional.
 * A conta é a mesma; muda só para onde ela vai depois de entrar. 5 senhas erradas bloqueiam por 15 minutos.
 */
class EntrarController extends Controller
{
    public function create()
    {
        return view('auth.entrar');
    }

    public function store(Request $request)
    {
        $dados = $request->validate(['email' => ['required', 'email:filter'], 'senha' => ['required', 'string'], 'ambiente' => ['nullable', 'in:haras,profissional']]);
        $voltar = ['email', 'ambiente'];
        $usuario = Usuario::where('email', mb_strtolower(trim($dados['email'])))->first();
        $erro = ['email' => 'E-mail ou senha incorretos.'];

        if (! $usuario || ! $usuario->ativo || ! $usuario->senha_hash) {
            return back()->withInput($request->only($voltar))->withErrors($erro);
        }
        if ($usuario->bloqueado_ate && $usuario->bloqueado_ate->isFuture()) {
            $min = (int) ceil(now()->diffInSeconds($usuario->bloqueado_ate) / 60);

            return back()->withInput($request->only($voltar))->withErrors(['email' => "Acesso bloqueado por excesso de tentativas. Tente de novo em {$min} min."]);
        }
        if (! Hash::check($dados['senha'], $usuario->senha_hash)) {
            $usuario->tentativas_login++;
            if ($usuario->tentativas_login >= 5) {
                $usuario->bloqueado_ate = now()->addMinutes(15);
                $usuario->tentativas_login = 0;
            }
            $usuario->save();

            return back()->withInput($request->only($voltar))->withErrors($erro);
        }

        $usuario->forceFill(['tentativas_login' => 0, 'bloqueado_ate' => null, 'ultimo_login_em' => now()])->save();
        Auth::login($usuario);
        $request->session()->regenerate();
        Auditoria::registrar('usuario.entrar', $usuario, null, ['email' => $usuario->email]);

        if (($dados['ambiente'] ?? 'haras') === 'profissional') {
            return redirect()->route('profissional.inicio');
        }
        $temHaras = HarasAtual::disponiveis($usuario)->isNotEmpty();
        if ($usuario->admin_plataforma && ! $temHaras) {
            return redirect()->route('admin.inicio');
        }
        // Sem nenhuma propriedade para abrir, o lugar da pessoa é o ambiente do profissional.
        if (! $temHaras) {
            return redirect()->route('profissional.inicio');
        }

        return redirect()->intended(route('painel'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('entrar')->with('ok', 'Você saiu da sua conta.');
    }
}
