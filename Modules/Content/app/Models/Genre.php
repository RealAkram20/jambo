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

    protected static function newFactory(): GenreFactory
    {
        return GenreFactory::new();
    }
}
