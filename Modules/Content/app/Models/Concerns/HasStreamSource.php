<?php

namespace Modules\Content\app\Models\Concerns;

/**
 * Shared behaviour for any content model that carries a `video_url` — in
 * practice Movie and Episode. Inspects the URL and returns a structured
 * descriptor the player view can switch on:
 *
 *   ['type' => 'youtube', 'embed_url' => '...', 'url' => '...']   // iframe
 *   ['type' => 'file',    'url' => '...', 'mime' => 'video/mp4']  // <video>
 *   null                                                          // no source
 *
 * Direct-from-Dropbox model: every browser-playable upload (mp4/webm/m4v/ogg)
 * plays straight from its origin via a 302 passthrough — the VPS never
 * stores or transcodes video bytes. Non-playable formats (mkv/mov/avi)
 * must be converted to mp4 by the uploader before adding to the library;
 * the admin form rejects them.
 */
trait HasStreamSource
{
    public function streamSource(): ?array
    {
        $url = $this->video_url ?? null;

        // Fall back to dropbox_path — users may paste a Dropbox share URL
        // into the Dropbox tab instead of the URL tab.
        if (!$url && !empty($this->dropbox_path)) {
            $url = $this->dropbox_path;
        }

        if (!$url) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === 'youtu.be'
            || str_ends_with($host, 'youtube.com')
            || str_ends_with($host, 'youtube-nocookie.com')
        ) {
            $id = $this->extractYouTubeId($url);
            if (!$id) {
                return null;
            }
            return [
                'type' => 'youtube',
                'url' => $url,
                'embed_url' => 'https://www.youtube.com/embed/' . $id . '?rel=0&modestbranding=1',
                'video_id' => $id,
            ];
        }

        $isEpisode = $this instanceof \Modules\Content\app\Models\Episode;

        // Browser-safe format. Routed through Laravel's passthrough
        // controller (302 to the resolved origin URL) instead of handing
        // the raw Contabo / Dropbox URL to the player — keeps the origin
        // out of the <video src="..."> attribute, and lets us enforce
        // tier_gate + auth on every play.
        return [
            'type' => 'file',
            'url' => route(
                $isEpisode ? 'stream.src.episode' : 'stream.src.movie',
                [$isEpisode ? 'episode' : 'movie' => $isEpisode ? $this->id : $this->slug]
            ),
            'mime' => $this->guessVideoMime($url),
        ];
    }

    /**
     * Returns the stream source for the low-quality (Data Saver) version.
     * Only available when the admin has set a video_url_low.
     *
     * Like streamSource(), the URL is wrapped in a Laravel passthrough
     * route so the raw Contabo / Dropbox URL stays out of the player's
     * data-src-low attribute. The controller 302s to the real origin
     * after tier_gate + auth.
     */
    public function streamSourceLow(): ?array
    {
        $url = $this->video_url_low ?? null;
        if (!$url) return null;

        $isEpisode = $this instanceof \Modules\Content\app\Models\Episode;

        return [
            'type' => 'file',
            'url' => route(
                $isEpisode ? 'stream.src.episode.low' : 'stream.src.movie.low',
                [$isEpisode ? 'episode' : 'movie' => $isEpisode ? $this->id : $this->slug]
            ),
            'mime' => $this->guessVideoMime($url),
        ];
    }

    private function extractYouTubeId(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        if ($host === 'youtu.be') {
            return trim($path, '/') ?: null;
        }

        // /watch?v=ID
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
        if (!empty($q['v'])) {
            return $q['v'];
        }

        // /embed/ID or /shorts/ID or /v/ID
        if (preg_match('#/(embed|shorts|v)/([A-Za-z0-9_-]{6,})#', $path, $m)) {
            return $m[2];
        }

        return null;
    }

    private function guessVideoMime(string $url): string
    {
        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match ($ext) {
            'webm' => 'video/webm',
            'ogv', 'ogg' => 'video/ogg',
            'm4v' => 'video/x-m4v',
            default => 'video/mp4',
        };
    }
}
