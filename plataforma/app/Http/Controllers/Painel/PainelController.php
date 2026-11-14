<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Empresa;
use App\Models\PlanoPlantel;
use App\Support\Auditoria;
use App\Support\Geografia;
use App\Support\HarasAtual;
use App\Support\Licenca;
use App\Support\Modulos;
use App\Support\PaginaPropriedade;
use App\Support\Permissao;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Área do Haras / Fazenda: início, dados da propriedade, troca de propriedade e plano (faixa de plantel + módulos). */
class PainelController extends Controller
{
    public function inicio(Request $request)
    {
        $empresa = $request->attributes->get('haras');

        return view('painel.inicio', $this->plantel($empresa) + [
            'ultimos' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->latest('criado_em')->limit(5)->get(),
        ]);
    }

    /**
     * Ocorrências do plantel: a entrada única para os fatos dos animais (nascimentos, cobrições, mortes e transferências).
     * Cada cartão respeita o papel da pessoa e o plano; "Ocorrências do Haras" (avisos entre pessoas) ainda vai ser construído.
     */
    public function ocorrencias(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $papel = $request->attributes->get('papel');
        $licenca = Licenca::ativa($empresa);
        $conta = fn (string $tabela) => $licenca ? DB::table($tabela)->where('empresa_id', $empresa->id)->count() : null;

        return view('painel.ocorrencias', [
            'licenca' => $licenca,
            'reproducao' => Permissao::pode($papel, 'reproducao'),
            'transferir' => Permissao::pode($papel, 'transferencias'),
            'nascimentos' => $conta('nascimento'),
            'cobricoes' => $conta('cobricao'),
            'transferencias' => $licenca ? DB::table('transferencia')->where(fn ($q) => $q->where('empresa_origem_id', $empresa->id)->orWhere('empresa_destino_id', $empresa->id))->count() : null,
            'mortes' => Animal::where('empresa_id', $empresa->id)->whereNull('excluido_em')->where('situacao', 'morto')->count(),
        ]);
    }

    public function escolher(Request $request)
    {
        $lista = HarasAtual::disponiveis($request->user());
        abort_if($lista->isEmpty(), 403);

        return view('painel.escolher', ['lista' => $lista]);
    }

    public function trocar(Request $request, Empresa $empresa)
    {
        abort_unless(HarasAtual::disponiveis($request->user())->contains('id', $empresa->id), 404);
        session([HarasAtual::CHAVE => $empresa->id]);

        return redirect()->route('painel')->with('ok', "Você está em: {$empresa->nome}.");
    }

    public function haras(Request $request)
    {
        $empresa = $request->attributes->get('haras');

        return view('painel.haras', ['titular' => $empresa->titular, 'racas' => $empresa->racas()]);
    }

