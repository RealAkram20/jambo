<?php

namespace Modules\Streaming\app\Services;

use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Events\PlaybackBeat;
use Modules\Streaming\app\Models\ActiveStream;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Streaming\app\Playback\BeatOutcome;

/**
 * Records one playback heartbeat: resume position, the live session signal
 * the concurrency cap reads, and the event Monetization accrues from.
 *
 * Lifted out of StreamingController::heartbeat() so /api/v1 can send the same
 * beat without a Laravel session or a CSRF token. Everything here was already
 * true of the web heartbeat; only the HTTP wrapper was removed.
 *
 * The $sessionKey is what identifies "this device". On the web it is the
 * Laravel session id; from the app it is the device UUID. active_streams is
 * keyed on it, so the cap, the device picker and the boot action all work for
 * app devices the moment they send a key — which is the whole reason the
 * plan calls it a client session key rather than a session id
 * (docs/plans/mobile-offline-app.md §4.2).
 *
 * Beats arrive every 15 seconds per playing device. At the scale this
 * platform runs at that is two writes and one event per beat against indexed
 * keys, which a single KVM 2 carries comfortably; if concurrent viewers ever
 * reach the low thousands this is the first thing to batch.
 */
class PlaybackBeatRecorder
{
    /**
     * @param  int       $position  Seconds into the title.
     * @param  int|null  $duration  Player-reported runtime, when it knows it.
     * @param  string|null $ip      Recorded on the accrual event for fraud bounds.
     */
    public function record(
        int $userId,
        Movie|Episode $content,
        int $position,
        ?int $duration,
        string $sessionKey,
        ?string $ip = null,
    ): BeatOutcome {
        // Kicked-device check. active_streams is keyed on
        // (user, session, title), so a second device playing the same title
        // cannot steal this row - each device has its own. If the picker set
        // terminated_at on this (user, session), the flag is here and the
        // beat stops rather than refreshing the stream.
        $existing = ActiveStream::query()
            ->where('user_id', $userId)
            ->where('session_id', $sessionKey)
            ->where('watchable_type', $content->getMorphClass())
            ->where('watchable_id', $content->getKey())
            ->first();

        if ($existing && $existing->terminated_at !== null) {
            return BeatOutcome::terminated();
        }

        // watch_history owns resume position and view count, keyed per
        // (user, title) and session-agnostic. active_streams owns the live
        // session signal that the cap, the picker and the kick flow read.
        $row = WatchHistoryItem::record(
            userId: $userId,
            item: $content,
            position: $position,
            duration: $duration,
            sessionId: $sessionKey,
        );

        ActiveStream::markBeat($userId, $sessionKey, $content);

        // Monetization accrual and any future analytics hang off this event
        // rather than off a controller. The listener is fully exception-
        // guarded, so playback can never break on an accrual bug.
        event(new PlaybackBeat(
            userId: $userId,
            item: $content,
            position: $position,
            sessionId: $sessionKey,
            ip: $ip,
        ));

        return BeatOutcome::recorded($row->position_seconds, (bool) $row->completed);
    }
}
