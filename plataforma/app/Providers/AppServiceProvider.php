<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Sessões ficam fora do public_html em produção (MT_ARMAZENAMENTO/sessoes): a publicação reescreve o public_html.
        $sessoes = config('session.files');
        if (config('session.driver') === 'file' && $sessoes && ! is_dir($sessoes)) {
            @mkdir($sessoes, 0750, true);
        }

        // Em desenvolvimento, acusa erro ao preencher coluna fora do $fillable.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('components.paginacao');
    }
}