    /**
     * Cadastro completo da propriedade pelo proprietário. Obrigatórios: nome, tipo e UF/município.
     * O CPF/CNPJ (identificação única) e a titularidade não mudam por aqui.
     */
    public function salvarHaras(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $request->merge([
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null,
            'corresp_cep' => preg_replace('/\D/', '', (string) $request->input('corresp_cep')) ?: null,
            'area_hectares' => $request->filled('area_hectares') ? str_replace(',', '.', str_replace('.', '', (string) $request->input('area_hectares'))) : null,
            'latitude' => $request->filled('latitude') ? str_replace(',', '.', (string) $request->input('latitude')) : null,
            'longitude' => $request->filled('longitude') ? str_replace(',', '.', (string) $request->input('longitude')) : null,
            'finalidades_criacao' => implode(',', array_intersect((array) $request->input('finalidades', []), array_keys(Empresa::FINALIDADES))) ?: null,
            'preferencias_marcha' => implode(',', array_intersect((array) $request->input('marchas', []), array_keys(Empresa::MARCHAS))) ?: null,
        ]);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'tipo_propriedade' => ['required', Rule::in(array_keys(Empresa::TIPOS_PROPRIEDADE))],
            'razao_social' => ['nullable', 'string', 'max:190'],
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'quantidade_animais_estimada' => ['nullable', Rule::in(Empresa::QUANTIDADES)],
            'email' => ['nullable', 'email:filter', 'max:190'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'responsavel_nome' => ['nullable', 'string', 'max:150'],
            'responsavel_email' => ['nullable', 'email:filter', 'max:190'],
            'responsavel_telefone' => ['nullable', 'string', 'max:30'],
            'telefone_residencial' => ['nullable', 'string', 'max:30'],
            'telefone_celular' => ['nullable', 'string', 'max:30'],
            'telefone_comercial' => ['nullable', 'string', 'max:30'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:190'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'como_chegar' => ['nullable', 'string', 'max:400'],
            'corresp_cep' => ['nullable', 'digits:8'],
            'corresp_logradouro' => ['nullable', 'string', 'max:190'],
            'corresp_numero' => ['nullable', 'string', 'max:20'],
            'corresp_complemento' => ['nullable', 'string', 'max:100'],
            'corresp_bairro' => ['nullable', 'string', 'max:100'],
            'finalidades_criacao' => ['nullable', 'string', 'max:500'],
            'preferencias_marcha' => ['nullable', 'string', 'max:300'],
            'marca_descricao' => ['nullable', 'string', 'max:500'],
            'site_url' => ['nullable', 'url:http,https', 'max:255'],
        ] + Geografia::regras($request, '', true) + Geografia::regras($request, 'corresp_') + PaginaPropriedade::regras($empresa),
            Geografia::mensagens() + Geografia::mensagens('corresp_') + PaginaPropriedade::mensagens() + ['site_url.url' => 'Informe o endereço completo do site, começando com https://.']);

        $titular = $empresa->titular;
        if ($titular) { // nome do responsável (e do proprietário PF) vem sempre do cadastro de Profissional
            $dados['responsavel_nome'] = $titular->nome;
            if ($empresa->pessoaFisica()) {
                $dados['razao_social'] = $titular->nome;
            }
        }
        $dados = PaginaPropriedade::colunas($request, $dados, $empresa);
        $antes = $empresa->getAttributes();
        $empresa->update(Geografia::colunas(Geografia::colunas($dados), 'corresp_'));
        $empresa->definirRacas((array) $request->input('racas', []));
        PaginaPropriedade::salvarImagens($request, $empresa);
        Auditoria::registrar('haras.configurar', $empresa, $antes);

        return redirect()->route('painel.haras')->with('ok', "Dados {$empresa->doRotulo()} salvos.".($empresa->paginaUrl() ? ' Página no ar: '.$empresa->paginaUrl() : ''));
    }

    /** Plano: faixa de plantel em vigor, as demais faixas e os módulos adicionais. Só o proprietário vê. */
    public function plano(Request $request)
    {
        $empresa = $request->attributes->get('haras');

        return view('painel.plano', $this->plantel($empresa) + [
            'faixas' => Licenca::faixas(),
            'solicitada' => Licenca::faixaSolicitada($empresa),
            'modulos' => Modulos::todos(),
        ]);
    }

    /** O proprietário pede o upgrade de faixa. Ainda sem cobrança on-line: a equipe do MinhaTropa libera. */
    public function solicitarFaixa(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $dados = $request->validate(['faixa' => ['required', 'integer', Rule::exists('plano_plantel', 'id')->where('ativo', 1)]]);
        $faixa = PlanoPlantel::findOrFail($dados['faixa']);
        $atual = Licenca::faixaAtual($empresa);
        if ($atual && $faixa->id === $atual->id) {
            return back()->withErrors(['faixa' => 'Essa já é a faixa em vigor.']);
        }
        Licenca::solicitar($empresa, $faixa, $request->user()->id);
        Auditoria::registrar('haras.faixa_solicitar', $empresa, null, ['faixa' => $faixa->nome, 'animais' => Licenca::animais($empresa)]);

        return redirect()->route('painel.plano')->with('ok', "Pedido de mudança para \"{$faixa->nome}\" ({$faixa->faixaTexto()}) enviado. Nossa equipe entra em contato para liberar.");
    }

    /** Números do plantel e da faixa, usados no início, no plano e no aviso de limite. */
    private function plantel(Empresa $empresa): array
    {
        return [
            'totalAnimais' => Licenca::animais($empresa),
            'faixa' => Licenca::faixaAtual($empresa),
            'limite' => Licenca::limite($empresa),
            'proxima' => Licenca::proximaFaixa($empresa),
            'licenca' => Licenca::ativa($empresa),
        ];
    }
}
