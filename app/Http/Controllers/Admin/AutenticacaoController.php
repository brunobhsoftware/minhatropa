<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\AtualizacaoBanco;
use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** Login da Administração com bloqueio: 5 senhas erradas bloqueiam por 15 minutos. */
class AutenticacaoController extends Controller
{
    private const MAX_TENTATIVAS = 5;

    private const BLOQUEIO_MINUTOS = 15;

    public function create()
    {
        if (! AtualizacaoBanco::instalado() || ! Usuario::where('admin_plataforma', 1)->whereNotNull('senha_hash')->exists()) {
            return redirect()->route('admin.instalar');
        }

        return view('admin.entrar');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'email' => ['required', 'email:filter'],
            'senha' => ['required', 'string'],
        ]);

        $usuario = Usuario::where('email', mb_strtolower(trim($dados['email'])))->first();
        $erro = ['email' => 'E-mail ou senha incorretos.'];

        if (! $usuario || ! $usuario->ativo || ! $usuario->senha_hash) {
            return back()->withInput($request->only('email'))->withErrors($erro);
        }

        if ($usuario->bloqueado_ate && $usuario->bloqueado_ate->isFuture()) {
            $minutos = (int) ceil(now()->diffInSeconds($usuario->bloqueado_ate) / 60);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "Acesso bloqueado por excesso de tentativas. Tente de novo em {$minutos} min."]);
        }

        if (! Hash::check($dados['senha'], $usuario->senha_hash)) {
            $usuario->tentativas_login++;
            if ($usuario->tentativas_login >= self::MAX_TENTATIVAS) {
                $usuario->bloqueado_ate = now()->addMinutes(self::BLOQUEIO_MINUTOS);
                $usuario->tentativas_login = 0;
            }
            $usuario->save();

            return back()->withInput($request->only('email'))->withErrors($erro);
        }

        if (! $usuario->admin_plataforma) {
            // A área dos Haras ainda não existe: por enquanto só a equipe da plataforma entra.
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'A área dos Haras ainda está em construção. Por enquanto, só a administração da plataforma tem acesso.']);
        }

        $usuario->forceFill(['tentativas_login' => 0, 'bloqueado_ate' => null, 'ultimo_login_em' => now()])->save();

        Auth::login($usuario);
        $request->session()->regenerate();
        Auditoria::registrar('admin.entrar', $usuario, null, ['email' => $usuario->email]);

        return redirect()->intended(route('admin.inicio'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.entrar')->with('ok', 'Você saiu da Administração.');
    }
}
