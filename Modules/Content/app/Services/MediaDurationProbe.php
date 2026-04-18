<?php

namespace Modules\Content\app\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Probes video duration via the `ffprobe` binary.
 *
 * Admin inputs can be wrong or missing — the real truth lives in the
 * file/stream itself. This service is the single source of truth for
 * anything that displays runtime (watch page, episode card, continue
 * watching, detail metadata). Callers should prefer probed data over
 * any manually entered value.
 *
 * Works against both local files on the `source` disk and remote
 * URLs (archive.org, Dropbox, etc.). Returns null on failure so the
 * caller can fall back to heartbeat-reported duration later.
 */
class MediaDurationProbe
{
    /**
     * Hard stop for probe execution. Remote probes over HTTPS to slow
     * hosts can hang; we'd rather surrender than block the admin form.
     * 25s is enough for archive.org's slower mirrors to hand off
     * the container header, while still being survivable on an admin
     * form submit.
     */
    private const TIMEOUT_SECONDS = 25;

    /**
     * Sanity bounds — nothing we host is shorter than 5s or longer
     * than 10h. Values outside this range are almost certainly wrong
     * (corrupt metadata, proxy stripping duration, unsupported codec).
     */
    private const MIN_SECONDS = 5;
    private const MAX_SECONDS = 36000;

    /**
     * Resolve duration for whatever storage handle is available.
     * Local file first (faster + doesn't hit the network), then URL.
     *
     * @return int|null seconds, or null if nothing probeable
     */
    public function detectFromAny(?string $sourcePath, ?string $videoUrl): ?int
    {
        if ($sourcePath) {
            $abs = $this->resolveSourceDiskPath($sourcePath);
            if ($abs && is_file($abs)) {
                $seconds = $this->probe($abs);
                if ($seconds !== null) {
                    return $seconds;
                }
            }
        }

        if ($videoUrl && preg_match('#^https?://#i', $videoUrl)) {
            return $this->probe($videoUrl);
        }

        return null;
    }

    /**
     * Run ffprobe, return integer seconds or null on any failure.
     * Uses Symfony Process so the timeout is enforced at the process
     * level (kills the child) rather than via PHP's set_time_limit
     * (which would crash the whole request on overrun).
     */
    public function probe(string $target): ?int
    {
        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $target,
        ]);
        $process->setTimeout(self::TIMEOUT_SECONDS);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            Log::debug('ffprobe timed out', ['target' => $target]);
            return null;
        } catch (\Throwable $e) {
            Log::debug('ffprobe threw', ['target' => $target, 'error' => $e->getMessage()]);
            return null;
        }

        if (!$process->isSuccessful()) {
            Log::debug('ffprobe failed', [
                'target' => $target,
                'exit' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
            ]);
            return null;
        }

        $seconds = (float) trim($process->getOutput());
        if (!is_finite($seconds) || $seconds < self::MIN_SECONDS || $seconds > self::MAX_SECONDS) {
            return null;
        }

        return (int) round($seconds);
    }

    /**
     * Resolve a relative path against the `source` disk root so
     * ffprobe can read it directly. Falls back to `public` if no
     * `source` disk is configured.
     */
    private function resolveSourceDiskPath(string $relative): ?string
    {
        foreach (['source', 'public'] as $disk) {
            try {
                $path = Storage::disk($disk)->path($relative);
                if ($path && is_file($path)) {
                    return $path;
                }
            } catch (\Throwable $e) {
                // disk not configured — try next
            }
        }
        return null;
    }
}
