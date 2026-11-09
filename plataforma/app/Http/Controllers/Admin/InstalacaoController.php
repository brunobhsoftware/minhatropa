<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\AtualizacaoBanco;
use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Primeira instalação, sem phpMyAdmin: cria as tabelas (V001) e o administrador da plataforma.
 * Só funciona enquanto não existe administrador com senha, e exige a senha do banco de dados
 * (que só quem configurou o servidor conhece) como prova de que é o dono da instalação.
 */
class InstalacaoController extends Controller
{
    private function jaInstalado(): bool
    {
        return AtualizacaoBanco::instalado() && Usuario::where('admin_plataforma', 1)->whereNotNull('senha_hash')->exists();
    }

    public function create()
    {
        if ($this->jaInstalado()) {
            return redirect()->route('admin.entrar');
        }

        $conexao = null;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $conexao = 'Não foi possível conectar ao banco de dados. Confira o arquivo .env.minhatropa no servidor (DB_DATABASE, DB_USERNAME e DB_PASSWORD).';
        }

        return view('admin.instalar', ['erroConexao' => $conexao, 'banco' => config('database.connections.mysql.database')]);
    }

    public function store(Request $request)
    {
        abort_if($this->jaInstalado(), 404);

        $dados = $request->validate([
            'senha_banco' => ['required', 'string'],
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:filter', 'max:190'],
            'senha' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [], ['senha_banco' => 'senha do banco de dados', 'senha' => 'senha do administrador']);

        $senhaBanco = (string) config('database.connections.mysql.password');
        if ($senhaBanco === '' || ! hash_equals($senhaBanco, $dados['senha_banco'])) {
            return back()->withInput($request->only('nome', 'email'))
                ->withErrors(['senha_banco' => 'A senha do banco de dados não confere com a configurada no servidor.']);
        }

        try {
            $comandos = AtualizacaoBanco::aplicar('V001__criacao_minhatropa.sql');
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->only('nome', 'email'))
                ->withErrors(['senha_banco' => 'O banco recusou a criação das tabelas: '.mb_substr($e->getMessage(), 0, 300)]);
        }

        $email = mb_strtolower(trim($dados['email']));
        $usuario = Usuario::firstOrNew(['email' => $email]);
        $usuario->forceFill([
            'nome' => $dados['nome'],
            'senha_hash' => Hash::make($dados['senha']),
            'admin_plataforma' => true,
            'ativo' => true,
        ])->save();

        Auth::login($usuario);
        $request->session()->regenerate();
        Auditoria::registrar('plataforma.instalacao', $usuario, null, ['email' => $email, 'comandos_v001' => $comandos]);

        return redirect()->route('admin.inicio')->with('ok', 'Instalação concluída! As tabelas foram criadas e você já está dentro da Administração.');
    }
}
