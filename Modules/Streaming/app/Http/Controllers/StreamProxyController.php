<?php

namespace Modules\Streaming\app\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Proxies video streams through FFmpeg to convert unsupported formats
 * (MKV, HEVC/x265, AVI, etc.) into browser-playable H.264 MP4 on the fly.
 *
 * For files already in H.264 MP4, the browser plays them directly without
 * hitting the FFmpeg transcode path — but the URL is still routed through
 * this controller via the `passthrough*` actions so the raw Contabo /
 * Dropbox / CDN URL never appears in the <video src="..."> attribute.
 * Devtools Network tab still reveals the redirect target during playback
 * (browsers expose this); fully hiding it would require byte-proxying
 * through the VPS, which trades bandwidth for opacity.
 */
class StreamProxyController extends Controller
{
    public function movie(Request $request, Movie $movie): StreamedResponse
    {
        $url = $this->getRawUrl($movie);
        abort_unless($url, 404);

        return $this->transcode($url);
    }

    public function episode(Request $request, Episode $episode): StreamedResponse
    {
        $url = $this->getRawUrl($episode);
        abort_unless($url, 404);

        return $this->transcode($url);
    }

    /**
     * Session-gated redirect to the real origin URL for a browser-safe
     * movie. The <video> src points at this Laravel route, so the raw
     * Contabo / Dropbox URL is no longer sitting in the HTML for anyone
     * to copy out of inspect-element. The redirect still requires the
     * user's tier_gate + auth middleware to pass, so a leaked /watch/src
     * URL shared with a logged-out friend gets bounced to login.
     */
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
     * streamSource() which would return the proxy URL (infinite loop).
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

    private function transcode(string $url): StreamedResponse
    {
        $ffmpeg = config('laravel-ffmpeg.ffmpeg.binaries', 'ffmpeg');
        $stderr = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';

        \Log::info('[proxy] transcode', ['url' => substr($url, 0, 100), 'ffmpeg' => basename($ffmpeg)]);

        $isRemote = (bool) preg_match('#^https?://#i', $url);

        if ($isRemote) {
            // Remote: use proc_open to pipe curl stdout → FFmpeg stdin.
            // PHP's popen() can't handle shell pipes on Windows reliably.
            return $this->streamRemote($url, $ffmpeg, $stderr);
        }

        $localPath = $this->resolveLocalPath($url);
        abort_unless($localPath, 404);

        $cmd = sprintf(
            '%s -i %s -map 0:v:0 -map 0:a:0 -c:v libx264 -preset ultrafast -crf 23 -c:a aac -b:a 192k -f mp4 -movflags frag_keyframe+empty_moov pipe:1 %s',
            escapeshellarg($ffmpeg),
            escapeshellarg($localPath),
            $stderr
        );

        return new StreamedResponse(function () use ($cmd) {
            set_time_limit(0);
            $proc = popen($cmd, 'r');
            if (!$proc) return;

            while (!feof($proc)) {
                $chunk = fread($proc, 65536);
                if ($chunk === false || $chunk === '') break;
                echo $chunk;
                if (ob_get_level()) ob_flush();
                flush();
            }

            pclose($proc);
        }, 200, [
            'Content-Type' => 'video/mp4',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Stream a remote URL through FFmpeg. Writes a temp shell script
     * and executes it via bash, since PHP's popen on Windows can't
     * handle binary pipe chains (cmd.exe corrupts binary data).
     *
     * On Linux/VPS this uses /bin/sh directly.
     * On Windows dev it uses Git Bash.
     */
    private function streamRemote(string $url, string $ffmpeg, string $stderr): StreamedResponse
    {
        $isWin = PHP_OS_FAMILY === 'Windows';
        $scriptPath = sys_get_temp_dir() . '/jambo_proxy_' . md5($url . time()) . '.sh';

        $ffmpegPath = $isWin ? str_replace('\\', '/', $ffmpeg) : $ffmpeg;
        $script = "#!/bin/sh\n" .
            'curl -sL "' . $url . '" | "' . $ffmpegPath . '" -i pipe:0 -map 0:v:0 -map 0:a:0 -c:v libx264 -preset ultrafast -crf 23 -c:a aac -b:a 192k -f mp4 -movflags frag_keyframe+empty_moov pipe:1 2>/dev/null' . "\n";
        file_put_contents($scriptPath, $script);

        // Find bash: Git Bash on Windows, /bin/sh on Linux.
        if ($isWin) {
            $bash = 'C:/Program Files/Git/bin/bash.exe';
            if (!file_exists($bash)) $bash = 'bash'; // fallback
            $runCmd = '"' . $bash . '" "' . str_replace('\\', '/', $scriptPath) . '"';
        } else {
            chmod($scriptPath, 0755);
            $runCmd = $scriptPath;
        }

        return new StreamedResponse(function () use ($runCmd, $scriptPath) {
            set_time_limit(0);
            $proc = popen($runCmd, 'rb');
            if (!$proc) return;

            while (!feof($proc)) {
                $chunk = fread($proc, 65536);
                if ($chunk === false || $chunk === '') break;
                echo $chunk;
                if (ob_get_level()) ob_flush();
                flush();
            }

            pclose($proc);
            @unlink($scriptPath);
        }, 200, [
            'Content-Type' => 'video/mp4',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function resolveLocalPath(string $url): ?string
    {
        $decoded = urldecode($url);
        $base = parse_url(config('app.url'), PHP_URL_PATH) ?: '';
        $base = rtrim($base, '/');
        if ($base !== '' && str_starts_with($decoded, $base)) {
            $decoded = substr($decoded, strlen($base));
        }

        $absolute = public_path(ltrim($decoded, '/'));
        $real = realpath($absolute);

        if (!$real || !file_exists($real)) return null;

        return $real;
    }
}
