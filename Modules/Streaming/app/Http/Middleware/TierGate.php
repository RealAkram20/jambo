<?php

namespace Modules\Streaming\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Playback\PlaybackDenial;
use Modules\Streaming\app\Services\PlaybackAuthorizer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate watch routes on the viewer's entitlement.
 *
 * The rules themselves — the "require sign-up to watch" switch, the tier
 * slug and the episode's fallback to its series, the admin bypass, the
 * access-level comparison and the concurrent-stream cap — used to live in
 * this file, in a second copy inside FrontendController, and in fragments
 * elsewhere. They now live in PlaybackAuthorizer, which /api/v1 calls too.
 * This middleware's remaining job is to turn one decision into the right
 * HTTP response, and those responses are unchanged:
 *
 *   login needed → 401 JSON, or a guest redirect to /login with intended set
 *   wrong plan   → 403 carrying the tier's name
 *   at the cap   → the device picker, with the target URL stashed for its
 *                  "Continue watching" button
 *
 * It deliberately does not ask isReleased(); it never did. Publish state is
 * enforced by StreamProxyController and the watch pages, which 404 rather
 * than 403 so an unreleased title does not advertise its tier.
 */
class TierGate
{
    public function __construct(private readonly PlaybackAuthorizer $authorizer)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $content = $this->resolveContent($request);
        if (!$content) {
            abort(404);
        }

        $decision = $this->authorizer->authorize(
            $content,
            $request->user(),
            $request->session()->getId(),
        );

        if ($decision->allowed) {
            return $next($request);
        }

        if ($decision->is(PlaybackDenial::LoginRequired)) {
            return $request->expectsJson()
                ? response()->json(['error' => 'unauthenticated'], 401)
                : redirect()->guest(route('login'));
        }

        if ($decision->needsBetterPlan()) {
            abort(403, $decision->message());
        }

        // Over the cap. Stash the URL they were trying to reach so the
        // picker can render a "Continue watching X" button once they
        // disconnect another device; read back via redirect()->intended().
        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()->route('streams.limit');
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
