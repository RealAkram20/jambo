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

        // Dropbox: rewrite share URLs to direct-streamable ones. The
        // regular www.dropbox.com/s/... URL serves an HTML preview
        // page — useless to the player — so we normalise to the
        // dl.dropboxusercontent.com host and drop the dl=0/dl=1 flag.
        if ($host === 'dropbox.com'
            || str_ends_with($host, '.dropbox.com')
            || str_ends_with($host, '.dropboxusercontent.com')
        ) {
            return [
                'type' => 'file',
                'url' => $this->normaliseDropboxUrl($url),
                'mime' => $this->guessVideoMime($url),
            ];
        }

        return [
            'type' => 'file',
            'url' => $url,
            'mime' => $this->guessVideoMime($url),
        ];
    }

    private function normaliseDropboxUrl(string $url): string
    {
        // https://www.dropbox.com/s/xxx/file.mp4?dl=0
        //   -> https://dl.dropboxusercontent.com/s/xxx/file.mp4
        $url = preg_replace('#^https?://(www\.)?dropbox\.com/#i', 'https://dl.dropboxusercontent.com/', $url);
        $url = preg_replace('/([?&])dl=\d+(&|$)/i', '$1', $url);
        return rtrim($url, '?&');
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
