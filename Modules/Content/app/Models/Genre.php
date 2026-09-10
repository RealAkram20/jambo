<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Content\database\factories\GenreFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $colour
 */
class Genre extends Model
{
    use HasFactory;

    protected $table = 'genres';

    protected $fillable = [
        'name',
        'slug',
        'colour',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'genre_movie');
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'genre_show');
    }

    /**
     * Falls back through the most recent published content in this
     * genre to produce a representative thumbnail. Returns whatever
     * is stored on the record (relative "media/..." path OR absolute
     * URL) — callers rely on the card component to handle both forms.
     *
     * null when the genre has no published content yet; callers
     * should OR with a bundled placeholder.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        // Set by attachFeaturedImages() for a whole collection at once. Read
        // first, so a batched caller pays two queries for ten genres instead
        // of forty. Null is a real answer and is cached like any other.
        if (array_key_exists('featured_image_url', $this->attributes)) {
            return $this->attributes['featured_image_url'];
        }

        $fromMovie = $this->movies()->published()
            ->orderByDesc('published_at')
            ->value('backdrop_url')
            ?? $this->movies()->published()
                ->orderByDesc('published_at')
                ->value('poster_url');

        if ($fromMovie) {
            return $fromMovie;
        }

        return $this->shows()->published()
            ->orderByDesc('published_at')
            ->value('backdrop_url')
            ?? $this->shows()->published()
                ->orderByDesc('published_at')
                ->value('poster_url');
    }

    /**
     * Resolve every genre's thumbnail in two queries instead of four each.
     *
     * The accessor above walks four `value()` calls per genre — movie
     * backdrop, movie poster, show backdrop, show poster — so a ten-genre rail
     * costs up to forty queries, on the home page, on every request. That is
     * the exact shape the engineering standard calls an unbounded query, and
     * it was invisible because the accessor reads like a property.
     *
     * This asks the same question of the whole set at once: one pass over
     * published movies, one over published shows, both ordered oldest-first so
     * that assigning into the map leaves the NEWEST title's artwork as the
     * final value. Movies win over shows, and a backdrop wins over a poster,
     * which is the accessor's own precedence.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $genres
     */
    public static function attachFeaturedImages($genres): void
    {
        $ids = $genres->pluck('id')->filter()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $images = [];

        // Shows first, so a movie in the same genre overwrites them.
        foreach ([[Show::class, 'genre_show', 'show_id'], [Movie::class, 'genre_movie', 'movie_id']] as [$model, $pivot, $key]) {
            $rows = $model::published()
                ->join($pivot, $pivot . '.' . $key, '=', (new $model)->getTable() . '.id')
                ->whereIn($pivot . '.genre_id', $ids)
                ->orderBy('published_at')
                ->get([$pivot . '.genre_id as genre_id', 'backdrop_url', 'poster_url']);

            // Posters first, so a backdrop on the same title overwrites them.
            foreach (['poster_url', 'backdrop_url'] as $column) {
                foreach ($rows as $row) {
                    if (! empty($row->{$column})) {
                        $images[$row->genre_id] = $row->{$column};
                    }
                }
            }
        }

        foreach ($genres as $genre) {
            $genre->setAttribute('featured_image_url', $images[$genre->id] ?? null);
            // There is no such column. Syncing it out of the dirty set means a
            // later save() on one of these models cannot try to write it.
            $genre->syncOriginalAttribute('featured_image_url');
        }
    }

    protected static function newFactory(): GenreFactory
    {
        return GenreFactory::new();
    }
}
