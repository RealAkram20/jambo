<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $colour
 * @property ?string $description
 */
class Vj extends Model
{
    protected $table = 'vjs';

    protected $fillable = [
        'name',
        'slug',
        'colour',
        'description',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_vj');
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'show_vj');
    }

    /**
     * Falls back through the VJ's most recent published movie / show
     * to produce a representative thumbnail. Mirrors Genre's accessor
     * so the homepage VJs slider can reuse the same card markup.
     * Returns whatever is stored on the record (relative "media/..."
     * path OR absolute URL) — callers OR with a placeholder.
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
}
