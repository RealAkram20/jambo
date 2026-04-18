<?php

namespace Modules\Streaming\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $watchable_type
 * @property int $watchable_id
 * @property int $position_seconds
 * @property ?int $duration_seconds
 * @property bool $completed
 * @property \Illuminate\Support\Carbon $watched_at
 */
class WatchHistoryItem extends Model
{
    use HasFactory;

    protected $table = 'watch_history';

    protected $fillable = [
        'user_id',
        'watchable_type',
        'watchable_id',
        'position_seconds',
        'duration_seconds',
        'completed',
        'watched_at',
    ];

    protected $casts = [
        'completed' => 'bool',
        'watched_at' => 'datetime',
    ];

    /**
     * How close to the end we consider "completed", in seconds. Credits
     * rolling etc. — users rarely sit through the literal last frame.
     */
    public const COMPLETION_TOLERANCE_SECONDS = 10;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function watchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Integer progress percentage, capped at 100. Returns 0 if we don't
     * yet know the asset's total duration.
     */
    public function progressPercent(): int
    {
        if (! $this->duration_seconds || $this->duration_seconds <= 0) {
            return 0;
        }

        $pct = (int) round(($this->position_seconds / $this->duration_seconds) * 100);

        return min($pct, 100);
    }

    /**
     * Upsert a playback heartbeat for (user, item). Marks the row completed
     * when a duration is known and the user is within the completion
     * tolerance of the end.
     *
     * Real view accounting: a new row (first heartbeat for this user on
     * this item) counts as +1 view on the parent content. Subsequent
     * heartbeats only update position, so the counter can't be inflated
     * by re-opening the page.
     */
    public static function record(int $userId, Model $item, int $position, ?int $duration = null): self
    {
        $completed = $duration !== null && $position >= ($duration - self::COMPLETION_TOLERANCE_SECONDS);

        $history = static::updateOrCreate(
            [
                'user_id' => $userId,
                'watchable_type' => $item->getMorphClass(),
                'watchable_id' => $item->getKey(),
            ],
            [
                'position_seconds' => $position,
                'duration_seconds' => $duration,
                'completed' => $completed,
                'watched_at' => now(),
            ],
        );

        if ($history->wasRecentlyCreated) {
            self::incrementViewCount($item);
        }

        self::backfillRuntime($item, $duration);

        return $history;
    }

    /**
     * Opportunistically populate `runtime_minutes` on the content
     * record from the client-reported `duration` (pulled straight off
     * <video>.duration). Fires on every heartbeat but only writes
     * when:
     *   • the current `runtime_minutes` is null / 0 (never overwrite
     *     an admin-set value),
     *   • the reported duration is a sane finite number between 30s
     *     and 10h (rejects Infinity / negative / obvious garbage).
     *
     * Cheap no-op once populated, so leaving this on every heartbeat
     * is fine.
     */
    private static function backfillRuntime(Model $item, ?int $duration): void
    {
        if (!$duration || $duration < 30 || $duration > 36000) {
            return;
        }

        $minutes = (int) round($duration / 60);

        if ($item instanceof \Modules\Content\app\Models\Movie) {
            \Modules\Content\app\Models\Movie::whereKey($item->getKey())
                ->where(fn ($q) => $q->whereNull('runtime_minutes')->orWhere('runtime_minutes', 0))
                ->update(['runtime_minutes' => $minutes]);
            return;
        }

        if ($item instanceof \Modules\Content\app\Models\Episode) {
            \Modules\Content\app\Models\Episode::whereKey($item->getKey())
                ->where(fn ($q) => $q->whereNull('runtime_minutes')->orWhere('runtime_minutes', 0))
                ->update(['runtime_minutes' => $minutes]);
        }
    }

    /**
     * Bump the viewer counter on the parent content. Movies carry their
     * own views_count column; episodes don't, so we credit the show.
     * Uses a direct query increment so it's atomic and doesn't fire
     * model events (no accidental timestamp churn).
     */
    private static function incrementViewCount(Model $item): void
    {
        if ($item instanceof \Modules\Content\app\Models\Movie) {
            \Modules\Content\app\Models\Movie::whereKey($item->getKey())->increment('views_count');
            return;
        }

        if ($item instanceof \Modules\Content\app\Models\Episode) {
            $showId = $item->season?->show_id;
            if ($showId) {
                \Modules\Content\app\Models\Show::whereKey($showId)->increment('views_count');
            }
        }
    }
}
