<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Content\app\Models\Concerns\HasStreamSource;
use Modules\Content\database\factories\EpisodeFactory;

/**
 * @property int $id
 * @property int $season_id
 * @property int $number
 * @property string $title
 * @property ?string $synopsis
 * @property ?int $runtime_minutes
 * @property ?string $still_url
 * @property ?string $dropbox_path
 * @property ?string $video_url
 * @property ?string $tier_required
 * @property ?\Illuminate\Support\Carbon $published_at
 */
class Episode extends Model
{
    use HasFactory;
    use HasStreamSource;

    protected $table = 'episodes';

    protected $fillable = [
        'season_id',
        'number',
        'title',
        'synopsis',
        'runtime_minutes',
        'still_url',
        'dropbox_path',
        'video_url',
        'video_url_low',
        'tier_required',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'number' => 'integer',
        'runtime_minutes' => 'integer',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function show(): HasOneThrough
    {
        return $this->hasOneThrough(
            Show::class,
            Season::class,
            'id',         // Season PK referenced by Episode.season_id
            'id',         // Show PK referenced by Season.show_id
            'season_id',  // Episode column
            'show_id'     // Season column
        );
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'ratable');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    protected static function newFactory(): EpisodeFactory
    {
        return EpisodeFactory::new();
    }
}
