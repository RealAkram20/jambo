<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Content\app\Models\Concerns\TracksContentActivity;
use Modules\Content\database\factories\SeasonFactory;

/**
 * @property int $id
 * @property int $show_id
 * @property int $number
 * @property ?string $title
 * @property ?string $synopsis
 * @property ?string $poster_url
 * @property ?\Illuminate\Support\Carbon $released_at
 */
class Season extends Model
{
    use HasFactory;
    use TracksContentActivity;

    public function activityType(): string
    {
        return 'season';
    }

    public function activityTitle(): string
    {
        $showTitle = $this->show?->title ?? 'Show';
        return $showTitle . ' — Season ' . ($this->number ?? '?');
    }

    public function activityMeta(): ?array
    {
        return ['show_id' => $this->show_id, 'season_number' => $this->number];
    }

    protected $table = 'seasons';

    protected $fillable = [
        'show_id',
        'number',
        'title',
        'synopsis',
        'poster_url',
        'released_at',
    ];

    protected $casts = [
        'released_at' => 'date',
        'announced_at' => 'datetime',
        'number' => 'integer',
    ];

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('number');
    }

    /**
     * A season has no page of its own — users reach it through the show.
     * So it is only "publicly visible" once the show is live AND at least
     * one of its episodes has actually dropped. A freshly created, empty
     * season is nothing a user can watch, which is why announcing one on
     * create (the old SeasonController::store behaviour) sent people to a
     * dead end.
     */
    public function isPubliclyVisible(): bool
    {
        $show = $this->relationLoaded('show') ? $this->show : $this->show()->first();

        return $show !== null
            && $show->isPubliclyAvailable()
            && $this->episodes()->published()->exists();
    }

    protected static function newFactory(): SeasonFactory
    {
        return SeasonFactory::new();
    }
}
