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
     *
     * Registered with empty middleware. Crawler endpoints don't need
     * sessions, CSRF, or auth — and we explicitly don't want to use
     * the `web` group (which would redirect unauthenticated requests
     * via the auth-aware middleware) OR the `api` group (which on
     * this app references a `localization` alias whose class doesn't
     * exist in the codebase, throwing a ReflectionException when
     * Laravel tries to instantiate it).
     *
     * The earlier 302→/login behavior that this provider previously
     * tried to work around with `api` was actually caused by a route
     * precedence collision with the /{username} profile route — fixed
     * in 1.5.6 by tightening that route's regex to exclude paths
     * ending in known static-file extensions. With that fix in place,
     * our routes win the match cleanly, and an empty middleware list
     * means nothing in the request pipeline can intercept.
     *
     * Global middleware (TrustProxies, TrimStrings, etc. from the
     * $middleware array in Kernel.php) still runs on every request —
     * none of those redirect.
     */
    protected function mapPublicRoutes(): void
    {
        Route::middleware([])
            ->namespace($this->moduleNamespace)
            ->group(module_path('Seo', '/routes/public.php'));
    }
}
