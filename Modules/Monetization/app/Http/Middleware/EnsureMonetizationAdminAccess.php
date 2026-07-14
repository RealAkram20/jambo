<?php

namespace Modules\Monetization\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Monetization\app\Services\MonetizationSettings;

/**
 * Visibility valve on top of the role:finance|super-admin gate.
 *
 * The platform owner can flip monetization.finance_can_view off to
 * restrict the ENTIRE monetization back office (partner wallets,
 * statements, withdrawal queue) to super-admins only — finance staff
 * then lose even read access. Super-admins always pass.
 */
class EnsureMonetizationAdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->hasRole('super-admin')) {
            return $next($request);
        }

        if ($user && $user->hasRole('finance') && MonetizationSettings::financeCanView()) {
            return $next($request);
        }

        abort(403, 'Monetization is restricted to the platform owner.');
    }
}
