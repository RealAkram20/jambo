<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/app';


    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Sign-in and the two-factor challenge. Keyed on email+ip, not ip
        // alone: East African carriers put thousands of handsets behind one
        // CGNAT address, so a per-ip bucket would throttle a whole cell
        // tower the moment one person fat-fingered a password. Mirrors the
        // key the browser login already uses, so the two front doors share
        // one budget rather than offering five attempts each.
        RateLimiter::for('auth', function (Request $request) {
            $identifier = Str::lower((string) $request->input('email', $request->input('challenge_token', '')));

            return [
                Limit::perMinute(10)->by($identifier . '|' . $request->ip()),
                // A loose second layer, so one address cannot walk a list of
                // addresses through the first limit unimpeded.
                Limit::perMinute(60)->by($request->ip()),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
