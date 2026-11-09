<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Equipe da plataforma (administradores) e troca da própria senha. */
class UsuariosController extends Controller
{
    public function index()
    {
        return view('admin.usuarios', [
            'administradores' => Usuario::where('admin_plataforma', 1)->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:filter', 'max:190'],
            'senha' => ['required', Password::min(10)->letters()->numbers()],
        ]);
        $email = mb_strtolower(trim($dados['email']));
        $usuario = Usuario::firstOrNew(['email' => $email]);
        if ($usuario->exists && $usuario->admin_plataforma) {
            return back()->withInput($request->except('senha'))->withErrors(['email' => 'Essa pessoa já é administradora.']);
        }
        $usuario->forceFill(['nome' => $dados['nome'], 'senha_hash' => Hash::make($dados['senha']), 'admin_plataforma' => true, 'ativo' => true])->save();
        Auditoria::registrar('plataforma.admin_criar', $usuario);

        return back()->with('ok', "{$usuario->nome} agora é administrador(a) da plataforma. Passe a senha provisória pessoalmente.");
    }

    public function update(Request $request, Usuario $usuario)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:filter', 'max:190', Rule::unique('usuario', 'email')->ignore($usuario->id)],
            'telefone' => ['nullable', 'string', 'max:30'],
            'ativo' => ['nullable', 'boolean'],
        ]);
        $ativo = $request->boolean('ativo');
        if ($usuario->is($request->user()) && ! $ativo) {
            return back()->withErrors(['ativo' => 'Você não pode desativar o seu próprio acesso.']);
        }
        $antes = $usuario->getAttributes();
        $usuario->update(['nome' => $dados['nome'], 'email' => mb_strtolower(trim($dados['email'])), 'telefone' => $dados['telefone'] ?? null, 'ativo' => $ativo]);
        Auditoria::registrar('usuario.editar', $usuario, $antes);

        return back()->with('ok', 'Dados de '.$usuario->nome.' salvos.');
    }

    /** Senha provisória definida pelo administrador (enquanto não há e-mail de recuperação). */
    public function definirSenha(Request $request, Usuario $usuario)
    {
        $dados = $request->validate(['senha' => ['required', Password::min(10)->letters()->numbers()]]);
        $usuario->forceFill(['senha_hash' => Hash::make($dados['senha']), 'tentativas_login' => 0, 'bloqueado_ate' => null])->save();
        Auditoria::registrar('usuario.senha_definida', $usuario, null, ['usuario' => $usuario->email]);

        return back()->with('ok', 'Senha de '.$usuario->nome.' alterada. Passe a senha pessoalmente e peça para trocar no primeiro acesso.');
    }

    public function minhaSenha()
    {
        return view('admin.minha-senha');
    }

    public function salvarMinhaSenha(Request $request)
    {
        $dados = $request->validate([
            'senha_atual' => ['required', 'current_password'],
            'senha' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);
        $request->user()->forceFill(['senha_hash' => Hash::make($dados['senha'])])->save();
        Auditoria::registrar('usuario.trocar_senha', $request->user(), null, ['usuario' => $request->user()->email]);

        return redirect()->route('admin.inicio')->with('ok', 'Sua senha foi alterada.');
    }
}
