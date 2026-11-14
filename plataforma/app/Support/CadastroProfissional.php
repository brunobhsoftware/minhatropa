<?php

namespace App\Support;

use App\Models\Profissional;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Cadastro de pessoa física na base global de Profissionais (CPF único), usado pelo site,
 * pelo painel do Haras / Fazenda (cadastro rápido na equipe) e pela Administração.
 */
class CadastroProfissional
{
    /** Valida os dados de um profissional novo. O CPF precisa ser válido e ainda não existir na base. */
    public static function validar(Request $request, bool $contatoObrigatorio = false): array
    {
        $request->merge([
            'cpf' => Documento::digitos($request->input('cpf')) ?: null,
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null,
        ]);
        $contato = $contatoObrigatorio ? 'required' : 'nullable';

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'cpf' => ['required', 'string', 'size:11', function ($a, $v, $falha) {
                if (! Documento::valido($v)) {
                    $falha('CPF inválido. Confira os números.');
                }
            }, Rule::unique('profissional', 'cpf')],
            'email' => [$contato, 'email:filter', 'max:190'],
            'telefone' => [$contato, 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'data_nascimento' => ['nullable', 'date', 'before_or_equal:today'],
            'crmv' => ['nullable', 'string', 'max:30'],
            'especialidades' => ['nullable', 'array'],
            'especialidades.*' => ['string', Rule::in(array_keys(Profissional::ESPECIALIDADES))],
        ] + Geografia::regras($request, '', $contatoObrigatorio), [
            'cpf.unique' => 'Já existe um profissional cadastrado com esse CPF.',
            'cpf.size' => 'O CPF precisa ter 11 números.',
        ] + Geografia::mensagens(), ['telefone' => 'celular / WhatsApp', 'nome' => 'nome completo']);

        return Geografia::colunas($dados);
    }

    public static function criar(array $dados, string $origem, ?int $criadoPor = null): Profissional
    {
        $funcoes = (array) ($dados['especialidades'] ?? []);
        unset($dados['especialidades']);
        if (filled($dados['email'] ?? null)) {
            $dados['email'] = mb_strtolower(trim($dados['email']));
        }
        $profissional = Profissional::create($dados + ['situacao' => 'ativo', 'origem' => $origem, 'criado_por' => $criadoPor]);
        $profissional->definirEspecialidades($funcoes);

        return $profissional;
    }

    /** Dados do profissional para as telas internas (painel e Administração) depois da busca por CPF. */
    public static function resumo(Profissional $p): array
    {
        return [
            'id' => $p->id, 'nome' => $p->nome, 'cpf' => $p->cpfFormatado(), 'email' => $p->email, 'telefone' => $p->telefone ?: $p->whatsapp,
            'cidade_uf' => $p->cidadeUf(), 'funcoes' => $p->especialidadesTexto(), 'situacao' => $p->situacao,
        ];
    }
}
