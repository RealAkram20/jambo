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

        return redirect()->away($url, 302);
    }

    public function passthroughMovieLow(Request $request, Movie $movie): RedirectResponse
    {
        $url = $this->getRawUrl($movie, 'low');
        abort_unless($url, 404);

        return redirect()->away($url, 302);
    }

    public function passthroughEpisode(Request $request, Episode $episode): RedirectResponse
    {
        $url = $this->getRawUrl($episode);
        abort_unless($url, 404);

        return redirect()->away($url, 302);
    }

    public function passthroughEpisodeLow(Request $request, Episode $episode): RedirectResponse
    {
        $url = $this->getRawUrl($episode, 'low');
        abort_unless($url, 404);

        return redirect()->away($url, 302);
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

        // Normalize Dropbox URLs to ensure dl=1.
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === 'dropbox.com'
            || str_ends_with($host, '.dropbox.com')
            || str_ends_with($host, '.dropboxusercontent.com')
        ) {
            $url = preg_replace('/([?&])dl=\d+/', '$1dl=1', $url);
            if (!str_contains($url, 'dl=1')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'dl=1';
            }
        }

        return $url;
    }
}
