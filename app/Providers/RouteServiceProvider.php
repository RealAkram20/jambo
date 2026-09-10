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
        // Keyed on the viewer, else the app install, else the address - with
        // the address only as a loose outer layer. East African carriers put
        // thousands of handsets behind one CGNAT address; a per-IP bucket on
        // the public catalogue would be one bucket per cell tower, and the
        // type-ahead alone sends a request per keystroke. The app sends
        // X-Device-Id on every request for exactly this.
        RateLimiter::for('api', function (Request $request) {
            $who = $request->user()?->id
                ?: $request->header('X-Device-Id')
                ?: $request->ip();

            return [
                Limit::perMinute(60)->by('api|' . $who),
                Limit::perMinute(600)->by('api-ip|' . $request->ip()),
            ];
        });

        /*
         * Starting a payment. **Below auth's rate, and it fails closed.**
         *
         * `engineering-standards`: anything that costs money is limited under
         * the auth rate and refuses rather than degrading. Every call here
         * mints a `PaymentOrder` row and a PesaPal order, so a loop does not
         * merely waste CPU — it fills the table the finance side reconciles
         * against with junk that looks like abandoned checkouts.
         *
         * Keyed on the user and never on the address alone: the CGNAT
         * reasoning from `api` applies with more force here, because throttling
         * a whole cell tower out of paying is worse than throttling it out of
         * browsing. Six a minute is generous for a human pressing Subscribe
         * and hostile to anything else.
         */
        RateLimiter::for('checkout', function (Request $request) {
            $who = $request->user()?->id ?: $request->header('X-Device-Id') ?: $request->ip();

            return [
                Limit::perMinute(6)->by('checkout|' . $who),
                Limit::perMinute(30)->by('checkout-ip|' . $request->ip()),
            ];
        });

        // Sign-in and the two-factor challenge. Keyed on email+ip, not ip
        // alone: East African carriers put thousands of handsets behind one
        // CGNAT address, so a per-ip bucket would throttle a whole cell
        // tower the moment one person fat-fingered a password. Mirrors the
        // key the browser login already uses, so the two front doors share
        // one budget rather than offering five attempts each.
        RateLimiter::for('auth', function (Request $request) {
            // Google sign-in carries neither an email nor a challenge token,
            // only a device block - without this fallback it was keyed on
            // the address alone, ten attempts per cell tower.
            $identifier = Str::lower((string) (
                $request->input('email')
                ?: $request->input('challenge_token')
                ?: $request->input('device.uuid')
                ?: ''
            ));

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
