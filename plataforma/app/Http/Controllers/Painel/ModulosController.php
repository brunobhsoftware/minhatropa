<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\ModuloSistema;
use App\Support\Auditoria;
use App\Support\Modulos;
use App\Support\Permissao;
use Illuminate\Http\Request;

/**
 * Módulos adicionais (add-ons) da propriedade. Módulo contratado abre a ferramenta;
 * módulo não contratado abre a tela explicativa com os benefícios e a opção de solicitar a ativação.
 */
class ModulosController extends Controller
{
    /** Área (RBAC) de cada módulo, para saber se o papel da pessoa alcança a ferramenta. */
    private const AREA = ['equipe' => 'equipe', 'saude' => 'saude', 'manejo' => 'manejo', 'nutricao' => 'nutricao'];

    public function show(Request $request, ModuloSistema $modulo)
    {
        abort_unless($modulo->ativo, 404);
        $empresa = $request->attributes->get('haras');
        $papel = $request->attributes->get('papel');
        $ativo = Modulos::ativo($empresa, $modulo->codigo);
        if ($ativo) {
            abort_unless(Permissao::pode($papel, self::AREA[$modulo->codigo] ?? 'propriedade'), 403, 'Seu acesso a esta propriedade não inclui este módulo.');
            if ($modulo->codigo === Modulos::EQUIPE) {
                return redirect()->route('painel.profissionais');
            }
        }

        return view('painel.modulo', [
            'modulo' => $modulo,
            'ativo' => $ativo,
            'solicitado' => Modulos::solicitado($empresa, $modulo->codigo),
            'podeSolicitar' => Permissao::pode($papel, 'assinatura', true),
        ]);
    }

    public function solicitar(Request $request, ModuloSistema $modulo)
    {
        abort_unless($modulo->ativo, 404);
        $empresa = $request->attributes->get('haras');
        if (Modulos::ativo($empresa, $modulo->codigo)) {
            return redirect()->route('painel.modulos.show', $modulo)->with('ok', 'Este módulo já está ativo.');
        }
        Modulos::solicitar($empresa, $modulo, $request->user()->id);
        Auditoria::registrar('haras.modulo_solicitar', $empresa, null, ['modulo' => $modulo->nome]);

        return redirect()->route('painel.modulos.show', $modulo)->with('ok', "Pedido de ativação do módulo \"{$modulo->nome}\" enviado. Nossa equipe entra em contato para liberar.");
    }
}
