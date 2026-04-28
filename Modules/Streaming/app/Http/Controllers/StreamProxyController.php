<?php

namespace Modules\Streaming\app\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
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

        // Normalize Dropbox URLs to ensure dl=1, then resolve the
        // www.dropbox.com → dropboxusercontent.com redirect server-side
        // so the browser can hit the CDN directly. Skipping this leaves
        // the browser to chase 3-4 redirects on every play, which on a
        // cold cache trips the 10s player stall-timeout and locks the
        // user in an infinite reload loop. Cached for 30 min — Dropbox's
        // signed CDN URLs typically last longer than that, and re-
        // resolving once a half-hour for popular titles is cheap.
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $isDropbox = $host === 'dropbox.com'
            || str_ends_with($host, '.dropbox.com')
            || str_ends_with($host, '.dropboxusercontent.com');

        if ($isDropbox) {
            $url = preg_replace('/([?&])dl=\d+/', '$1dl=1', $url);
            if (!str_contains($url, 'dl=1')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'dl=1';
            }

            // Only worth resolving share-link hosts. Already-direct
            // .dropboxusercontent.com URLs short-circuit (no redirect
            // to chase).
            if ($host === 'dropbox.com' || str_ends_with($host, '.dropbox.com')) {
                $url = $this->resolveDropboxRedirect($url);
            }
        }

        return $url;
    }

    /**
     * Follow the first redirect from a www.dropbox.com share URL to its
     * dropboxusercontent.com CDN target. Returns the original URL if
     * resolution fails (network blip, timeout) so playback always has
     * SOMETHING to redirect to — the browser will just chase the chain
     * itself in that case.
     *
     * Cache key is the input URL; TTL is 30 min. Dropbox's signed CDN
     * URLs are typically valid for ~4 hours, so a 30-min cache trades
     * a small amount of staleness for not having to re-curl on every
     * play of a popular movie.
     */
    private function resolveDropboxRedirect(string $shareUrl): string
    {
        return Cache::remember(
            'dropbox-cdn:' . sha1($shareUrl),
            now()->addMinutes(30),
            function () use ($shareUrl) {
                $ch = curl_init($shareUrl);
                curl_setopt_array($ch, [
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_HEADER         => true,
                    CURLOPT_NOBODY         => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 5,
                    CURLOPT_CONNECTTIMEOUT => 3,
                    CURLOPT_USERAGENT      => 'Jambo/1.0',
                ]);

                $response = curl_exec($ch);
                curl_close($ch);

                if ($response && preg_match('/^Location:\s*(.+)$/mi', $response, $m)) {
                    $cdnUrl = trim($m[1]);
                    if (str_contains($cdnUrl, 'dropboxusercontent.com')) {
                        return $cdnUrl;
                    }
                }

                return $shareUrl; // fall back — browser chases the chain
            }
        );
    }
}
