<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdministradorPlataforma::class,
            'haras' => \App\Http\Middleware\IdentificarHaras::class,
            'permissao' => \App\Http\Middleware\VerificarPermissao::class,
            'banco-atual' => \App\Http\Middleware\BancoAtualizado::class,
        ]);
        // Quem não está logado vai para o login da área que tentou abrir.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin*') ? route('admin.entrar') : route('entrar'));
        $middleware->redirectUsersTo(fn ($request) => $request->user()?->admin_plataforma && $request->is('admin*') ? route('admin.inicio') : route('painel'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sessão expirada (419): volta ao formulário com o que foi digitado, em vez da página "Page Expired".
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return redirect()->back()->withInput($request->except(['_token', 'senha', 'senha_confirmation', 'senha_banco']))
                ->withErrors(['sessao' => 'A página ficou muito tempo aberta e a sessão expirou. Confira os dados e envie de novo.']);
        });
    })->create();

// Em produção (Hostinger) o .env fica FORA do public_html, uma pasta acima da
// aplicação: /home/<conta>/domains/minhatropa.com.br/.env.minhatropa
// Assim as credenciais nunca ficam dentro da pasta publicada nem no Git.
$pastaExterna = dirname(__DIR__, 2);
if (is_file($pastaExterna.'/.env.minhatropa')) {
    $app->useEnvironmentPath($pastaExterna);
    $app->loadEnvironmentFrom('.env.minhatropa');
}

return $app;
