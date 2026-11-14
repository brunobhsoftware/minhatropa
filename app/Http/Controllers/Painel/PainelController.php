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
        return view('painel.haras', ['titular' => $request->attributes->get('haras')->titular]);
    }

    public function salvarHaras(Request $request)
    {
        $empresa = $request->attributes->get('haras');
        $request->merge([
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep')) ?: null,
            'area_hectares' => $request->filled('area_hectares') ? str_replace(',', '.', str_replace('.', '', (string) $request->input('area_hectares'))) : null,
        ]);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'tipo_propriedade' => ['required', Rule::in(array_keys(Empresa::TIPOS_PROPRIEDADE))],
            'razao_social' => ['nullable', 'string', 'max:190'],
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:filter', 'max:190'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:190'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'quantidade_animais_estimada' => ['nullable', Rule::in(Empresa::QUANTIDADES)],
        ] + Geografia::regras($request, '', true), Geografia::mensagens());
        $antes = $empresa->getAttributes();
        $empresa->update(Geografia::colunas($dados));
        Auditoria::registrar('haras.configurar', $empresa, $antes);

        return redirect()->route('painel.haras')->with('ok', "Dados {$empresa->doRotulo()} salvos.");
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
