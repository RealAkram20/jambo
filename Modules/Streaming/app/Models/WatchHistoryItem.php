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
     */
    public static function record(int $userId, Model $item, int $position, ?int $duration = null): self
    {
        $completed = $duration !== null && $position >= ($duration - self::COMPLETION_TOLERANCE_SECONDS);

        return static::updateOrCreate(
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
    }
}
