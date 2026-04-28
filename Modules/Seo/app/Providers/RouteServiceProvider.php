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
     * Registered under the `api` middleware group instead of `web`
     * because this app's `web` group quietly redirects unauthenticated
     * requests to /login (we never managed to pin down which middleware
     * does it — possibly an undocumented fork of MaintenanceMode or a
     * leftover from the auth-related additions). The 1.5.3 attempt to
     * use `Route::middleware([])` did not strip the redirect, suggesting
     * something downstream still forces the web stack on bare routes.
     *
     * The `api` group is known to have only ThrottleRequests +
     * localization + SubstituteBindings — none of those redirect, none
     * touch sessions, none enforce auth. Crawler endpoints need exactly
     * none of the things `web` provides anyway (no CSRF on GET, no
     * sessions in a public XML response, no cookies).
     *
     * The throttle rate-limit is api default (60/min) which is way more
     * than any real crawler will request — Googlebot averages a request
     * every few seconds at most.
     */
    protected function mapPublicRoutes(): void
    {
        Route::middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Seo', '/routes/public.php'));
    }
}
