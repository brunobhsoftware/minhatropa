<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use App\Models\Empresa;
use App\Support\Auditoria;
use App\Support\HarasAtual;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Área do Haras: início, dados do Haras (configuração inicial), troca de Haras e licença. */
class PainelController extends Controller
{
    private const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    public function inicio(Request $request)
    {
        $empresa = $request->attributes->get('haras');

        return view('painel.inicio', [
            'totalAnimais' => Licenca::animais($empresa),
            'gratis' => Licenca::animaisGratis(),
            'licenca' => Licenca::ativa($empresa),
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

        return redirect()->route('painel')->with('ok', "Você está no {$empresa->nome}.");
    }

    public function haras(Request $request)
    {
        return view('painel.haras', ['ufs' => self::UFS]);
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
            'cidade' => ['nullable', 'string', 'max:100'],
            'uf' => ['nullable', Rule::in(self::UFS)],
            'area_hectares' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'quantidade_animais_estimada' => ['nullable', Rule::in(Empresa::QUANTIDADES)],
        ]);
        $antes = $empresa->getAttributes();
        $empresa->update($dados);
        Auditoria::registrar('haras.configurar', $empresa, $antes);

        return redirect()->route('painel.haras')->with('ok', 'Dados do Haras salvos.');
    }

    public function licenca(Request $request)
    {
        $empresa = $request->attributes->get('haras');

        return view('painel.licenca', ['totalAnimais' => Licenca::animais($empresa), 'gratis' => Licenca::animaisGratis(), 'licenca' => Licenca::ativa($empresa)]);
    }
}
