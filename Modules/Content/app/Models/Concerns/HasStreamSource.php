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
 * The mime guess is best-effort by extension — good enough for local
 * test files. A missing or unrecognised extension falls back to
 * `video/mp4` which covers 99% of our use cases.
 */
trait HasStreamSource
{
    public function streamSource(): ?array
    {
        $url = $this->video_url ?? null;
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

        return [
            'type' => 'file',
            'url' => $url,
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
            'm3u8' => 'application/vnd.apple.mpegurl',
            'mov' => 'video/quicktime',
            default => 'video/mp4',
        };
    }
}
