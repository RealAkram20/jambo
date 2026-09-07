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

    /**
     * Display name of the admin who created this row, for the "Created by"
     * column on the admin lists.
     *
     * Three sources, in order of trust:
     *   1. the `creator` relation (created_by → users), the live record;
     *   2. `creator_label_snapshot`, the append-only activity log's
     *      actor_name — the only source left once the admin's user row is
     *      deleted, since created_by is ON DELETE SET NULL;
     *   3. null, which the badge renders as an em dash. Content added
     *      before authorship tracking (2026_07_13 migration) and anything
     *      written by a seeder or a console command has no actor at all.
     *      Never guess one: a wrong name here is a credit — and, via the
     *      Performance dashboard, a payout — assigned to the wrong person.
     *
     * Name shape matches ContentActivity::record() so a live name and a
     * snapshot of the same person read identically.
     */
    public function creatorLabel(): ?string
    {
        if ($this->creator) {
            $name = trim(($this->creator->first_name ?? '') . ' ' . ($this->creator->last_name ?? ''));

            return $name ?: $this->creator->username;
        }

        return $this->creator_label_snapshot ?: null;
    }

    /**
     * Fills `creator_label_snapshot` on rows whose creator is gone or was
     * never recorded, from the activity log. One query for the whole page
     * — call it once on a paginator, never per row.
     */
    public static function hydrateCreatorLabels(iterable $models): void
    {
        $needing = [];
        foreach ($models as $model) {
            if (!$model->creator) {
                $needing[$model->getKey()] = $model;
            }
        }

        if (!$needing) {
            return;
        }

        $names = ContentActivity::query()
            ->where('action', ContentActivity::ACTION_CREATED)
            ->where('content_type', (new static)->activityType())
            ->whereIn('content_id', array_keys($needing))
            ->whereNotNull('actor_name')
            ->orderBy('id') // earliest 'created' row wins if one ever repeats
            ->pluck('actor_name', 'content_id');

        foreach ($needing as $id => $model) {
            $model->creator_label_snapshot = $names[$id] ?? null;
        }
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
