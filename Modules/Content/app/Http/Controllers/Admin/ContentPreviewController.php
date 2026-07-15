<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Services\CdnUrlResolver;

/**
 * Admin-only "does this file actually play?" preview.
 *
 * Deliberately NOT the public playback path:
 *   - No tier_gate, so a draft/unpublished title previews fine.
 *   - No active_streams row, so previewing never counts against the
 *     admin's own device cap.
 *   - No PlaybackBeat / heartbeat, so a preview mints no watch-time and
 *     no view count — an admin scrubbing a movie must never move the
 *     monetization needle.
 *
 * It still routes through CdnUrlResolver, so the preview plays exactly
 * the way a real viewer will (through the CDN, signed if signing is on)
 * — the whole point is to catch a bad upload before it goes live.
 *
 * The whole controller lives behind the admin route group's
 * auth + role:admin middleware; the resolved origin URL is only ever
 * handed to a trusted admin.
 */
class ContentPreviewController extends Controller
{
    public function __construct(private readonly CdnUrlResolver $cdn)
    {
    }

    public function movie(Movie $movie): RedirectResponse
    {
        return $this->redirectToSource($movie->video_url ?: $movie->dropbox_path);
    }

    public function episode(Episode $episode): RedirectResponse
    {
        return $this->redirectToSource($episode->video_url ?: $episode->dropbox_path);
    }

    private function redirectToSource(?string $url): RedirectResponse
    {
        abort_unless($url, 404);

        // Short, private cache: an admin seeking around re-requests the
        // 302 a lot, but this URL is admin-scoped and may be signed, so
        // it must never be shared by a proxy.
        return redirect()->away($this->cdn->resolve($url), 302)
            ->withHeaders(['Cache-Control' => 'private, max-age=60']);
    }
}
