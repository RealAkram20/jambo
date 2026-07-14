<?php

namespace Modules\Streaming\app\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Fired on every accepted player heartbeat (~15s cadence) after
 * watch_history / active_streams bookkeeping succeeds.
 *
 * Deliberately a dumb data carrier: listeners (currently the
 * Monetization accrual pipeline) keep their own state — the event does
 * NOT expose watch_history's previous position because Eloquent syncs
 * originals on save, and because monetization needs month-scoped
 * baselines that watch_history can't provide anyway.
 *
 * $position is the CLIENT-reported playhead in seconds. Trust it only
 * as a progress signal, never as a duration source.
 */
class PlaybackBeat
{
    public function __construct(
        public readonly int $userId,
        public readonly Model $item, // Movie or Episode
        public readonly int $position,
        public readonly ?string $sessionId,
        public readonly ?string $ip,
    ) {
    }
}
