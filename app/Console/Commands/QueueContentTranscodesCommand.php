<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Content\app\Jobs\DownloadAndTranscodeJob;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;

/**
 * Queue every movie + episode whose HLS hasn't been built yet.
 *
 * The transcode pipeline normally fires from the admin save flow,
 * but rows that came in via direct video_url updates (no upload, no
 * Dropbox button) skip the dispatch and end up serving as raw files.
 * On Dropbox that means freezes; for non-MP4 sources (mkv/m4v) it
 * means no playback at all. This command sweeps them up in one go.
 *
 *   php artisan content:queue-transcodes
 *
 * Idempotent. Safe to re-run; only rows missing hls_master_path get
 * queued. The systemd jambo-queue worker picks them up and chews
 * through them sequentially.
 */
class QueueContentTranscodesCommand extends Command
{
    protected $signature = 'content:queue-transcodes
                            {--only-movies : Only queue movies, skip episodes}
                            {--only-episodes : Only queue episodes, skip movies}';

    protected $description = 'Queue HLS transcode for every movie and/or episode that does not have it yet.';

    public function handle(): int
    {
        $count = 0;

        if (!$this->option('only-episodes')) {
            $count += $this->queue(Movie::class, 'movie');
        }
        if (!$this->option('only-movies')) {
            $count += $this->queue(Episode::class, 'episode');
        }

        $this->newLine();
        $this->info("Queued {$count} job" . ($count === 1 ? '' : 's') . '.');
        return self::SUCCESS;
    }

    private function queue(string $modelClass, string $payableType): int
    {
        // Skip rows that are already in flight — re-dispatching while a
        // job is queued or actively downloading/transcoding just creates
        // duplicate work for the worker.
        $inFlight = ['queued', 'downloading', 'transcoding'];

        $count = 0;
        $modelClass::query()
            ->whereNull('hls_master_path')
            // SQL three-valued logic: `NULL NOT IN (...)` returns NULL,
            // not TRUE, so plain whereNotIn would silently drop rows
            // whose transcode_status is null. The OR-null branch
            // explicitly readmits them.
            ->where(function ($q) use ($inFlight) {
                $q->whereNotIn('transcode_status', $inFlight)
                  ->orWhereNull('transcode_status');
            })
            ->each(function ($row) use ($payableType, &$count) {
                if (empty($row->video_url)) {
                    $this->line("  skip #{$row->id} — no video_url");
                    return;
                }
                $row->forceFill([
                    'transcode_status' => 'queued',
                    'transcode_error'  => null,
                ])->save();
                DownloadAndTranscodeJob::dispatch($payableType, $row->id, $row->video_url);
                $title = $row->title ?? ('episode ' . $row->id);
                $this->line("  queued #{$row->id} {$title}");
                $count++;
            });
        return $count;
    }
}
