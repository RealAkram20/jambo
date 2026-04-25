<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-toggleable maintenance mode.
 *
 * Reads `maintenance_enabled` from the settings table on every request.
 * When ON:
 *
 *   - Admins (`role:admin`) pass through unchanged — they need full
 *     access to disable the toggle and verify the site works before
 *     re-opening to users.
 *   - The auth routes (login, logout, 2FA challenge) stay open so an
 *     accidentally-logged-out admin can sign back in via the floating
 *     gear on the maintenance page.
 *   - Everyone else gets a 503 with the branded maintenance view,
 *     `Retry-After` header set when a "back by" datetime is configured.
 *
 * Distinct from `php artisan down`: that command writes
 * storage/framework/maintenance.php and blocks even admins, intended
 * for deploys. This middleware leaves admins working — intended for
 * scheduled-content windows, soft launches, and incident triage.
 */
class MaintenanceMode
{
    /**
     * URL prefixes that bypass maintenance regardless of auth state.
     * Without these, a logged-out admin can't get back in. Listed
     * verbose so future readers see exactly what's exempted.
     */
    private const BYPASS_PATHS = [
        'login',
        'logout',
        'two-factor-challenge',
        'forgot-password',
        'reset-password',
        'email/verification-notification',
        'verify-email',
        'maintenance',         // the maintenance page itself
        'storage',             // public assets (logos, posters)
        'frontend',            // theme assets
        'dashboard',           // admin theme assets
        'build',               // Vite-built bundles
        'build-frontend',      // Vite-built bundles (frontend module)
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Cheapest possible miss path: a single cached settings lookup.
        // Setting::get caches all rows in memory once per request via
        // Cache::rememberForever('settings.all'), so this is effectively
        // free after the first call in a request lifecycle.
        if (!(bool) Setting::get('maintenance_enabled')) {
            return $next($request);
        }

        // Authenticated admins pass through. We deliberately allow ANY
        // admin, not just the one who toggled the switch — multiple
        // admins working on the upgrade need to coordinate. Both
        // `admin` and `super-admin` qualify so platform owners aren't
        // locked out of their own site even if they no longer carry
        // the regular admin role.
        $user = $request->user();
        if ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'super-admin'])) {
            return $next($request);
        }

        // Auth routes + assets always open so visitors can either log
        // in (if they're an admin) or load the maintenance page styles.
        if ($this->isBypassPath($request->path())) {
            return $next($request);
        }

        return $this->renderMaintenance($request);
    }

    private function isBypassPath(string $path): bool
    {
        $path = ltrim($path, '/');
        if ($path === '') {
            return false;
        }
        foreach (self::BYPASS_PATHS as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }
        return false;
    }

    private function renderMaintenance(Request $request): Response
    {
        $message = (string) Setting::get('maintenance_message');
        $until = (string) Setting::get('maintenance_until');

        $headers = [];

        // RFC-compliant Retry-After in seconds when a "back by" target
        // is set. Caps at 7 days because anything bigger is probably a
        // bug, and most clients ignore values past that anyway.
        if ($until !== '') {
            try {
                $seconds = max(60, min(7 * 86400, (int) (strtotime($until) - time())));
                if ($seconds > 0) {
                    $headers['Retry-After'] = (string) $seconds;
                }
            } catch (\Throwable) {
                // Bad datetime in settings — fall through without the header
            }
        }

        $payload = [
            'message' => $message !== ''
                ? $message
                : "We're updating Jambo with some improvements. Be right back.",
            'until' => $until ?: null,
        ];

        // JSON clients (the watchlist toggle, push subscribe, etc.) get
        // a JSON body so their fetch handlers can show a sensible
        // "site offline" message rather than mis-parsing HTML.
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($payload + ['error' => 'maintenance'], 503, $headers);
        }

        return response()->view('maintenance', $payload, 503, $headers);
    }
}
