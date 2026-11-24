<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

// ---------- Site público ----------
Route::get('/', [SiteController::class, 'inicio'])->name('inicio');
Route::post('/contato', [SiteController::class, 'contato'])->name('contato')->middleware('throttle:5,10,contato');
Route::get('/cadastro', [\App\Http\Controllers\Site\CadastroController::class, 'create'])->name('cadastro');
Route::post('/cadastro', [\App\Http\Controllers\Site\CadastroController::class, 'store'])->name('cadastro.enviar')->middleware('throttle:10,10,cadastro');
Route::post('/cadastro/consultar-cpf', [\App\Http\Controllers\Site\CadastroController::class, 'consultarCpf'])->name('cadastro.consultar-cpf')->middleware('throttle:12,1,cadastro-cpf');
Route::post('/cadastro/verificar-sufixo', [\App\Http\Controllers\Site\CadastroController::class, 'verificarSufixo'])->name('cadastro.verificar-sufixo')->middleware('throttle:12,1,cadastro-sufixo');
Route::get('/cadastro/profissional', [\App\Http\Controllers\Site\CadastroController::class, 'profissional'])->name('cadastro.profissional');
Route::post('/cadastro/profissional', [\App\Http\Controllers\Site\CadastroController::class, 'salvarProfissional'])->name('cadastro.profissional.enviar')->middleware('throttle:5,10,cadastro-profissional');
// Municípios de uma UF (IBGE) para o seletor em cascata UF → Município
Route::get('/geo/municipios/{uf}', \App\Http\Controllers\Geo\MunicipiosController::class)->name('geo.municipios')->where('uf', '[A-Za-z]{2}');

// Convite do sócio de um animal: cadastro simples pelo link
Route::get('/convite/{token}', [\App\Http\Controllers\Site\ConviteController::class, 'mostrar'])->name('convite.socio')->where('token', '[A-Za-z0-9]{40}');
Route::post('/convite/{token}', [\App\Http\Controllers\Site\ConviteController::class, 'aceitar'])->name('convite.socio.aceitar')->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:10,10,convite');

// Página pública de cada Haras / Fazenda e as imagens dela (logomarca, capa, marca)
Route::get('/h/{slug}', [\App\Http\Controllers\Site\PropriedadeController::class, 'pagina'])->name('propriedade.pagina')->where('slug', '[a-z0-9][a-z0-9\-]*');
Route::get('/midia/p/{uuid}/{tipo}', [\App\Http\Controllers\Site\PropriedadeController::class, 'imagem'])->name('midia.propriedade')->whereUuid('uuid')->whereIn('tipo', ['logo', 'capa', 'marca']);

// Classificados do site: animais à venda que estão no ar
Route::get('/classificados', [\App\Http\Controllers\Site\ClassificadosController::class, 'index'])->name('classificados');
Route::get('/classificados/{uuid}', [\App\Http\Controllers\Site\ClassificadosController::class, 'show'])->name('classificados.ver')->whereUuid('uuid');
Route::get('/classificados/{uuid}/foto/{posicao}', [\App\Http\Controllers\Site\ClassificadosController::class, 'foto'])->name('classificados.foto')->whereUuid('uuid')->whereIn('posicao', ['1', '2', '3']);

// ---------- Área do Haras ----------
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [\App\Http\Controllers\Auth\EntrarController::class, 'create'])->name('entrar');
    Route::post('/entrar', [\App\Http\Controllers\Auth\EntrarController::class, 'store'])->name('entrar.enviar')->middleware('throttle:10,1,entrar');
});
Route::post('/sair', [\App\Http\Controllers\Auth\EntrarController::class, 'destroy'])->name('sair')->middleware('auth');
Route::middleware('auth')->prefix('painel')->name('painel.')->group(function () {
    Route::get('/escolher', [\App\Http\Controllers\Painel\PainelController::class, 'escolher'])->name('escolher');
    Route::post('/trocar/{empresa}', [\App\Http\Controllers\Painel\PainelController::class, 'trocar'])->name('trocar');
});
// ---------- Ambiente do profissional ----------
Route::middleware('auth')->prefix('profissional')->name('profissional.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Profissional\AreaController::class, 'inicio'])->name('inicio');
    Route::get('/dados', [\App\Http\Controllers\Profissional\AreaController::class, 'dados'])->name('dados');
    Route::put('/dados', [\App\Http\Controllers\Profissional\AreaController::class, 'salvar'])->name('dados.salvar');
});

