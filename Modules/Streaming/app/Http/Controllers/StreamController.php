<?php

namespace Modules\Streaming\app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves HLS playlists + segments off the private `hls` disk through a
 * controller, so the browser never sees storage paths. All routes pass
 * through `auth` + `tier_gate` — every segment request re-validates the
 * viewer's subscription, not just the initial page load.
 *
 * URL shape:
 *   /stream/movie/{slug}/master.m3u8
 *   /stream/movie/{slug}/720p/segment_001.ts
 *   /stream/episode/{id}/master.m3u8
 *   /stream/episode/{id}/360p/playlist.m3u8
 */
class StreamController extends Controller
{
    public function movie(Request $request, Movie $movie, string $path = 'master.m3u8'): Response
    {
        return $this->serve('movie', $movie->id, $movie->hls_master_path, $path);
    }

    public function episode(Request $request, Episode $episode, string $path = 'master.m3u8'): Response
    {
        return $this->serve('episode', $episode->id, $episode->hls_master_path, $path);
    }

    private function serve(string $kind, int $id, ?string $masterPath, string $path): Response
    {
        if (!$masterPath) {
            abort(404, 'Stream not available.');
        }

        // Strict whitelist on the path fragment. HLS output only ever uses
        // ASCII letters, digits, underscore, dash, dot, and slash — so any
        // traversal attempt (`..`, leading `/`, backslashes, nulls) or odd
        // character short-circuits before we touch the filesystem.
        if (!preg_match('#^[A-Za-z0-9_./-]+$#', $path)
            || str_contains($path, '..')
            || str_starts_with($path, '/')
        ) {
            abort(404);
        }

        $full = $kind . '/' . $id . '/' . $path;

        if (!Storage::disk('hls')->exists($full)) {
            abort(404);
        }

        $absolute = Storage::disk('hls')->path($full);

        // Pick the right MIME for the HLS artifact type. Browsers + Video.js
        // pay attention: application/vnd.apple.mpegurl is the official
        // playlist MIME; .ts segments need video/mp2t so Safari's native
        // player doesn't gag.
        $mime = match (pathinfo($path, PATHINFO_EXTENSION)) {
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts'   => 'video/mp2t',
            'm4s'  => 'video/iso.segment',
            'mp4'  => 'video/mp4',
            default => 'application/octet-stream',
        };

        return response()->file($absolute, [
            'Content-Type' => $mime,
            // Playlists shouldn't be cached (they'll be rewritten when the
            // transcode runs again). Segments are immutable once written.
            'Cache-Control' => str_ends_with($path, '.m3u8')
                ? 'no-cache, no-store, must-revalidate'
                : 'public, max-age=31536000, immutable',
        ]);
    }
}
