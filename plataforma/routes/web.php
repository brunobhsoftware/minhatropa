<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

// ---------- Site público ----------
Route::get('/', [SiteController::class, 'inicio'])->name('inicio');
Route::post('/contato', [SiteController::class, 'contato'])->name('contato')->middleware('throttle:5,10,contato');
Route::get('/cadastro', [\App\Http\Controllers\Site\CadastroController::class, 'create'])->name('cadastro');
Route::post('/cadastro', [\App\Http\Controllers\Site\CadastroController::class, 'store'])->name('cadastro.enviar')->middleware('throttle:5,10,cadastro');

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
Route::middleware(['auth', 'haras'])->prefix('painel')->group(function () {
    Route::get('/', [\App\Http\Controllers\Painel\PainelController::class, 'inicio'])->name('painel');
    Route::name('painel.')->group(function () {
        Route::get('/haras', [\App\Http\Controllers\Painel\PainelController::class, 'haras'])->name('haras');
        Route::put('/haras', [\App\Http\Controllers\Painel\PainelController::class, 'salvarHaras'])->name('haras.salvar');
        Route::get('/licenca', [\App\Http\Controllers\Painel\PainelController::class, 'licenca'])->name('licenca');
        Route::get('/animais', [\App\Http\Controllers\Painel\AnimaisController::class, 'index'])->name('animais');
        Route::get('/animais/novo', [\App\Http\Controllers\Painel\AnimaisController::class, 'create'])->name('animais.novo');
        Route::post('/animais', [\App\Http\Controllers\Painel\AnimaisController::class, 'store'])->name('animais.criar');
        Route::get('/animais/{animal}/editar', [\App\Http\Controllers\Painel\AnimaisController::class, 'edit'])->name('animais.editar');
        Route::put('/animais/{animal}', [\App\Http\Controllers\Painel\AnimaisController::class, 'update'])->name('animais.salvar');
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

        Route::get('/haras', [Admin\HarasController::class, 'index'])->name('haras');
        Route::get('/haras/novo', [Admin\HarasController::class, 'create'])->name('haras.novo');
        Route::post('/haras', [Admin\HarasController::class, 'store'])->name('haras.criar');
        Route::get('/haras/{empresa}', [Admin\HarasController::class, 'show'])->name('haras.show');
        Route::get('/haras/{empresa}/editar', [Admin\HarasController::class, 'edit'])->name('haras.editar');
        Route::put('/haras/{empresa}', [Admin\HarasController::class, 'update'])->name('haras.salvar');
        Route::put('/haras/{empresa}/licenca', [Admin\HarasController::class, 'licenca'])->name('haras.licenca');
        Route::put('/haras/{empresa}/situacao', [Admin\HarasController::class, 'situacao'])->name('haras.situacao');
        Route::post('/haras/{empresa}/usuarios', [Admin\HarasController::class, 'vincularUsuario'])->name('haras.usuarios.vincular');
        Route::put('/haras/{empresa}/usuarios/{vinculo}', [Admin\HarasController::class, 'alterarVinculo'])->name('haras.usuarios.alterar')->whereNumber('vinculo');
        Route::post('/haras/{empresa}/marca', [Admin\HarasController::class, 'uploadMarca'])->name('haras.marca');

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
