<?php

namespace Modules\Streaming\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Live per-device streaming session for the concurrent-streams cap.
 *
 * Keyed on (user_id, session_id, watchable_type, watchable_id) so
 * two devices watching the same title produce two rows instead of
 * ping-ponging one. That property is what the device-limit picker's
 * "boot" action depends on: flagging terminated_at on a specific
 * (user, session) set stays flagged; another device's heartbeat
 * can't accidentally clear it.
 *
 * watch_history remains the source of truth for resume position,
 * view counts, and long-term history — those don't care about the
 * session dimension.
 */
class ActiveStream extends Model
{
    protected $table = 'active_streams';

    protected $fillable = [
        'user_id',
        'session_id',
        'watchable_type',
        'watchable_id',
        'last_beat_at',
        'terminated_at',
    ];

    protected $casts = [
        'last_beat_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    /**
     * How close the last heartbeat must be for a row to count as
     * still "active" against the cap. Tuned to ~2× the player's
     * heartbeat cadence so one dropped beat doesn't false-kick a
     * real viewer.
     */
    public const STREAM_IDLE_SECONDS = 90;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function watchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Upsert a live heartbeat for (user, session, title). Returns the
     * row so the caller can inspect `terminated_at` in the same
     * request cycle. Does NOT clear terminated_at on its own — a
     * booted session must go through reclaim() to resume, otherwise
     * every heartbeat would un-kick the device.
     */
    public static function markBeat(int $userId, string $sessionId, Model $content): self
    {
        $row = static::query()
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->where('watchable_type', $content->getMorphClass())
            ->where('watchable_id', $content->getKey())
            ->first();

        if ($row) {
            // Only bump last_beat_at; preserve terminated_at so a
            // kicked session stays kicked across its own heartbeats.
            $row->last_beat_at = now();
            $row->save();
            return $row;
        }

        return static::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'watchable_type' => $content->getMorphClass(),
            'watchable_id' => $content->getKey(),
            'last_beat_at' => now(),
            'terminated_at' => null,
        ]);
    }

    /**
     * How many distinct sessions for this user are currently streaming
     * premium content. Optionally excludes a session id — call with
     * the current session when asking "how many OTHER devices are
     * streaming right now".
     *
     * "Premium" is derived by joining through the polymorphic target
     * and requiring tier_required IS NOT NULL — free content doesn't
     * count against the cap.
     */
    public static function activeCount(int $userId, ?string $excludeSessionId = null): int
    {
        $q = static::query()
            ->where('user_id', $userId)
            ->where('last_beat_at', '>', now()->subSeconds(self::STREAM_IDLE_SECONDS))
            ->whereNull('terminated_at')
            ->whereHasMorph(
                'watchable',
                [
                    \Modules\Content\app\Models\Movie::class,
                    \Modules\Content\app\Models\Episode::class,
                ],
                fn ($mq) => $mq->whereNotNull('tier_required')
            );

        if ($excludeSessionId !== null) {
            $q->where('session_id', '!=', $excludeSessionId);
        }

        return $q->distinct('session_id')->count('session_id');
    }

    /**
     * Hydrated list of the user's currently-active premium streams
     * for the device-limit picker. One entry per distinct session
     * (the most recent beating row for each session, so if the same
     * device is mid-title-change we still only show one device).
     *
     * Each row gets two virtual attributes populated from the
     * Laravel `sessions` table so the picker can render
     * "Chrome on Windows · Kampala · 2 min ago":
     *   • session_ip
     *   • session_user_agent
     */
    public static function activeFor(int $userId): Collection
    {
        $rows = static::query()
            ->where('user_id', $userId)
            ->where('last_beat_at', '>', now()->subSeconds(self::STREAM_IDLE_SECONDS))
            ->whereNull('terminated_at')
            ->whereHasMorph(
                'watchable',
                [
                    \Modules\Content\app\Models\Movie::class,
                    \Modules\Content\app\Models\Episode::class,
                ],
                fn ($mq) => $mq->whereNotNull('tier_required')
            )
            ->with('watchable')
            ->orderByDesc('last_beat_at')
            ->get()
            ->unique('session_id')
            ->values();

        $sessionIds = $rows->pluck('session_id')->filter()->unique()->values();

        $sessions = $sessionIds->isEmpty()
            ? collect()
            : DB::table('sessions')->whereIn('id', $sessionIds)->get()->keyBy('id');

        return $rows->map(function ($row) use ($sessions) {
            $s = $sessions->get($row->session_id);
            $row->session_ip = $s?->ip_address;
            $row->session_user_agent = $s?->user_agent;
            return $row;
        });
    }

    /**
     * Flag every active stream for (user, session) as terminated.
     * Called from the device-limit picker's boot action. Returns the
     * number of rows actually terminated.
     */
    public static function terminateSession(int $userId, string $sessionId): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->whereNull('terminated_at')
            ->update(['terminated_at' => now()]);
    }

    /**
     * Clear termination on (user, session). Used by the "take back
     * here" reclaim flow so a kicked device can resume after its
     * owner asks for the stream back.
     */
    public static function reviveSession(int $userId, string $sessionId): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->whereNotNull('terminated_at')
            ->update(['terminated_at' => null]);
    }
}