Route::middleware(['auth', 'haras'])->prefix('painel')->group(function () {
    $p = '\App\Http\Controllers\Painel';
    Route::get('/', [$p.'\PainelController', 'inicio'])->name('painel');
    Route::name('painel.')->group(function () use ($p) {
        // Dados da propriedade
        Route::middleware('permissao:propriedade')->group(function () use ($p) {
            Route::get('/haras', [$p.'\PainelController', 'haras'])->name('haras');
            Route::put('/haras', [$p.'\PainelController', 'salvarHaras'])->name('haras.salvar');
            Route::get('/haras-buscar-sufixo', [$p.'\PainelController', 'buscarSufixo'])->name('haras.buscar-sufixo');
            Route::post('/haras-conferir-sufixo', [$p.'\PainelController', 'conferirSufixo'])->name('haras.conferir-sufixo');
        });

        // Assinatura: faixa de plantel e módulos (só o proprietário)
        Route::middleware('permissao:assinatura')->group(function () use ($p) {
            Route::get('/plano', [$p.'\PainelController', 'plano'])->name('plano');
            Route::post('/plano/solicitar', [$p.'\PainelController', 'solicitarFaixa'])->name('plano.solicitar');
            Route::post('/modulos/{modulo}/solicitar', [$p.'\ModulosController', 'solicitar'])->name('modulos.solicitar');
        });
        Route::get('/licenca', fn () => redirect()->route('painel.plano'))->name('licenca');
        // Tela do módulo: aberta a quem acessa a propriedade (ativo = ferramenta; não contratado = benefícios)
        Route::get('/modulos/{modulo}', [$p.'\ModulosController', 'show'])->name('modulos.show');

        Route::middleware('permissao:animais')->group(function () use ($p) {
            // Ocorrências do plantel: nascimentos, cobrições, mortes e transferências num só lugar
            Route::get('/ocorrencias', [$p.'\PainelController', 'ocorrencias'])->name('ocorrencias');
            // Consulta cross-Haras (qualquer animal da plataforma)
            Route::get('/consulta', [$p.'\ConsultaController', 'index'])->name('consulta');
            Route::get('/consulta/{animal}', [$p.'\ConsultaController', 'show'])->name('consulta.show');
            Route::get('/animais', [$p.'\AnimaisController', 'index'])->name('animais');
            // Importar / exportar a tropa por planilha (caminhos com hífen para não cair no curinga /animais/{animal})
            Route::get('/animais-importar', [$p.'\AnimaisController', 'importar'])->name('animais.importar');
            Route::get('/animais-exportar', [$p.'\AnimaisController', 'exportar'])->name('animais.exportar');
            Route::post('/animais-importar', [$p.'\AnimaisController', 'importarProcessar'])->name('animais.importar.processar');
            Route::get('/animais/novo', [$p.'\AnimaisController', 'create'])->name('animais.novo');
            Route::post('/animais', [$p.'\AnimaisController', 'store'])->name('animais.criar');
            Route::get('/animais/{animal}', [$p.'\AnimaisController', 'show'])->name('animais.show');
            Route::get('/animais/{animal}/editar', [$p.'\AnimaisController', 'edit'])->name('animais.editar');
            Route::get('/animais/{animal}/foto/{posicao}', [$p.'\AnimaisController', 'foto'])->name('animais.foto')->whereIn('posicao', ['1', '2', '3']);
            Route::put('/animais/{animal}', [$p.'\AnimaisController', 'update'])->name('animais.salvar');
            Route::put('/animais/{animal}/morte', [$p.'\AnimaisController', 'morte'])->name('animais.morte');
            // Exclusão (lógica) de animal: uma ficha, ou vários de uma vez (ex.: importação no Haras errado)
            Route::delete('/animais/{animal}', [$p.'\AnimaisController', 'destroy'])->name('animais.excluir');
            Route::post('/animais-excluir', [$p.'\AnimaisController', 'excluirVarios'])->name('animais.excluir-varios');
            Route::get('/animais-buscar-genitor', [$p.'\AnimaisController', 'buscarGenitor'])->name('animais.buscar-genitor');
            Route::get('/animais-buscar-socio', [$p.'\AnimaisController', 'buscarSocio'])->name('animais.buscar-socio');
            Route::get('/animais-proximo-numero', [$p.'\AnimaisController', 'proximoNumero'])->name('animais.proximo-numero');
            Route::post('/animais-cadastro-rapido', [$p.'\AnimaisController', 'cadastroRapido'])->name('animais.cadastro-rapido');
            Route::get('/animais-buscar-haras', [$p.'\AnimaisController', 'buscarHaras'])->name('animais.buscar-haras');
        });

        Route::middleware('permissao:reproducao')->group(function () use ($p) {
            Route::get('/cobricoes', [$p.'\CobricoesController', 'index'])->name('cobricoes');
            Route::get('/cobricoes/novo', [$p.'\CobricoesController', 'create'])->name('cobricoes.novo');
            Route::post('/cobricoes', [$p.'\CobricoesController', 'store'])->name('cobricoes.criar');
            Route::get('/cobricoes/{cobricao}', [$p.'\CobricoesController', 'show'])->name('cobricoes.show');
            Route::get('/cobricoes/{cobricao}/editar', [$p.'\CobricoesController', 'edit'])->name('cobricoes.editar');
            Route::put('/cobricoes/{cobricao}', [$p.'\CobricoesController', 'update'])->name('cobricoes.salvar');

            Route::get('/nascimentos', [$p.'\NascimentosController', 'index'])->name('nascimentos');
            Route::get('/nascimentos/novo', [$p.'\NascimentosController', 'create'])->name('nascimentos.novo');
            Route::post('/nascimentos', [$p.'\NascimentosController', 'store'])->name('nascimentos.criar');
            Route::get('/nascimentos/{nascimento}', [$p.'\NascimentosController', 'show'])->name('nascimentos.show');
        });

        Route::middleware('permissao:transferencias')->group(function () use ($p) {
            Route::get('/transferencias', [$p.'\TransferenciasController', 'index'])->name('transferencias');
            Route::get('/transferencias/novo', [$p.'\TransferenciasController', 'create'])->name('transferencias.novo');
            Route::post('/transferencias', [$p.'\TransferenciasController', 'store'])->name('transferencias.criar');
            Route::get('/transferencias/{transferencia}', [$p.'\TransferenciasController', 'show'])->name('transferencias.show');
            Route::put('/transferencias/{transferencia}/responder', [$p.'\TransferenciasController', 'responder'])->name('transferencias.responder');
            Route::get('/transferencias-buscar-haras', [$p.'\TransferenciasController', 'buscarHaras'])->name('transferencias.buscar-haras');
        });

        // Agenda e rotinas
        Route::get('/agenda', [$p.'\AgendaController', 'index'])->name('agenda');
        Route::get('/agenda/hoje', [$p.'\AgendaController', 'hoje'])->name('agenda.hoje');
        Route::post('/agenda/{execucao}/registrar', [$p.'\AgendaController', 'registrar'])->name('agenda.registrar');
        Route::get('/rotinas', [$p.'\AgendaController', 'rotinas'])->name('rotinas');
        Route::get('/rotinas/nova', [$p.'\AgendaController', 'criarRotina'])->name('rotinas.nova');
        Route::post('/rotinas', [$p.'\AgendaController', 'salvarRotina'])->name('rotinas.salvar');
        Route::get('/rotinas/{rotina}/editar', [$p.'\AgendaController', 'editarRotina'])->name('rotinas.editar');
        Route::put('/rotinas/{rotina}', [$p.'\AgendaController', 'atualizarRotina'])->name('rotinas.atualizar');
        Route::delete('/rotinas/{rotina}', [$p.'\AgendaController', 'excluirRotina'])->name('rotinas.excluir');

        // Clínica veterinária
        Route::get('/clinica', [$p.'\ClinicaController', 'index'])->name('clinica');
        Route::get('/clinica/novo', [$p.'\ClinicaController', 'criar'])->name('clinica.novo');
        Route::post('/clinica', [$p.'\ClinicaController', 'salvar'])->name('clinica.salvar');
        Route::get('/clinica/{evento}', [$p.'\ClinicaController', 'ver'])->name('clinica.ver');
        Route::get('/clinica/{evento}/editar', [$p.'\ClinicaController', 'editar'])->name('clinica.editar');
        Route::put('/clinica/{evento}', [$p.'\ClinicaController', 'atualizar'])->name('clinica.atualizar');
        Route::delete('/clinica/{evento}', [$p.'\ClinicaController', 'excluir'])->name('clinica.excluir');

        // Controle de estoque (módulo nutrição)
        Route::middleware('permissao:nutricao')->group(function () use ($p) {
            Route::get('/estoque', [$p.'\EstoqueController', 'index'])->name('estoque');
            Route::get('/estoque/novo', [$p.'\EstoqueController', 'criar'])->name('estoque.novo');
            Route::post('/estoque', [$p.'\EstoqueController', 'salvar'])->name('estoque.salvar');
            Route::get('/estoque/categorias', [$p.'\EstoqueController', 'categorias'])->name('estoque.categorias');
            Route::post('/estoque/categorias', [$p.'\EstoqueController', 'salvarCategoria'])->name('estoque.categorias.salvar');
            Route::delete('/estoque/categorias/{categoria}', [$p.'\EstoqueController', 'excluirCategoria'])->name('estoque.categorias.excluir');
            Route::get('/estoque/{item}', [$p.'\EstoqueController', 'ver'])->name('estoque.ver');
            Route::get('/estoque/{item}/editar', [$p.'\EstoqueController', 'editar'])->name('estoque.editar');
            Route::put('/estoque/{item}', [$p.'\EstoqueController', 'atualizar'])->name('estoque.atualizar');
            Route::post('/estoque/{item}/movimentar', [$p.'\EstoqueController', 'movimentar'])->name('estoque.movimentar');
            Route::delete('/estoque/{item}', [$p.'\EstoqueController', 'excluir'])->name('estoque.excluir');
        });

        // Comércio → Classificados: animais à venda (preço e período); aparecem na página pública /classificados
        Route::middleware('permissao:transferencias')->group(function () use ($p) {
            Route::get('/classificados', [$p.'\ClassificadosController', 'index'])->name('classificados');
            Route::get('/classificados/novo', [$p.'\ClassificadosController', 'create'])->name('classificados.novo');
            Route::post('/classificados', [$p.'\ClassificadosController', 'store'])->name('classificados.criar');
            Route::get('/classificados/{classificado}/editar', [$p.'\ClassificadosController', 'edit'])->name('classificados.editar');
            Route::put('/classificados/{classificado}', [$p.'\ClassificadosController', 'update'])->name('classificados.salvar');
            Route::put('/classificados/{classificado}/encerrar', [$p.'\ClassificadosController', 'encerrar'])->name('classificados.encerrar');
        });

        // Financeiro (receitas e despesas)
        Route::get('/financeiro', [$p.'\FinanceiroController', 'index'])->name('financeiro');
        Route::get('/financeiro/novo', [$p.'\FinanceiroController', 'criar'])->name('financeiro.novo');
        Route::post('/financeiro', [$p.'\FinanceiroController', 'salvar'])->name('financeiro.salvar');
        Route::get('/financeiro/{lancamento}', [$p.'\FinanceiroController', 'ver'])->name('financeiro.ver');
        Route::get('/financeiro/{lancamento}/editar', [$p.'\FinanceiroController', 'editar'])->name('financeiro.editar');
        Route::put('/financeiro/{lancamento}', [$p.'\FinanceiroController', 'atualizar'])->name('financeiro.atualizar');
        Route::delete('/financeiro/{lancamento}', [$p.'\FinanceiroController', 'excluir'])->name('financeiro.excluir');

        // Equipe: vínculo de profissionais pelo CPF (base global) e acesso externo (módulo de equipe)
        Route::middleware('permissao:equipe')->group(function () use ($p) {
            Route::get('/profissionais', [$p.'\ProfissionaisController', 'index'])->name('profissionais');
            Route::get('/profissionais/novo', [$p.'\ProfissionaisController', 'create'])->name('profissionais.novo');
            Route::post('/profissionais', [$p.'\ProfissionaisController', 'store'])->name('profissionais.criar');
            Route::put('/profissionais/{vinculo}', [$p.'\ProfissionaisController', 'editar'])->name('profissionais.editar')->whereNumber('vinculo');
            Route::put('/profissionais/{vinculo}/acesso', [$p.'\ProfissionaisController', 'acesso'])->name('profissionais.acesso')->whereNumber('vinculo');
        });
    });
});

