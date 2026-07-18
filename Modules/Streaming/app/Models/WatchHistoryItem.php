<?php

namespace Modules\Streaming\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;

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
        'session_id',
        'last_beat_at',
        'terminated_at',
    ];

    protected $casts = [
        'completed' => 'bool',
        'watched_at' => 'datetime',
        'last_beat_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    /**
     * How close to the end we consider "completed", in seconds. Credits
     * rolling etc. — users rarely sit through the literal last frame.
     */
    public const COMPLETION_TOLERANCE_SECONDS = 10;

    /**
     * How many distinct titles the Continue Watching row holds. Because
     * users routinely bail during the end-credits (so the row is never
     * marked completed), in-progress rows would otherwise pile up forever.
     * We keep only the most-recent N and drop the rest — a title is one
     * movie, or one whole series (all its in-progress episodes count as a
     * single slot, matching how the row de-dupes shows into one card).
     */
    public const CONTINUE_WATCHING_LIMIT = 5;

    /**
     * A row with a last_beat_at newer than this is counted as an
     * "active stream" for concurrency gating. Tuned to ~2× the
     * player's heartbeat cadence so a single dropped beat doesn't
     * kick a real user off.
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
    public static function record(int $userId, Model $item, int $position, ?int $duration = null, ?string $sessionId = null): self
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
                'session_id' => $sessionId,
                'last_beat_at' => now(),
            ],
        );

        if ($history->wasRecentlyCreated) {
            self::incrementViewCount($item);

            // A brand-new in-progress title just entered Continue Watching.
            // That's the only moment the distinct-title count can grow, so
            // it's the only moment we need to prune — cheap, and it keeps
            // the row from ballooning with half-watched titles over time.
            if (!$history->completed) {
                self::pruneContinueWatching($userId);
            }
        }

        self::backfillRuntime($item, $duration);

        return $history;
    }

    /**
     * Keep the user's Continue Watching row down to CONTINUE_WATCHING_LIMIT
     * distinct titles, newest first, deleting the in-progress rows of any
     * older title (and any orphaned rows whose content was removed).
     *
     * De-duped exactly like the on-screen row: every in-progress episode of
     * one show shares a single slot, so bingeing a series can't crowd out
     * five separate movies. Only fires when a new title is added, so the
     * heartbeat hot path stays untouched for the common case.
     */
    public static function pruneContinueWatching(int $userId, int $keep = self::CONTINUE_WATCHING_LIMIT): void
    {
        $rows = static::where('user_id', $userId)
            ->where('completed', false)
            ->orderByDesc('watched_at')
            ->with(['watchable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([Episode::class => ['season']]);
            }])
            ->take(200)
            ->get();

        $keptKeys = [];
        $evict = [];

        foreach ($rows as $row) {
            $key = self::continueWatchingKey($row);

            if ($key === null) {
                $evict[] = $row;          // content deleted — drop the orphan
                continue;
            }

            if (in_array($key, $keptKeys, true)) {
                continue;                 // same title already kept (e.g. another episode)
            }

            if (count($keptKeys) >= $keep) {
                $evict[] = $row;          // beyond the cap — this older title goes
                continue;
            }

            $keptKeys[] = $key;
        }

        // Delete per-model (not a bulk query) so PersonalisationCacheObserver
        // fires and the row updates immediately rather than on cache TTL.
        foreach ($evict as $row) {
            $row->delete();
        }
    }

    /**
     * The Continue Watching de-dupe key for a row: a movie is its own slot;
     * every episode of a show collapses onto that show's slot. Null when the
     * underlying content no longer exists.
     */
    private static function continueWatchingKey(self $row): ?string
    {
        $w = $row->watchable;

        if ($w instanceof Movie) {
            return 'movie:' . $w->getKey();
        }

        if ($w instanceof Episode) {
            $showId = $w->season?->show_id;

            return $showId ? 'show:' . $showId : 'episode:' . $w->getKey();
        }

        return null;
    }

    // Concurrent-stream tracking lives on the `active_streams` table
    // and `Modules\Streaming\app\Models\ActiveStream` now — see that
    // model for activeCount / activeFor / terminateSession / reviveSession.
    // watch_history stays the source of truth for resume position,
    // view counts, and long-term history. The `session_id` /
    // `last_beat_at` / `terminated_at` columns here are legacy and
    // ignored by the concurrency flow; they're kept around so the
    // migration down-path doesn't destroy existing rows.

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
