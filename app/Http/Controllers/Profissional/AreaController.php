<?php

namespace App\Http\Controllers\Profissional;

use App\Http\Controllers\Controller;
use App\Models\Profissional;
use App\Support\Auditoria;
use App\Support\CadastroProfissional;
use App\Support\Documento;
use App\Support\Esquema;
use App\Support\Geografia;
use App\Support\HarasAtual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Ambiente do profissional: a área de cada pessoa física no MinhaTropa.
 * Mostra o cadastro dela (pelo CPF), as propriedades em que atua e dá entrada ao painel das que ela acessa.
 * Daqui ela também cadastra o próprio Haras / Fazenda.
 */
class AreaController extends Controller
{
    public function inicio(Request $request)
    {
        if (! Esquema::atual()) {
            return response()->view('painel.manutencao', [], 503);
        }
        $usuario = $request->user();
        $profissional = $usuario->profissional;
        $acessos = HarasAtual::disponiveis($usuario);

        // Propriedades em que a pessoa está vinculada como profissional (com ou sem acesso ao painel).
        $vinculos = $profissional ? DB::table('empresa_profissional as ep')->join('empresa as e', 'e.id', '=', 'ep.empresa_id')
            ->where('ep.profissional_id', $profissional->id)->whereNull('e.excluido_em')
            ->orderByDesc('ep.titular')->orderBy('e.nome')
            ->get(['e.id', 'e.uuid', 'e.nome', 'e.tipo_propriedade', 'e.cidade', 'e.uf', 'ep.funcao', 'ep.titular', 'ep.situacao']) : collect();

        return view('profissional.inicio', [
            'usuario' => $usuario, 'profissional' => $profissional, 'vinculos' => $vinculos, 'acessos' => $acessos,
            // Acessos que não passam pelo cadastro de profissional (ex.: dono de propriedade em CNPJ)
            'outrosAcessos' => $acessos->whereNotIn('id', $vinculos->pluck('id')),
        ]);
    }

    public function dados(Request $request)
    {
        if (! Esquema::atual()) {
            return response()->view('painel.manutencao', [], 503);
        }
        $profissional = $request->user()->profissional;

        return view('profissional.dados', [
            'usuario' => $request->user(),
            'profissional' => $profissional ?? new Profissional,
            'funcoes' => $profissional ? $profissional->especialidades() : [],
            'titular' => $profissional ? DB::table('empresa_profissional')->where('profissional_id', $profissional->id)->where('titular', 1)->exists() : false,
        ]);
    }

    public function salvar(Request $request)
    {
        abort_unless(Esquema::atual(), 503);
        $usuario = $request->user();
        $profissional = $usuario->profissional;

        if (! $profissional) {
            return $this->criar($request);
        }

        $request->merge(['cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null]);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'crmv' => ['nullable', 'string', 'max:30'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'especialidades' => ['nullable', 'array'],
            'especialidades.*' => ['string', Rule::in(array_keys(Profissional::ESPECIALIDADES))],
        ] + Geografia::regras($request, '', true), Geografia::mensagens(), ['nome' => 'nome completo']);

        $funcoes = (array) ($dados['especialidades'] ?? []);
        unset($dados['especialidades']);
        // Quem é titular de uma propriedade continua com a função de proprietário.
        if (DB::table('empresa_profissional')->where('profissional_id', $profissional->id)->where('titular', 1)->exists()) {
            $funcoes[] = Profissional::PROPRIETARIO;
        }
        $antes = $profissional->getAttributes();
        $profissional->update(Geografia::colunas($dados));
        $profissional->definirEspecialidades($funcoes);
        if ($usuario->nome !== $profissional->nome) {
            $usuario->forceFill(['nome' => $profissional->nome])->save();
        }
        Auditoria::registrar('profissional.meus_dados', $profissional, $antes);

        return redirect()->route('profissional.dados')->with('ok', 'Seus dados foram salvos.');
    }

    /** Conta que ainda não tem cadastro de Profissional (ex.: dono de propriedade em CNPJ) cria o seu pelo CPF. */
    private function criar(Request $request)
    {
        $usuario = $request->user();
        $existente = Profissional::porCpf($request->input('cpf'));
        if ($existente) {
            // CPF já na base: só liga à conta se ninguém usa e o e-mail do cadastro é o desta conta.
            if ($existente->usuario_id || ! $existente->email || mb_strtolower($existente->email) !== mb_strtolower($usuario->email)) {
                return back()->withInput()->withErrors(['cpf' => 'Esse CPF já está na base de Profissionais'.($existente->usuario_id ? ' ligado a outra conta.' : ' com outro e-mail ('.($existente->emailMascarado() ?: 'sem e-mail').').').' Fale com o atendimento.']);
            }
            $existente->update(['usuario_id' => $usuario->id]);
            Auditoria::registrar('profissional.ligar_conta', $existente);

            return redirect()->route('profissional.inicio')->with('ok', 'Seu cadastro de Profissional foi ligado a esta conta.');
        }
        $request->merge(['email' => $usuario->email, 'telefone' => $request->input('telefone') ?: $usuario->telefone]);
        $dados = CadastroProfissional::validar($request, contatoObrigatorio: true);
        $dados['whatsapp'] = $dados['whatsapp'] ?? $dados['telefone'];
        $profissional = DB::transaction(function () use ($dados, $usuario) {
            $profissional = CadastroProfissional::criar($dados, 'site', $usuario->id);
            $profissional->update(['usuario_id' => $usuario->id]);

            return $profissional;
        });
        Auditoria::registrar('profissional.autocadastro', $profissional);

        return redirect()->route('profissional.inicio')->with('ok', 'Cadastro de Profissional concluído.');
    }
}
