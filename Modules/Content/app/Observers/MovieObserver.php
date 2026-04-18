<?php

namespace Modules\Content\app\Observers;

use Modules\Content\app\Models\Movie;
use Modules\Content\app\Services\MediaDurationProbe;

/**
 * Keeps `runtime_minutes` honest — runs ffprobe whenever the source
 * changes (source_path on upload, video_url on paste) and writes the
 * real duration back. Display surfaces (watch page, detail page,
 * continue-watching card, carousels) read `runtime_minutes` directly,
 * so this observer is what makes those numbers trustworthy.
 */
class MovieObserver
{
    public function __construct(private MediaDurationProbe $probe)
    {
    }

    public function saving(Movie $movie): void
    {
        // Only re-probe when the source actually changed — we don't
        // want to waste an FFprobe call every time an admin edits the
        // title or synopsis. `exists === false` means a brand-new row.
        $dirtySource = !$movie->exists
            || $movie->isDirty('source_path')
            || $movie->isDirty('video_url');

        if (!$dirtySource) {
            return;
        }

        $seconds = $this->probe->detectFromAny(
            $movie->source_path,
            $movie->video_url
        );

        if ($seconds !== null) {
            $movie->runtime_minutes = max(1, (int) round($seconds / 60));
        }
    }
}
