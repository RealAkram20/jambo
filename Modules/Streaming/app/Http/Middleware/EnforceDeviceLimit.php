<?php

namespace Modules\Streaming\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Subscriptions\app\Models\UserSubscription;
use Symfony\Component\HttpFoundation\Response;

/**
 * Account-level concurrent-device cap.
 *
 * Enforces `subscription_tiers.max_concurrent_streams` as a hard ceiling
 * on the number of browser sessions signed into an account at one time —
 * not just streams. Previously the cap only applied to premium
 * playback; this closes the loophole where a user could stay logged in
 * on many devices as long as only a few were streaming.
 *
 * Fires on every authenticated request in the web middleware group.
 * Skips:
 *   • unauthenticated requests — no user, no cap
 *   • admins — catalogue curators need multi-device access to QA
 *   • the picker itself + its helper endpoints — otherwise we'd
 *     redirect-loop (over cap → picker → middleware → over cap → …)
 *   • auth endpoints (login/logout/registration) — cap is enforced on
 *     the next real page request after auth, not during it
 *   • the heartbeat endpoint — stream-level kick flow is separate
 *
 * "Active session" = row in Laravel's sessions table whose user_id
 * matches and last_activity is within session.lifetime. Requires
 * SESSION_DRIVER=database; if a different driver is configured this
 * middleware short-circuits rather than crash.
 */
class EnforceDeviceLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return $next($request);
        }

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        if (config('session.driver') !== 'database') {
            return $next($request);
        }

        $activeSub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $cap = $activeSub?->tier?->max_concurrent_streams;
        if ($cap === null || $cap <= 0) {
            return $next($request);
        }

        $cutoff = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;

        $activeSessionCount = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>', $cutoff)
            ->count();

        if ($activeSessionCount > $cap) {
            // Stash the destination so the picker's Continue button can
            // send the user back after they disconnect a device. Same
            // convention TierGate uses for its premium-playback block.
            $request->session()->put('url.intended', $request->fullUrl());
            return redirect()->route('streams.limit');
        }

        return $next($request);
    }

    /**
     * Routes that must NOT run the cap check. Keeping the picker and
     * its action endpoints out of the list is what prevents the
     * redirect loop: over-cap → picker, but the picker itself is the
     * only place the user can resolve the state.
     */
    private function shouldSkip(Request $request): bool
    {
        $name = optional($request->route())->getName();

        $skipNames = [
            'streams.limit',
            'streams.boot',
            'streams.reclaim',
            'streams.continue',
            'streaming.heartbeat',
            'logout',
            'login',
            'register',
            'password.confirm',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'verification.notice',
            'verification.send',
            'verification.verify',
            'two-factor.login',
            'two-factor.login.store',
        ];

        if ($name && in_array($name, $skipNames, true)) {
            return true;
        }

        // AJAX / JSON / API — bouncing those with a 302 to a picker HTML
        // page breaks callers. Cap is re-checked on the next real page
        // navigation.
        if ($request->expectsJson() || $request->is('api/*')) {
            return true;
        }

        return false;
    }
}
