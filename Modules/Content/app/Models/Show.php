<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Modules\Content\app\Models\Concerns\CleansContentMorphsOnDelete;
use Modules\Content\database\factories\ShowFactory;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?string $synopsis
 * @property ?int $year
 * @property ?string $rating
 * @property ?string $poster_url
 * @property ?string $backdrop_url
 * @property ?string $trailer_url
 * @property ?string $tier_required
 * @property string $status
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property int $views_count
 */
class Show extends Model
{
    use HasFactory;
    use CleansContentMorphsOnDelete;

    protected static function booted(): void
    {
        static::deleting(function (Show $show) {
            // Episodes go away with the show via the FK cascade
            // (seasons.show_id, episodes.season_id). The cascade
            // doesn't fire Eloquent events, so the descendants'
            // morph references have to be wiped here BEFORE the
            // show row is removed and the cascade kicks in.
            $episodeIds = DB::table('episodes')
                ->join('seasons', 'episodes.season_id', '=', 'seasons.id')
                ->where('seasons.show_id', $show->id)
                ->pluck('episodes.id')
                ->all();

            if (!empty($episodeIds)) {
                self::cleanContentMorphsFor(Episode::class, $episodeIds);
            }
            self::cleanContentMorphsFor(self::class, $show->id);
        });
    }

    protected $table = 'shows';

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'year',
        'rating',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'tier_required',
        'status',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'year' => 'integer',
        'views_count' => 'integer',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_UPCOMING = 'upcoming';

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_show');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_show');
    }

    public function vjs(): BelongsToMany
    {
        return $this->belongsToMany(Vj::class, 'show_vj');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'show_tag');
    }

    public function cast(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'show_person')
            ->withPivot(['role', 'character_name', 'display_order']);
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class)->orderBy('number');
    }

    public function episodes(): HasManyThrough
    {
        return $this->hasManyThrough(Episode::class, Season::class);
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
        return $q->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Announced / scheduled series — visible in the Upcoming rail only.
     * `published_at` here is the intended release date (may be null if
     * the admin hasn't picked one yet).
     */
    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_UPCOMING);
    }

    /**
     * Series whose detail page is publicly reachable: Published OR
     * Upcoming. See Movie::scopeDetailVisible for rationale — the
     * page must load for upcoming rail clicks, but streaming still
     * gates on `published()`.
     */
    public function scopeDetailVisible(Builder $q): Builder
    {
        return $q->where(function ($outer) {
            $outer->where(function ($pub) {
                $pub->where('status', self::STATUS_PUBLISHED)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            })->orWhere('status', self::STATUS_UPCOMING);
        });
    }

    protected static function newFactory(): ShowFactory
    {
        return ShowFactory::new();
    }
}
