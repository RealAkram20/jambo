<?php

namespace Modules\Streaming\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Models\UserSubscription;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate watch routes by subscription access_level.
 *
 * Reads the content's `tier_required` (a tier slug, or null = free) from
 * whichever route-bound Movie/Episode is present, resolves it to the
 * tier's numeric access_level, and checks that against the user's current
 * active UserSubscription.
 *
 * Behaviour:
 *   - tier_required is null                → allow (free content)
 *   - user has no active sub                → 403 with "subscription required"
 *   - user's tier.access_level >= required  → allow
 *   - otherwise                             → 403 with "upgrade required"
 */
class TierGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $content = $this->resolveContent($request);
        if (!$content) {
            abort(404);
        }

        $requiredSlug = $content->tier_required ?? null;

        if (!$requiredSlug) {
            return $next($request);
        }

        $requiredTier = SubscriptionTier::where('slug', $requiredSlug)->first();
        if (!$requiredTier) {
            // Misconfigured tier slug on the content — treat as free rather
            // than locking users out of content we can't gate correctly.
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        // Admins always pass — they curate the catalog and need to
        // verify playback of every tier without juggling test subs.
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return $next($request);
        }

        $activeSub = UserSubscription::with('tier')
            ->where('user_id', $user->id)
            ->current()
            ->orderByDesc('ends_at')
            ->first();

        $userLevel = $activeSub?->tier?->access_level ?? SubscriptionTier::ACCESS_FREE;

        if ($userLevel < $requiredTier->access_level) {
            abort(403, "This requires a {$requiredTier->name} subscription.");
        }

        // Concurrency gate — premium-gated content only. The user's
        // own tier (activeSub->tier) sets the cap; if it's null the
        // tier has no cap and we skip. We count OTHER devices so the
        // current session still plays (no self-kick).
        $cap = $activeSub?->tier?->max_concurrent_streams;
        if ($cap !== null && $cap > 0) {
            $currentSession = $request->session()->getId();
            $otherActive    = WatchHistoryItem::activeStreamCount($user->id, $currentSession);
            if ($otherActive >= $cap) {
                return redirect()->route('streams.limit');
            }
        }

        return $next($request);
    }

    private function resolveContent(Request $request): Movie|Episode|null
    {
        $movie = $request->route('movie');
        if ($movie instanceof Movie) {
            return $movie;
        }

        $episode = $request->route('episode');
        if ($episode instanceof Episode) {
            return $episode;
        }

        return null;
    }
}
