<?php

namespace Modules\Streaming\app\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Services\PlaybackAuthorizer;
use Modules\Streaming\app\Services\StreamSourceResolver;

/**
 * Session-gated 302 redirect to the original video URL (Dropbox /
 * Contabo / wherever the admin pasted the link). The <video src="...">
 * attribute points at these routes so the raw origin URL never sits
 * in the HTML for inspect-element to copy. Auth + tier_gate run on
 * the way through, so a leaked /watch/src URL shared with a
 * logged-out friend bounces to login.
 *
 * The Network tab still reveals the final URL during playback —
 * that's a browser-level constraint we don't try to hide. Fully
 * proxying bytes through the VPS would trade Dropbox CDN bandwidth
 * for opacity, which isn't worth it for a Hostinger KVM 2.
 *
 * The class used to also do live FFmpeg transcoding (movie() /
 * episode() methods, MKV/MOV → H.264 MP4 on the fly). That path
 * was unreliable mid-stream and was removed in 1.4.0 — the new
 * rule is "MP4/WebM uploads only, convert before adding to library."
 */
class StreamProxyController extends Controller
{
    public function __construct(
        private readonly StreamSourceResolver $source,
        private readonly PlaybackAuthorizer $authorizer,
    ) {
    }

    public function passthroughMovie(Request $request, Movie $movie): RedirectResponse
    {
        $url = $this->getRawUrl($movie);
        abort_unless($url, 404);

        return redirect()->away($url, 302)->withHeaders([
            // Let the browser reuse the 302 for 5 min so a play →
            // pause → play (or restart from beginning) skips the
            // round-trip through Laravel + tier_gate + DB and goes
            // straight to the origin/CDN. `private` (not public):
            // the target is a tier-gated, token-signed URL — a
            // shared proxy must never serve one viewer's redirect
            // to another.
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function passthroughMovieLow(Request $request, Movie $movie): RedirectResponse
    {
        $url = $this->getRawUrl($movie, 'low');
        abort_unless($url, 404);

        return redirect()->away($url, 302)->withHeaders([
            // Let the browser reuse the 302 for 5 min so a play →
            // pause → play (or restart from beginning) skips the
            // round-trip through Laravel + tier_gate + DB and goes
            // straight to the origin/CDN. `private` (not public):
            // the target is a tier-gated, token-signed URL — a
            // shared proxy must never serve one viewer's redirect
            // to another.
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function passthroughEpisode(Request $request, Episode $episode): RedirectResponse
    {
        $url = $this->getRawUrl($episode);
        abort_unless($url, 404);

        return redirect()->away($url, 302)->withHeaders([
            // Let the browser reuse the 302 for 5 min so a play →
            // pause → play (or restart from beginning) skips the
            // round-trip through Laravel + tier_gate + DB and goes
            // straight to the origin/CDN. `private` (not public):
            // the target is a tier-gated, token-signed URL — a
            // shared proxy must never serve one viewer's redirect
            // to another.
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function passthroughEpisodeLow(Request $request, Episode $episode): RedirectResponse
    {
        $url = $this->getRawUrl($episode, 'low');
        abort_unless($url, 404);

        return redirect()->away($url, 302)->withHeaders([
            // Let the browser reuse the 302 for 5 min so a play →
            // pause → play (or restart from beginning) skips the
            // round-trip through Laravel + tier_gate + DB and goes
            // straight to the origin/CDN. `private` (not public):
            // the target is a tier-gated, token-signed URL — a
            // shared proxy must never serve one viewer's redirect
            // to another.
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * The URL to 302 to, or null when this title has no file at that quality.
     *
     * Reads the model directly rather than going through streamSource(), which
     * would return this controller's own route and loop forever.
     *
     * The rendition choice, the historic dropbox_path fallback and the CDN
     * rewrite all live in StreamSourceResolver now, because /api/v1 needs the
     * same answer and asking twice is how the entitlement rules ended up in
     * three places. This controller stays the single auth chokepoint; the
     * resolver is the single origin-routing one.
     */
    private function getRawUrl(Movie|Episode $model, string $quality = 'default'): ?string
    {
        // Publish/release gate. TierGate covers tier_required, but nothing
        // stopped a guessed slug from streaming a draft or a scheduled-but-
        // unreleased title straight from the origin.
        abort_unless($this->streamable($model), 404);

        return $this->source->resolve($model, $quality);
    }

    /**
     * Is this title streamable to the public right now?
     *
     * Delegates to PlaybackAuthorizer::isReleased(), which is the same call
     * the watch pages make. TierGate covers tier_required on the way in, but
     * nothing stopped a guessed slug from streaming a draft or a scheduled-
     * but-unreleased title straight from the origin, so this stays a
     * separate 404 rather than being folded into the tier decision: an
     * unreleased title must not advertise its plan.
     */
    private function streamable(Movie|Episode $model): bool
    {
        return $this->authorizer->isReleased($model, auth()->user());
    }
}