// ---------- Administração da plataforma (equipe BH Software) ----------
Route::prefix('admin')->name('admin.')->group(function () {
    // Instalação inicial: só funciona enquanto não existe administrador com senha.
    Route::get('/instalar', [Admin\InstalacaoController::class, 'create'])->name('instalar');
    Route::post('/instalar', [Admin\InstalacaoController::class, 'store'])->name('instalar.executar')->middleware('throttle:5,10,instalar');

    Route::middleware('guest')->group(function () {
        Route::get('/entrar', [Admin\AutenticacaoController::class, 'create'])->name('entrar');
        Route::post('/entrar', [Admin\AutenticacaoController::class, 'store'])->name('entrar.enviar')->middleware('throttle:10,1,admin-entrar');
    });
    Route::post('/sair', [Admin\AutenticacaoController::class, 'destroy'])->name('sair')->middleware('auth');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [Admin\AdminController::class, 'inicio'])->name('inicio');

        // Telas que usam as tabelas mais novas (até o V007): sem elas, mandam aplicar as atualizações do banco.
        Route::middleware('banco-atual')->group(function () {
            Route::get('/haras', [Admin\HarasController::class, 'index'])->name('haras');
            Route::get('/haras/novo', [Admin\HarasController::class, 'create'])->name('haras.novo');
            Route::post('/haras', [Admin\HarasController::class, 'store'])->name('haras.criar');
            Route::get('/haras/{empresa}', [Admin\HarasController::class, 'show'])->name('haras.show');
            Route::get('/haras/{empresa}/editar', [Admin\HarasController::class, 'edit'])->name('haras.editar');
            Route::put('/haras/{empresa}', [Admin\HarasController::class, 'update'])->name('haras.salvar');
            Route::put('/haras/{empresa}/faixa', [Admin\HarasController::class, 'faixa'])->name('haras.faixa');
            Route::put('/haras/{empresa}/modulos/{modulo}', [Admin\HarasController::class, 'modulo'])->name('haras.modulo');
            Route::put('/haras/{empresa}/cortesia', [Admin\HarasController::class, 'cortesia'])->name('haras.cortesia');
            Route::get('/haras-consultar-cpf', [Admin\HarasController::class, 'consultarCpf'])->name('haras.consultar-cpf');
            Route::put('/haras/{empresa}/situacao', [Admin\HarasController::class, 'situacao'])->name('haras.situacao');
            Route::post('/haras/{empresa}/usuarios', [Admin\HarasController::class, 'vincularUsuario'])->name('haras.usuarios.vincular');
            Route::put('/haras/{empresa}/usuarios/{vinculo}', [Admin\HarasController::class, 'alterarVinculo'])->name('haras.usuarios.alterar')->whereNumber('vinculo');
            Route::post('/haras/{empresa}/marca', [Admin\HarasController::class, 'uploadMarca'])->name('haras.marca');

            Route::get('/profissionais', [Admin\ProfissionaisController::class, 'index'])->name('profissionais');
            Route::get('/profissionais/novo', [Admin\ProfissionaisController::class, 'create'])->name('profissionais.novo');
            Route::post('/profissionais', [Admin\ProfissionaisController::class, 'store'])->name('profissionais.criar');
            Route::get('/profissionais/{profissional}', [Admin\ProfissionaisController::class, 'show'])->name('profissionais.show');
            Route::get('/profissionais/{profissional}/editar', [Admin\ProfissionaisController::class, 'edit'])->name('profissionais.editar');
            Route::put('/profissionais/{profissional}', [Admin\ProfissionaisController::class, 'update'])->name('profissionais.salvar');
            Route::put('/profissionais/{profissional}/situacao', [Admin\ProfissionaisController::class, 'situacao'])->name('profissionais.situacao');
            Route::get('/profissionais-buscar', [Admin\ProfissionaisController::class, 'buscar'])->name('profissionais.buscar');

            Route::get('/planos', [Admin\PlanosController::class, 'index'])->name('planos');
            Route::post('/planos/faixas', [Admin\PlanosController::class, 'criarFaixa'])->name('planos.faixas.criar');
            Route::put('/planos/faixas/{faixa}', [Admin\PlanosController::class, 'salvarFaixa'])->name('planos.faixas.salvar')->whereNumber('faixa');
            Route::put('/planos/modulos/{modulo}', [Admin\PlanosController::class, 'salvarModulo'])->name('planos.modulos.salvar');
        });

        Route::get('/usuarios', [Admin\UsuariosController::class, 'index'])->name('usuarios');
        Route::post('/usuarios', [Admin\UsuariosController::class, 'store'])->name('usuarios.criar');
        Route::put('/usuarios/{usuario}', [Admin\UsuariosController::class, 'update'])->name('usuarios.salvar');
        Route::put('/usuarios/{usuario}/senha', [Admin\UsuariosController::class, 'definirSenha'])->name('usuarios.senha');
        Route::get('/minha-senha', [Admin\UsuariosController::class, 'minhaSenha'])->name('minha-senha');
        Route::put('/minha-senha', [Admin\UsuariosController::class, 'salvarMinhaSenha'])->name('minha-senha.salvar');

        Route::get('/contatos', [Admin\ContatosController::class, 'index'])->name('contatos');
        Route::post('/contatos/{contato}/lido', [Admin\ContatosController::class, 'lido'])->name('contatos.lido')->whereNumber('contato');

        Route::get('/banco', [Admin\BancoController::class, 'index'])->name('banco');
        Route::post('/banco', [Admin\BancoController::class, 'aplicar'])->name('banco.aplicar')->middleware('throttle:30,10,admin-banco');

        Route::get('/sufixos', [Admin\SufixosController::class, 'index'])->name('sufixos');
        Route::post('/sufixos/importar', [Admin\SufixosController::class, 'importar'])->name('sufixos.importar');

        Route::get('/auditoria', [Admin\AdminController::class, 'auditoria'])->name('auditoria');

        // Mercado Pago (requer V015)
        Route::get('/mercadopago', [Admin\AdminController::class, 'mercadoPago'])->name('mercadopago');
        Route::post('/mercadopago', [Admin\AdminController::class, 'salvarMercadoPago'])->name('mercadopago.salvar');
        Route::post('/mercadopago/teste', [Admin\AdminController::class, 'testarPagamento'])->name('mercadopago.teste');
        Route::get('/mercadopago/teste/retorno', [Admin\AdminController::class, 'retornoTeste'])->name('mercadopago.teste.retorno');
        Route::post('/mercadopago/verificar', [Admin\AdminController::class, 'verificarTokens'])->name('mercadopago.verificar');

        // E-mails enviados (requer V015)
        Route::get('/emails', [Admin\AdminController::class, 'emails'])->name('emails');
    });
});

// ---------- Webhooks ----------
Route::post('/webhooks/mercadopago', [\App\Http\Controllers\WebhookController::class, 'mercadoPago'])->name('webhooks.mercadopago');
