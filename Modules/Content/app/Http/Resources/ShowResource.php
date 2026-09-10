<?php

namespace Modules\Content\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Content\app\Http\Resources\Concerns\RendersHeroFields;

/**
 * A series, as the app is allowed to see it. Allow-listed for the same reason
 * MovieResource is: nothing that could become a file URL leaves here.
 *
 * `tier_required` on a show is what its episodes inherit when they carry none
 * of their own, which is the normal case — so the app can draw the lock badge
 * on a series card from this field alone, exactly as PlaybackAuthorizer
 * resolves it server-side.
 *
 * Four shapes, matching MovieResource: card(), hero(), banner(), detail().
 */
class ShowResource extends JsonResource
{
    use RendersHeroFields;

    private const CARD = 'card';

    private const HERO = 'hero';

    private const BANNER = 'banner';

    private const DETAIL = 'detail';

    private string $shape = self::CARD;

    public static function card($resource): self
    {
        return new self($resource);
    }

    /** The home banner. See MovieResource for why this is not detail(). */
    public static function hero($resource): self
    {
        $instance = new self($resource);
        $instance->shape = self::HERO;

        return $instance;
    }

    /** One slide of the Top 10 Series of the Day. See MovieResource::banner. */
    public static function banner($resource, int $rank): self
    {
        $instance = new self($resource);
        $instance->shape = self::BANNER;
        $instance->rank = $rank;

        return $instance;
    }

    public static function detail($resource): self
    {
        $instance = new self($resource);
        $instance->shape = self::DETAIL;

        return $instance;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $card = [
            'type' => 'series',
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'year' => $this->year,
            'rating' => $this->rating,
            'poster_url' => media_url($this->poster_url),
            'tier_required' => $this->tier_required,
        ];

        if ($this->shape === self::CARD) {
            return $card;
        }

        if ($this->shape === self::HERO) {
            return $card + [
                'backdrop_url' => media_url($this->backdrop_url ?: $this->poster_url),
                'synopsis' => $this->synopsis,
                'genres' => $this->taxonomy('genres'),
                'tags' => $this->taxonomy('tags'),
                'cast' => $this->heroCast(),
                // The banner's badge for a series is "N season" where a
                // movie's is its certification, and the thumbnail strip under
                // the banner uses the same count as its meta line. Both hero
                // paths eager-load `seasons` already — FeaturedItem::heroItems
                // says why, and it is not for this.
                'seasons_count' => $this->whenLoaded('seasons', fn () => $this->seasons->count()),
                // The banner's clock line.
                //
                // ⚠️ Deliberately NOT the website's number. hero-banner.blade
                // takes `seasons->flatMap->episodes->first()->runtime_minutes`
                // — the first episode of whichever season loaded first, found
                // by walking every episode of the series into memory on a
                // relation nobody eager-loaded. This is the mean episode
                // length instead: one aggregate for the whole banner, and a
                // number that describes the series rather than one arbitrary
                // episode of it. Null when the aggregate was not loaded, which
                // draws no clock line, exactly as a null runtime does on the
                // website.
                'episode_runtime_minutes' => $this->meanEpisodeRuntime(),
            ];
        }

        if ($this->shape === self::BANNER) {
            return $card + $this->bannerFields() + [
                'backdrop_url' => media_url($this->backdrop_url ?: $this->poster_url),
                'synopsis' => $this->synopsis,
                // The tab slider's meta line is "May 2024 · 2 Seasons", so
                // this shape carries a date where the hero's does not.
                //
                // The blade reads `published_at ?? created_at`. There is no
                // fallback here because there cannot be one: every title in
                // this shelf came through `Show::published()`, which already
                // requires a non-null `published_at` at or before now. A
                // fallback would be a branch no data can take.
                'published_at' => optional($this->published_at)->toIso8601String(),
                'seasons_count' => $this->whenLoaded('seasons', fn () => $this->seasons->count()),
            ];
        }

        return $card + [
            'synopsis' => $this->synopsis,
            'backdrop_url' => media_url($this->backdrop_url),
            'trailer_url' => $this->trailer_url,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'views_count' => $this->views_count,
            'genres' => $this->whenLoaded('genres', fn () => $this->genres->map(fn ($g) => [
                'slug' => $g->slug,
                'name' => $g->name,
            ])->values()),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'slug' => $c->slug,
                'name' => $c->name,
            ])->values()),
            'cast' => $this->whenLoaded('cast', fn () => $this->cast->map(fn ($p) => [
                'slug' => $p->slug,
                'name' => $p->full_name,
                'photo_url' => media_url($p->photo_url),
            ])->values()),
            'seasons' => SeasonResource::collection($this->whenLoaded('seasons')),
        ];
    }
}
