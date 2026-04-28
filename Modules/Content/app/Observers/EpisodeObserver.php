<?php

namespace Modules\Content\app\Observers;

use Modules\Content\app\Models\Episode;
use Modules\Content\app\Services\MediaDurationProbe;

/**
 * Mirror of MovieObserver for episode rows. Runs FFprobe on
 * video_url changes and writes runtime_minutes from the real file
 * metadata.
 */
class EpisodeObserver
{
    public function __construct(private MediaDurationProbe $probe)
    {
    }

    public function saving(Episode $episode): void
    {
        $dirtySource = !$episode->exists || $episode->isDirty('video_url');

        if (!$dirtySource) {
            return;
        }

        $seconds = $this->probe->detectFromAny(null, $episode->video_url);

        if ($seconds !== null) {
            $episode->runtime_minutes = max(1, (int) round($seconds / 60));
        }
    }
}
