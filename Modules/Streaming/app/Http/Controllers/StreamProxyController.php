<?php

namespace Modules\Streaming\app\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;

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
    public function passthroughMovie(Request $request, Movie $movie): RedirectResponse
    {
        $url = $this->getRawUrl($movie);
        abort_unless($url, 404);

        return redirect()->away($url, 302)->withHeaders([
            // Let browsers reuse the 302 for 5 min so a play → pause →
            // play (or restart from beginning) skips the round-trip
            // through Laravel + tier_gate + DB and goes straight to
            // Dropbox. Public is safe — the redirect target itself is
            // a signed Dropbox URL, and tier_gate already ran when
            // the response was first issued.
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function passthroughMovieLow(Request $request, Movie $movie): RedirectResponse
    {
        $url = $this->getRawUrl($movie, 'low');
        abort_unless($url, 404);

        return redirect()->away($url, 302)->withHeaders([
            // Let browsers reuse the 302 for 5 min so a play → pause →
            // play (or restart from beginning) skips the round-trip
            // through Laravel + tier_gate + DB and goes straight to
            // Dropbox. Public is safe — the redirect target itself is
            // a signed Dropbox URL, and tier_gate already ran when
            // the response was first issued.
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function passthroughEpisode(Request $request, Episode $episode): RedirectResponse
    {
        $url = $this->getRawUrl($episode);
        abort_unless($url, 404);

        return redirect()->away($url, 302)->withHeaders([
            // Let browsers reuse the 302 for 5 min so a play → pause →
            // play (or restart from beginning) skips the round-trip
            // through Laravel + tier_gate + DB and goes straight to
            // Dropbox. Public is safe — the redirect target itself is
            // a signed Dropbox URL, and tier_gate already ran when
            // the response was first issued.
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function passthroughEpisodeLow(Request $request, Episode $episode): RedirectResponse
    {
        $url = $this->getRawUrl($episode, 'low');
        abort_unless($url, 404);

        return redirect()->away($url, 302)->withHeaders([
            // Let browsers reuse the 302 for 5 min so a play → pause →
            // play (or restart from beginning) skips the round-trip
            // through Laravel + tier_gate + DB and goes straight to
            // Dropbox. Public is safe — the redirect target itself is
            // a signed Dropbox URL, and tier_gate already ran when
            // the response was first issued.
            'Cache-Control' => 'public, max-age=300',
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
        if ($quality === 'low') {
            $url = $model->video_url_low ?? null;
        } else {
            $url = $model->video_url ?? null;

            if (!$url && !empty($model->dropbox_path)) {
                $url = $model->dropbox_path;
            }
        }

        if (!$url) return null;

        // Normalize Dropbox URLs for inline video playback.
        //
        // ?dl=1  → Dropbox sends `Content-Disposition: attachment;
        //          filename="..."`. Chrome / Firefox / Android ignore
        //          that header on a <video src=...> request and play
        //          the file. iPhone Safari (and any iOS browser, which
        //          all use WebKit) refuses — it treats `attachment` as
        //          "this is a download, not a video," and the player
        //          fires MEDIA_ERR_SRC_NOT_SUPPORTED (code 4) before
        //          a single byte is decoded. That's the "works on
        //          desktop + Android, fails on iPhone" pattern users
        //          have been hitting.
        //
        // ?raw=1 → Dropbox sends `Content-Disposition: inline` (or
        //          omits the header). Same byte stream; iPhone plays
        //          it. Other browsers don't care either way.
        //
        // Strip any dl= variant and force raw=1. Use parse_url +
        // http_build_query rather than regex on the query string —
        // safer when admins paste URLs with weird parameter ordering
        // or already-encoded characters.
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === 'dropbox.com'
            || str_ends_with($host, '.dropbox.com')
            || str_ends_with($host, '.dropboxusercontent.com')
        ) {
            $parts = parse_url($url);
            parse_str($parts['query'] ?? '', $query);
            unset($query['dl']);
            $query['raw'] = '1';

            $rebuilt = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
            if (!empty($parts['port']))     $rebuilt .= ':' . $parts['port'];
            if (!empty($parts['path']))     $rebuilt .= $parts['path'];
            $rebuilt .= '?' . http_build_query($query);
            if (!empty($parts['fragment'])) $rebuilt .= '#' . $parts['fragment'];

            $url = $rebuilt;
        }

        return $url;
    }
}
