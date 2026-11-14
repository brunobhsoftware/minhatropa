<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModuloSistema;
use App\Models\PlanoPlantel;
use App\Support\Auditoria;
use App\Support\Licenca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Planos e módulos: as faixas de plantel (plano base por quantidade de animais ativos) e os módulos adicionais.
 * Criar uma faixa nova (21 a 50, 51 a 100, ilimitado…) ou mudar limites e valores é feito aqui, sem alterar o código.
 */
class PlanosController extends Controller
{
    public function index()
    {
        return view('admin.planos', [
            'faixas' => PlanoPlantel::orderBy('limite_min')->orderBy('ordem')->get(),
            'usoFaixas' => DB::table('assinatura_empresa')->selectRaw('plano_plantel_id, COUNT(*) total')->groupBy('plano_plantel_id')->pluck('total', 'plano_plantel_id'),
            'pedidosFaixa' => DB::table('assinatura_empresa as a')->join('empresa as e', 'e.id', '=', 'a.empresa_id')->join('plano_plantel as p', 'p.id', '=', 'a.plano_solicitado_id')
                ->whereNotNull('a.plano_solicitado_id')->orderBy('a.solicitado_em')->get(['e.uuid', 'e.nome', 'p.nome as faixa', 'a.solicitado_em']),
            'modulos' => ModuloSistema::orderBy('ordem')->get(),
            'usoModulos' => DB::table('empresa_modulo')->where('situacao', 'ativo')->selectRaw('modulo_id, COUNT(*) total')->groupBy('modulo_id')->pluck('total', 'modulo_id'),
            'pedidosModulo' => DB::table('empresa_modulo as em')->join('empresa as e', 'e.id', '=', 'em.empresa_id')->join('modulo_sistema as m', 'm.id', '=', 'em.modulo_id')
                ->where('em.situacao', 'solicitado')->orderBy('em.solicitado_em')->get(['e.uuid', 'e.nome', 'm.nome as modulo', 'em.solicitado_em']),
        ]);
    }

    public function criarFaixa(Request $request)
    {
        $dados = $this->validarFaixa($request);
        $dados['codigo'] = $this->codigoLivre($dados['nome']);
        $dados['ordem'] = $dados['limite_min'];
        $faixa = PlanoPlantel::create($dados + ['ativo' => true]);
        Licenca::esquecerFaixas();
        Auditoria::registrar('plano.faixa_criar', $faixa);

        return back()->with('ok', "Faixa \"{$faixa->nome}\" criada ({$faixa->faixaTexto()}).");
    }

    public function salvarFaixa(Request $request, int $faixa)
    {
        $registro = PlanoPlantel::findOrFail($faixa);
        $dados = $this->validarFaixa($request) + ['ativo' => $request->boolean('ativo')];
        if (! $dados['ativo'] && DB::table('assinatura_empresa')->where('plano_plantel_id', $registro->id)->exists()) {
            return back()->withErrors(['ativo' => "A faixa \"{$registro->nome}\" está em uso por propriedades e não pode ser desativada."]);
        }
        $antes = $registro->getAttributes();
        $registro->update($dados);
        Licenca::esquecerFaixas();
        Auditoria::registrar('plano.faixa_editar', $registro, $antes);

        return back()->with('ok', "Faixa \"{$registro->nome}\" salva.");
    }

    public function salvarModulo(Request $request, ModuloSistema $modulo)
    {
        $request->merge(['valor_mensal' => $this->valor($request->input('valor_mensal'))]);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'resumo' => ['nullable', 'string', 'max:255'],
            'beneficios' => ['nullable', 'string', 'max:3000'],
            'valor_mensal' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ]) + ['ativo' => $request->boolean('ativo')];
        $antes = $modulo->getAttributes();
        $modulo->update($dados);
        Auditoria::registrar('plano.modulo_editar', $modulo, $antes);

        return back()->with('ok', "Módulo \"{$modulo->nome}\" salvo.");
    }

    private function validarFaixa(Request $request): array
    {
        $request->merge(['valor_mensal' => $this->valor($request->input('valor_mensal')), 'limite_max' => $request->filled('limite_max') ? $request->input('limite_max') : null]);

        return $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'limite_min' => ['required', 'integer', 'min:0', 'max:1000000'],
            'limite_max' => ['nullable', 'integer', 'gte:limite_min', 'max:1000000'],
            'valor_mensal' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ], ['limite_max.gte' => 'O último animal da faixa não pode ser menor que o primeiro.'], ['limite_min' => 'primeiro animal', 'limite_max' => 'último animal', 'valor_mensal' => 'valor mensal']);
    }

    /** "1.250,50" → "1250.50"; em branco = a definir (NULL). */
    private function valor($texto): ?string
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return null;
        }

        return str_contains($texto, ',') ? str_replace(',', '.', str_replace('.', '', $texto)) : $texto;
    }

    private function codigoLivre(string $nome): string
    {
        $base = Str::slug($nome, '_') ?: 'faixa';
        $codigo = $base;
        for ($i = 2; PlanoPlantel::where('codigo', $codigo)->exists(); $i++) {
            $codigo = $base.'_'.$i;
        }

        return mb_substr($codigo, 0, 40);
    }
}
