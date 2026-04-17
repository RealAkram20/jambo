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
        // HLS takes precedence. Once the transcode job finishes, the
        // hls_master_path points at master.m3u8 on the private `hls` disk,
        // and we serve it through the StreamController so the browser
        // never sees the storage path. Video.js + VHS picks it up and
        // handles adaptive bitrate automatically.
        if (($this->transcode_status ?? null) === 'ready' && !empty($this->hls_master_path)) {
            $routeName = $this instanceof \Modules\Content\app\Models\Episode
                ? 'stream.episode'
                : 'stream.movie';
            $routeKey = $this instanceof \Modules\Content\app\Models\Episode
                ? $this->id
                : $this->slug;

            return [
                'type' => 'hls',
                'url' => route($routeName, [
                    $this instanceof \Modules\Content\app\Models\Episode ? 'episode' : 'movie' => $routeKey,
                    'path' => 'master.m3u8',
                ]),
                'mime' => 'application/vnd.apple.mpegurl',
            ];
        }

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

    /**
     * Returns the stream source for the low-quality (Data Saver) version.
     * Only available when the admin has set a video_url_low.
     */
    public function streamSourceLow(): ?array
    {
        $url = $this->video_url_low ?? null;
        if (!$url) return null;

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

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
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (str_starts_with($path, '/scl/')) {
            // New format: /scl/fi/xxx/file.mkv?rlkey=...&dl=0
            // Flip dl=0 → dl=1, then resolve the 302 redirect to get the
            // actual CDN URL (dl.dropboxusercontent.com). The www.dropbox.com
            // URL with dl=1 returns an HTML/JSON redirect page, not raw bytes.
            $url = preg_replace('/([?&])dl=\d+/', '$1dl=1', $url);
            if (!str_contains($url, 'dl=1')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'dl=1';
            }

            // Follow the redirect to get the direct CDN URL.
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADER         => true,
                CURLOPT_NOBODY         => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
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
        } else {
            // Legacy format: /s/xxx/file.mp4?dl=0
            // Rewrite domain to dl.dropboxusercontent.com.
            $url = preg_replace('#^https?://(www\.)?dropbox\.com/#i', 'https://dl.dropboxusercontent.com/', $url);
            $url = preg_replace('/([?&])dl=\d+(&|$)/i', '$1', $url);
            $url = rtrim($url, '?&');
        }

        return $url;
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
            'mkv' => 'video/x-matroska',
            'avi' => 'video/x-msvideo',
            'ts' => 'video/mp2t',
            default => 'video/mp4',
        };
    }
}
