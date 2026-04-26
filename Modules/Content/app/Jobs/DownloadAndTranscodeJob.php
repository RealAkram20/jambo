<?php

namespace Modules\Content\app\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;

/**
 * Downloads a remote video (e.g. Dropbox share link) to the private `source`
 * disk, then chains into TranscodeVideoJob for HLS encoding.
 *
 * Status flow: queued → downloading → transcoding → ready  (or failed).
 */
class DownloadAndTranscodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        public string $payableType,   // 'movie' | 'episode'
        public int    $payableId,
        public string $downloadUrl,   // direct-download URL (dl=1 already applied)
    ) {}

    public function handle(): void
    {
        $payable = $this->resolvePayable();
        if (!$payable) return;

        // Normalise Dropbox share links so we always get the actual
        // file. Without this the bare URL returns the share-page HTML
        // and the next step fails with "moov atom not found".
        $url = $this->normaliseDropboxUrl($this->downloadUrl);

        $payable->forceFill([
            'transcode_status' => 'downloading',
            'transcode_error'  => null,
        ])->save();

        $ext = $this->guessExtension($url);
        $dir = ($this->payableType === 'episode' ? 'episodes' : 'movies') . '/' . $payable->id;
        $destPath = $dir . '/source.' . $ext;

        try {
            Storage::disk('source')->makeDirectory($dir);

            // Stream the download directly to disk to avoid memory issues.
            $tempFile = Storage::disk('source')->path($destPath);
            $this->streamDownload($url, $tempFile);

            $size = Storage::disk('source')->size($destPath);
            if ($size < 1024) {
                $this->fail($payable, 'Downloaded file is too small (' . $size . ' bytes) — the URL may be invalid or expired.');
                return;
            }

            Log::info('[download] finished', [
                'kind' => $this->payableType,
                'id'   => $payable->id,
                'size' => round($size / 1024 / 1024, 1) . ' MB',
            ]);

            // Wipe previous HLS output.
            if ($payable->hls_master_path) {
                Storage::disk('hls')->deleteDirectory($this->payableType . '/' . $payable->id);
            }

            $payable->forceFill([
                'source_path'      => $destPath,
                'hls_master_path'  => null,
                'transcode_status' => 'queued',
                'transcode_error'  => null,
            ])->save();

            // Chain straight into the transcode job.
            TranscodeVideoJob::dispatch($this->payableType, $payable->id);

        } catch (\Throwable $e) {
            $this->fail($payable, $e->getMessage());
            Log::error('[download] failed', [
                'kind'  => $this->payableType,
                'id'    => $payable->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $payable = $this->resolvePayable();
        if ($payable) $this->fail($payable, $e->getMessage());
    }

    /**
     * Stream a remote URL directly to a local file path using cURL,
     * so we never hold the full video in memory.
     */
    private function streamDownload(string $url, string $dest): void
    {
        $fp = fopen($dest, 'w');
        if (!$fp) {
            throw new \RuntimeException("Cannot open {$dest} for writing.");
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 3600,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_USERAGENT      => 'Jambo/1.0',
        ]);

        $ok = curl_exec($ch);
        $error = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if (!$ok) {
            @unlink($dest);
            throw new \RuntimeException("Download failed (HTTP {$code}): {$error}");
        }
    }

    private function guessExtension(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['mp4', 'webm', 'mov', 'mkv', 'm4v']) ? $ext : 'mp4';
    }

    /**
     * Convert a Dropbox share URL into a direct-download URL. Mirrors
     * MovieController::normaliseDropboxUrl so jobs dispatched outside
     * the controller (manual tinker, console commands, batch jobs)
     * also get the real file instead of the share-landing-page HTML.
     */
    private function normaliseDropboxUrl(string $url): string
    {
        if (!preg_match('#^https?://([^/]+\.)?dropbox\.com/#i', $url)
            && !preg_match('#^https?://([^/]+\.)?dropboxusercontent\.com/#i', $url)) {
            return $url;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        if (str_starts_with($path, '/scl/')) {
            $url = preg_replace('/([?&])dl=\d+/', '$1dl=1', $url);
            if (!str_contains($url, 'dl=1')) {
                $url .= (str_contains($url, '?') ? '&' : '?') . 'dl=1';
            }
        } else {
            $url = preg_replace('#^https?://(www\.)?dropbox\.com/#i', 'https://dl.dropboxusercontent.com/', $url);
            $url = preg_replace('/([?&])dl=\d+(&|$)/i', '$1', $url);
            $url = rtrim($url, '?&');
        }

        return $url;
    }

    private function resolvePayable(): Movie|Episode|null
    {
        return match ($this->payableType) {
            'movie'   => Movie::find($this->payableId),
            'episode' => Episode::find($this->payableId),
            default   => null,
        };
    }

    private function fail(Movie|Episode $payable, string $message): void
    {
        $payable->forceFill([
            'transcode_status' => 'failed',
            'transcode_error'  => substr($message, 0, 1000),
        ])->save();
    }
}
