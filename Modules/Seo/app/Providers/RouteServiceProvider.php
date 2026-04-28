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
     * Registered with a deliberately bare middleware stack — NOT the
     * `web` group. Reason: this app's web group apparently includes
     * something that redirects unauthenticated requests to /login
     * (a custom force-auth middleware, possibly tier_gate fanned out
     * site-wide, or 2FA enforcement). We learned that the hard way
     * when /sitemap.xml started 302'ing Googlebot to /login.
     *
     * Crawler endpoints don't need session, CSRF, cookies, or auth —
     * they just need to run the controller and return bytes. Empty
     * middleware list ensures nothing in the global / web stack can
     * intercept the response. The route-cache invalidation on the
     * `Illuminate\Routing\Middleware\SubstituteBindings` removal is
     * fine; neither sitemap nor robots uses route model binding.
     */
    protected function mapPublicRoutes(): void
    {
        Route::middleware([])
            ->namespace($this->moduleNamespace)
            ->group(module_path('Seo', '/routes/public.php'));
    }
}
