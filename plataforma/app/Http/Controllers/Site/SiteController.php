<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Contato;
use App\Support\Esquema;
use App\Support\Geografia;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function inicio()
    {
        return view('site.inicio');
    }

    /** Formulário "Quero conhecer o MinhaTropa" do site: grava em contato e aparece na Administração. */
    public function contato(Request $request)
    {
        // Campo escondido contra robôs: pessoas não preenchem.
        if (filled($request->input('site'))) {
            return redirect()->to(route('inicio').'#contato')->with('contato_ok', true);
        }

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'required_without:telefone', 'email:filter', 'max:190'],
            'telefone' => ['nullable', 'required_without:email', 'string', 'max:30'],
            'haras' => ['nullable', 'string', 'max:150'],
            'quantidade_animais' => ['nullable', 'string', 'max:30'],
            'mensagem' => ['nullable', 'string', 'max:3000'],
        ] + (Esquema::temV007() ? Geografia::regras($request) : []), [
            'email.required_without' => 'Informe um e-mail ou um telefone para o retorno.',
            'telefone.required_without' => 'Informe um telefone ou um e-mail para o retorno.',
        ] + Geografia::mensagens());
        // Localização escolhida nas listas (UF → município); o contato guarda "Município/UF".
        $local = Esquema::temV007() ? Geografia::colunas($dados) : [];
        $dados['cidade_uf'] = filled($local['cidade'] ?? null) ? $local['cidade'].'/'.$local['uf'] : ($local['uf'] ?? null);
        unset($dados['uf'], $dados['municipio_id']);

        try {
            Contato::create($dados + ['origem' => 'site', 'ip' => $request->ip()]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->to(route('inicio').'#contato')->withInput()
                ->withErrors(['contato' => 'Não conseguimos registrar seu contato agora. Tente de novo em alguns minutos.']);
        }

        return redirect()->to(route('inicio').'#contato')->with('contato_ok', true);
    }
}
