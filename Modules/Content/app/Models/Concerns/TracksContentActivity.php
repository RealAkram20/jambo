<?php

namespace Modules\Content\app\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\app\Models\ContentActivity;

/**
 * Stamps created_by / updated_by and writes the append-only
 * content_activity_log for a content model (Movie, Show, Season,
 * Episode).
 *
 * Uses a trait boot hook (bootTracksContentActivity) so it composes with
 * each model's existing booted() overrides instead of replacing them.
 * Each model must define activityType(); activityTitle()/activityMeta()
 * have sensible defaults a model can override.
 */
trait TracksContentActivity
{
    public static function bootTracksContentActivity(): void
    {
        static::creating(function ($model) {
            if ($id = auth()->id()) {
                $model->created_by ??= $id;
                $model->updated_by ??= $id;
            }
        });

        static::updating(function ($model) {
            if ($id = auth()->id()) {
                $model->updated_by = $id;
            }
        });

        static::created(fn ($model) => ContentActivity::record(ContentActivity::ACTION_CREATED, $model));
        static::updated(fn ($model) => ContentActivity::record(ContentActivity::ACTION_UPDATED, $model));
        static::deleted(fn ($model) => ContentActivity::record(ContentActivity::ACTION_DELETED, $model));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Short content-type slug used in the activity log and payout
     * counting. Each model MUST override this.
     */
    abstract public function activityType(): string;

    /**
     * Human-readable title snapshot for the log. Default reads `title`;
     * Season overrides (it has no standalone title of note).
     */
    public function activityTitle(): string
    {
        return (string) ($this->title ?? ('#' . $this->getKey()));
    }

    /**
     * Extra context stored on the log row (e.g. an episode's parent show).
     * Default: none. Season/Episode override to record their parent.
     *
     * @return array<string, mixed>|null
     */
    public function activityMeta(): ?array
    {
        return null;
    }
}
