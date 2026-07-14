<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $colour
 * @property ?string $photo_url
 * @property ?string $description
 * @property ?string $youtube_url
 * @property ?string $tiktok_url
 * @property ?string $facebook_url
 * @property ?string $instagram_url
 * @property ?string $website_url
 */
class Vj extends Model
{
    protected $table = 'vjs';

    protected $fillable = [
        'name',
        'slug',
        'colour',
        'photo_url',
        'description',
        // Feed schema.org `sameAs` on the Person node — see
        // Modules\Seo\app\Support\StructuredData::vjPerson().
        'youtube_url',
        'tiktok_url',
        'facebook_url',
        'instagram_url',
        'website_url',
    ];

    /**
     * The social URLs, in the order they should be shown. Keyed by column so
     * both the admin form and the public bio card can iterate one list instead
     * of each hard-coding the same five fields.
     */
    public const SOCIAL_FIELDS = [
        'youtube_url'   => 'YouTube',
        'tiktok_url'    => 'TikTok',
        'facebook_url'  => 'Facebook',
        'instagram_url' => 'Instagram',
        'website_url'   => 'Website',
    ];

    /**
     * The VJ's name as it should be written for a human — and for Google.
     *
     * The `name` column holds values like "Vj Junior" / "vj junior", and the
     * cards render them through CSS `text-transform: capitalize`, which fixes
     * the look but not the markup: a crawler reads the raw HTML, so it sees
     * "Vj Junior". The audience searches "VJ Junior", and the <title>/<h1>/
     * schema should say exactly that.
     *
     * Only the leading VJ token is normalised — the rest of the name is left
     * exactly as the admin typed it, because we cannot know whether "Ice P"
     * or "Heavy-K" is meant to be cased some other way.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) $this->name);

        return preg_replace('/^vj\b/i', 'VJ', $name) ?? $name;
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_vj');
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'show_vj');
    }

    /**
     * The monetization-program enrollment for this VJ, if any. A VJ
     * row is display credits; earning requires this link (Monetization
     * module). Nullable-safe when the module is disabled.
     */
    public function monetizationPartner(): HasOne
    {
        return $this->hasOne(\Modules\Monetization\app\Models\MonetizationPartner::class, 'vj_id');
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
