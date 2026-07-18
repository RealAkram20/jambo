<?php

namespace Modules\Streaming\app\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Services\CdnUrlResolver;

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
    public function __construct(private readonly CdnUrlResolver $cdn)
    {
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
     * Get the raw video URL directly from the model, bypassing
     * streamSource() which would return our own passthrough route
     * (infinite redirect loop).
     *
     * $quality: 'default' reads video_url (falling back to dropbox_path);
     * 'low' reads video_url_low — used by the Data Saver path.
     */
    private function getRawUrl(Movie|Episode $model, string $quality = 'default'): ?string
    {
        // Publish/release gate. TierGate covers tier_required, but nothing
        // stopped a guessed slug from streaming a draft or a scheduled-but-
        // unreleased title straight from the origin. Mirror the HTML watch
        // page: you can't stream what isn't publicly visible yet. Admins are
        // exempt (they verify playback), matching userCanWatch()'s bypass.
        abort_unless($this->streamable($model), 404);

        if ($quality === 'low') {
            $url = $model->video_url_low ?? null;
        } else {
            $url = $model->video_url ?? null;

            if (!$url && !empty($model->dropbox_path)) {
                $url = $model->dropbox_path;
            }
        }

        if (!$url) return null;

        // Per-provider URL resolution lives in CdnUrlResolver:
        // Dropbox links get raw=1 normalization (iOS refuses to play
        // Content-Disposition: attachment), Backblaze links are
        // rewritten to the Bunny pull zone and token-signed. This
        // controller stays the single auth/tier chokepoint; the
        // resolver is the single origin-routing chokepoint.
        return $this->cdn->resolve($url);
    }

    /**
     * Is this title streamable to the public right now? A movie must be
     * publicly visible; an episode must itself be released AND belong to a
     * publicly-visible show (an episode of a draft/unreleased series is not
     * reachable, mirroring the episode watch page). Admins bypass so they
     * can verify playback of scheduled content, exactly as userCanWatch does.
     */
    private function streamable(Movie|Episode $model): bool
    {
        if (auth()->user()?->hasRole('admin')) {
            return true;
        }

        if ($model instanceof Movie) {
            return $model->isPubliclyVisible();
        }

        $show = $model->show;

        return $model->isPubliclyVisible() && $show && $show->isPubliclyVisible();
    }
}
