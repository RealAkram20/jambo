<?php

namespace Modules\Monetization\app\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Monetization\app\Services\WatchAccrualService;
use Modules\Streaming\app\Events\PlaybackBeat;

/**
 * Bridges the Streaming heartbeat into monetization accrual.
 *
 * HARD RULE: this listener may never throw. It sits on the playback
 * hot path (every viewer, every 15s) — a bug here must degrade to a
 * logged warning, not a 500 on the player.
 */
class RecordWatchAccrual
{
    public function __construct(protected WatchAccrualService $accrual)
    {
    }

    public function handle(PlaybackBeat $beat): void
    {
        try {
            $this->accrual->ingest($beat);
        } catch (\Throwable $e) {
            Log::warning('Monetization accrual failed for heartbeat', [
                'user_id' => $beat->userId,
                'watchable' => $beat->item->getMorphClass().'#'.$beat->item->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
