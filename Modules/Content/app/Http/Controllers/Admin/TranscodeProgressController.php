<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;

/**
 * JSON endpoint that the admin movie/episode edit page polls every
 * few seconds while a transcode is in flight. Returns enough state to
 * drive the progress bar — current status, error message if any,
 * percent complete (estimated from frame count vs source duration),
 * and whether publish_when_ready is set so the UI can show "will
 * auto-publish" copy.
 */
class TranscodeProgressController extends Controller
{
    public function movie(Movie $movie): JsonResponse
    {
        return response()->json($this->payload('movie', $movie));
    }

    public function episode(Episode $episode): JsonResponse
    {
        return response()->json($this->payload('episode', $episode));
    }

    private function payload(string $kind, Movie|Episode $row): array
    {
        $isPublished = $row instanceof Movie
            ? $row->status === 'published'
            : (bool) $row->published_at;

        return [
            'kind'                => $kind,
            'id'                  => $row->id,
            'transcode_status'    => $row->transcode_status,
            'transcode_error'     => $row->transcode_error,
            'hls_ready'           => !empty($row->hls_master_path),
            'publish_when_ready'  => (bool) ($row->publish_when_ready ?? false),
            'is_published'        => $isPublished,
            'percent'             => $this->estimatePercent($kind, $row),
        ];
    }

    /**
     * Best-effort progress estimate by reading the most recent
     * temporary HLS segment number from the disk. ffmpeg writes one
     * .ts segment every 6s of source video, so segment_count * 6 ÷
     * total_duration ≈ progress. Returns null when we can't tell —
     * UI then falls back to a spinner.
     */
    private function estimatePercent(string $kind, Movie|Episode $row): ?int
    {
        if ($row->transcode_status !== 'transcoding') {
            return $row->transcode_status === 'ready' ? 100 : null;
        }

        $runtime = $row->runtime_minutes ?? null;
        if (!$runtime || $runtime <= 0) {
            return null;
        }

        $dir = $kind . '/' . $row->id;
        try {
            $files = Storage::disk('hls')->files($dir);
        } catch (\Throwable) {
            return null;
        }

        $segments = collect($files)->filter(fn ($f) => str_ends_with($f, '.ts'))->count();
        if ($segments === 0) {
            return 0;
        }

        // We emit two ladders (360p + 720p), so each second of source
        // produces two .ts files. Segment length is 6s.
        $secondsTranscoded = ($segments / 2) * 6;
        $totalSeconds = $runtime * 60;
        $pct = (int) min(99, round(($secondsTranscoded / max(1, $totalSeconds)) * 100));
        return max(0, $pct);
    }
}
