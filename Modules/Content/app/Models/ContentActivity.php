<?php

namespace Modules\Content\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One append-only row per admin action on content. See the migration for
 * why this exists (activity feed + delete-proof payout source of truth).
 *
 * Written only through record(); never updated. `content_type` is a short
 * slug (movie/show/season/episode) rather than a class so the log stays
 * readable and queryable without touching the model layer.
 */
class ContentActivity extends Model
{
    protected $table = 'content_activity_log';
    public $timestamps = false; // only created_at, stamped in record()

    protected $fillable = [
        'actor_id', 'actor_name', 'action',
        'content_type', 'content_id', 'content_title', 'meta', 'created_at',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';

    /**
     * Append a row for $action on content $model. The acting admin is the
     * authenticated user; system/console changes (no auth) are skipped so
     * automated writes (e.g. runtime re-probes, seeders) don't pollute the
     * feed or credit anyone. $model must expose activityType()/
     * activityTitle()/activityMeta() (the TracksContentActivity trait).
     */
    public static function record(string $action, Model $model): void
    {
        $actor = auth()->user();
        if (!$actor) {
            return; // no human actor → not an admin performance event
        }

        static::create([
            'actor_id'      => $actor->id,
            'actor_name'    => trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) ?: $actor->username,
            'action'        => $action,
            'content_type'  => $model->activityType(),
            'content_id'    => $model->getKey(),
            'content_title' => $model->activityTitle(),
            'meta'          => $model->activityMeta(),
            'created_at'    => now(),
        ]);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
