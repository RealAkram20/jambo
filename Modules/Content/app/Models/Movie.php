<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Content\app\Models\Concerns\HasStreamSource;
use Modules\Content\database\factories\MovieFactory;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property ?string $synopsis
 * @property ?int $year
 * @property ?int $runtime_minutes
 * @property ?string $rating
 * @property ?string $poster_url
 * @property ?string $backdrop_url
 * @property ?string $trailer_url
 * @property ?string $dropbox_path
 * @property ?string $video_url
 * @property ?string $tier_required
 * @property string $status
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property int $views_count
 */
class Movie extends Model
{
    use HasFactory;
    use HasStreamSource;

    protected $table = 'movies';

    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'year',
        'runtime_minutes',
        'rating',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'dropbox_path',
        'video_url',
        'tier_required',
        'status',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'year' => 'integer',
        'runtime_minutes' => 'integer',
        'views_count' => 'integer',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_movie');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_movie');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'movie_tag');
    }

    public function cast(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'movie_person')
            ->withPivot(['role', 'character_name', 'display_order']);
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

    protected static function newFactory(): MovieFactory
    {
        return MovieFactory::new();
    }
}
