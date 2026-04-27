<?php

namespace Modules\Content\app\Jobs;

use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSExporter;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

/**
 * Transcodes an uploaded video file into an HLS adaptive-bitrate ladder
 * (360p + 720p) written to the private `hls` disk.
 *
 * The job is self-contained: it reads `source_path` off the payable,
 * writes renditions + `master.m3u8` to `hls/{kind}/{id}/`, flips
 * `transcode_status` as it moves through queued → transcoding → ready/failed,
 * and stamps `hls_master_path`. The StreamController then serves bytes from
 * that path — the raw storage path is never exposed.
 */
class TranscodeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 6-hour ceiling. Even a 3-hour film at 0.5× realtime on a small
    // VPS finishes inside this. The previous 1-hour cap was killing
    // anything longer than ~50 minutes mid-stream — see failed_jobs
    // entries from 2026-04-26 (TimeoutExceededException).
    public int $timeout = 21600;
    public int $tries = 1;

    // When the job hits $timeout, call failed() so transcode_status
    // moves to 'failed' cleanly instead of leaving the asset stuck
    // on 'transcoding' forever. Default is false, which lets the
    // worker SIGTERM without giving the job a chance to write state.
    public bool $failOnTimeout = true;

    public function __construct(
        public string $payableType,  // 'movie' | 'episode'
        public int $payableId,
    ) {}

    public function handle(): void
    {
        $payable = $this->resolvePayable();
        if (!$payable) return;

        if (!$payable->source_path || !Storage::disk('source')->exists($payable->source_path)) {
            $this->fail($payable, 'Source file missing or unreadable.');
            return;
        }

        $payable->forceFill([
            'transcode_status' => 'transcoding',
            'transcode_error' => null,
        ])->save();

        // HLS output directory on the private disk.
        //   hls/{kind}/{id}/master.m3u8
        //   hls/{kind}/{id}/360p/…
        //   hls/{kind}/{id}/720p/…
        $outDir = $this->payableType . '/' . $payable->id;
        Storage::disk('hls')->deleteDirectory($outDir);
        Storage::disk('hls')->makeDirectory($outDir);

        try {
            // 2-rung ladder: 360p @ 400k for slow connections, 720p @ 800k
            // as the default. VHS auto-picks based on bandwidth and steps
            // down on congestion. Segment length 6s is the HLS sweet spot —
            // long enough for efficient ABR decisions, short enough to keep
            // startup latency low.
            //
            // -preset veryfast is the speed/size sweet spot for VOD on a
            // low-core VPS: ~4× faster than libx264's default `medium`
            // for ~10% larger files. Without this, full-length films
            // were taking longer to encode than they were to watch and
            // hitting the queue timeout.
            $rung360 = (new X264('aac', 'libx264'))
                ->setKiloBitrate(400)
                ->setAudioKiloBitrate(64)
                ->setAdditionalParameters(['-preset', 'veryfast']);
            $rung720 = (new X264('aac', 'libx264'))
                ->setKiloBitrate(800)
                ->setAudioKiloBitrate(96)
                ->setAdditionalParameters(['-preset', 'veryfast']);

            FFMpeg::fromDisk('source')
                ->open($payable->source_path)
                ->exportForHLS()
                ->setSegmentLength(6)
                ->addFormat($rung360, function ($media) { $media->scale(640, 360); })
                ->addFormat($rung720, function ($media) { $media->scale(1280, 720); })
                ->toDisk('hls')
                ->save($outDir . '/master.m3u8');

            $payable->forceFill([
                'hls_master_path' => $outDir . '/master.m3u8',
                'transcode_status' => 'ready',
                'transcode_error' => null,
            ])->save();

            Log::info('[transcode] finished', [
                'kind' => $this->payableType,
                'id' => $payable->id,
                'master' => $outDir . '/master.m3u8',
            ]);

            // Auto-publish + audience push if the admin clicked
            // Publish before transcoding finished. The dispatch happens
            // here rather than in the controller because this is the
            // moment when the asset is actually watchable.
            $this->autoPublish($payable);
        } catch (\Throwable $e) {
            $this->fail($payable, $e->getMessage());
            Log::error('[transcode] failed', [
                'kind' => $this->payableType,
                'id' => $payable->id,
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
            'transcode_error' => substr($message, 0, 1000),
        ])->save();
    }

    /**
     * If the admin clicked Publish before this finished encoding,
     * flip the asset to published, stamp published_at, and fire the
     * matching *Added event so subscribers get pushed. Movies and
     * episodes diverge on the published signal (movies: status column;
     * episodes: published_at presence) so each is handled separately.
     *
     * Idempotent for re-transcodes: if the asset was already public,
     * we won't fire the audience-facing event again.
     */
    private function autoPublish(Movie|Episode $payable): void
    {
        if (!($payable->publish_when_ready ?? false)) {
            return;
        }

        if ($payable instanceof Movie) {
            $alreadyPublished = $payable->status === 'published';
            $payable->forceFill([
                'status'              => 'published',
                'published_at'        => $payable->published_at ?: now(),
                'publish_when_ready'  => false,
            ])->save();

            Log::info('[transcode] auto-published movie', [
                'id' => $payable->id, 'title' => $payable->title,
            ]);

            if (!$alreadyPublished) {
                event(new \Modules\Notifications\app\Events\MovieAdded(
                    $payable->id, $payable->title, $payable->slug, $payable->poster_url,
                ));
            }
            return;
        }

        if ($payable instanceof Episode) {
            $alreadyPublished = (bool) $payable->published_at;
            $payable->forceFill([
                'published_at'       => $payable->published_at ?: now(),
                'publish_when_ready' => false,
            ])->save();

            Log::info('[transcode] auto-published episode', [
                'id' => $payable->id,
            ]);

            if (!$alreadyPublished) {
                $show = $payable->season?->show;
                if ($show) {
                    event(new \Modules\Notifications\app\Events\EpisodeAdded(
                        $show->title, $payable->season->number, $payable->number,
                        $payable->title, $show->slug, $payable->still_url ?? $show->poster_url,
                    ));
                }
            }
        }
    }
}
