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
Route::get('/cadastro/profissional', [\App\Http\Controllers\Site\CadastroController::class, 'profissional'])->name('cadastro.profissional');
Route::post('/cadastro/profissional', [\App\Http\Controllers\Site\CadastroController::class, 'salvarProfissional'])->name('cadastro.profissional.enviar')->middleware('throttle:5,10,cadastro-profissional');
// Municípios de uma UF (IBGE) para o seletor em cascata UF → Município
Route::get('/geo/municipios/{uf}', \App\Http\Controllers\Geo\MunicipiosController::class)->name('geo.municipios')->where('uf', '[A-Za-z]{2}');

// Página pública de cada Haras / Fazenda e as imagens dela (logomarca, capa, marca)
Route::get('/h/{slug}', [\App\Http\Controllers\Site\PropriedadeController::class, 'pagina'])->name('propriedade.pagina')->where('slug', '[a-z0-9][a-z0-9\-]*');
Route::get('/midia/p/{uuid}/{tipo}', [\App\Http\Controllers\Site\PropriedadeController::class, 'imagem'])->name('midia.propriedade')->whereUuid('uuid')->whereIn('tipo', ['logo', 'capa', 'marca']);

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
            Route::get('/animais', [$p.'\AnimaisController', 'index'])->name('animais');
            Route::get('/animais/novo', [$p.'\AnimaisController', 'create'])->name('animais.novo');
            Route::post('/animais', [$p.'\AnimaisController', 'store'])->name('animais.criar');
            Route::get('/animais/{animal}', [$p.'\AnimaisController', 'show'])->name('animais.show');
            Route::get('/animais/{animal}/editar', [$p.'\AnimaisController', 'edit'])->name('animais.editar');
            Route::put('/animais/{animal}', [$p.'\AnimaisController', 'update'])->name('animais.salvar');
            Route::put('/animais/{animal}/morte', [$p.'\AnimaisController', 'morte'])->name('animais.morte');
            Route::get('/animais-buscar-genitor', [$p.'\AnimaisController', 'buscarGenitor'])->name('animais.buscar-genitor');
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
        Route::post('/banco', [Admin\BancoController::class, 'aplicar'])->name('banco.aplicar')->middleware('throttle:10,10,admin-banco');

        Route::get('/auditoria', [Admin\AdminController::class, 'auditoria'])->name('auditoria');
    });
});
