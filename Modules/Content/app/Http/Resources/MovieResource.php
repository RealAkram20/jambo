<?php

namespace Modules\Content\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Content\app\Http\Resources\Concerns\RendersHeroFields;

/**
 * A movie, as the app is allowed to see it.
 *
 * Every field is listed by hand. The engineering standard forbids spreading a
 * model into a response, and here it is not a style point: `video_url`,
 * `video_url_low` and `dropbox_path` sit on this model, and any of them in a
 * catalogue response would hand out the file the whole offline design exists
 * to protect. A viewer gets a playable URL from POST /playback/sessions,
 * after the entitlement check, and never from a listing.
 *
 * Four shapes, because a rail of 30 posters should not carry 30 synopses:
 *   card()   - what a poster in a rail needs
 *   hero()   - what the home banner needs, which is more than a card and far
 *              less than a page
 *   banner() - a slide of the Top 10 Movies of the Day, which is a hero minus
 *              the Tags and Starring lines, plus where it placed
 *   detail() - the full page
 *
 * `hero()` exists rather than reusing `detail()` because the home banner is on
 * the critical path of every first paint, and `detail()` carries fields the
 * banner never draws — `views_count`, `published_at`, `categories` and, on a
 * series, every season. What the banner actually renders is fixed by
 * `components/partials/hero-banner.blade.php`, and this shape is that list.
 *
 * `banner()` is a fourth shape rather than one more flag on `hero()` for the
 * same reason: `components/partials/vertical-banner.blade.php` draws neither
 * Tags nor Starring, so ten slides of a hero would be ten titles' worth of
 * cast and tag arrays nobody renders, on that same first paint. It carries
 * four genres where the hero carries three, because the blade takes four.
 */
class MovieResource extends JsonResource
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

    /** The home banner. See the class docblock for why this is not detail(). */
    public static function hero($resource): self
    {
        $instance = new self($resource);
        $instance->shape = self::HERO;

        return $instance;
    }

    /**
     * One slide of the Top 10 Movies of the Day.
     *
     * The rank is a parameter because it is a fact about the list, not about
     * the film: the same film is third today and seventh tomorrow, and the
     * only thing that knows which is the caller holding the ordered shelf.
     */
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
            'type' => 'movie',
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'year' => $this->year,
            'runtime_minutes' => $this->runtime_minutes,
            'rating' => $this->rating,
            'poster_url' => media_url($this->poster_url),
            // The plan slug this title needs, or null for free. The app reads
            // it to draw a lock badge without asking the server per card.
            'tier_required' => $this->tier_required,
        ];

        if ($this->shape === self::CARD) {
            return $card;
        }

        if ($this->shape === self::HERO) {
            return $card + [
                // The banner is a full-bleed backdrop with the poster as the
                // fallback, which is the same chain hero-banner.blade.php
                // walks. A hero drawn from a 5:7 poster is the thing this
                // shape exists to stop.
                'backdrop_url' => media_url($this->backdrop_url ?: $this->poster_url),
                'synopsis' => $this->synopsis,
                'genres' => $this->taxonomy('genres'),
                'tags' => $this->taxonomy('tags'),
                'cast' => $this->heroCast(),
            ];
        }

        if ($this->shape === self::BANNER) {
            return $card + $this->bannerFields() + [
                // The same fallback chain the vertical banner walks, and for
                // the same reason as the hero: the slide is a full-bleed
                // backdrop, and a 5:7 poster in that box is what this avoids.
                'backdrop_url' => media_url($this->backdrop_url ?: $this->poster_url),
                'synopsis' => $this->synopsis,
                // Four, not the hero's three. The blade takes four.
                'genres' => $this->taxonomy('genres', self::BANNER_TAXONOMY_LIMIT),
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
            // Person has no `name` column; full_name is the accessor the
            // website's own templates compose from first_name + last_name.
            'cast' => $this->whenLoaded('cast', fn () => $this->cast->map(fn ($p) => [
                'slug' => $p->slug,
                'name' => $p->full_name,
                'photo_url' => media_url($p->photo_url),
            ])->values()),
        ];
    }
}
