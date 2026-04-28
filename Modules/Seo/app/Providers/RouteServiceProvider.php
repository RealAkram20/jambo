<?php

namespace Modules\Seo\app\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\Seo\app\Http\Controllers';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapPublicRoutes();
    }

    /**
     * /admin/seo/* — admin settings UI for Analytics + SEO config.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Seo', '/routes/web.php'));
    }

    /**
     * /sitemap.xml + /robots.txt — public crawler endpoints.
     * Loaded as a separate route file so the public-facing routes
     * are easy to spot vs. the auth-gated admin ones.
     */
    protected function mapPublicRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Seo', '/routes/public.php'));
    }
}
