<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional authentication for public API endpoints that personalise.
 *
 * `auth:sanctum` refuses a request without a token, which is wrong for an
 * endpoint like /api/v1/home: a guest must get a full home screen, exactly as
 * on the website. But a *signed-in* viewer must get Continue Watching and
 * personal rails, and those are chosen deep inside HomeRailsService and
 * TopPicksRecommender by reading `auth()->id()`.
 *
 * Without this, a public route never resolves the bearer token — nothing
 * inspects it — so `auth()->id()` is null even when the app sent a perfectly
 * valid one, and the viewer silently gets the guest home screen. That is not
 * an error anyone would notice in a status code; it just quietly stops
 * personalising. It was caught by a test asserting Continue Watching appears.
 *
 * Switching the default guard is what makes the existing code work unchanged:
 * every `auth()->id()` call already in the rails path now reads the token
 * instead of a session cookie, and still answers null for a guest rather than
 * aborting.
 *
 * Use it only on public routes that personalise. Anything that must have a
 * viewer keeps `auth:sanctum`, which fails closed.
 */
class IdentifyApiViewer
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
