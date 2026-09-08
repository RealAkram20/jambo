<?php

namespace Modules\Content\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
 * Two shapes, because a rail of 30 posters should not carry 30 synopses:
 *   card()   - what a poster in a rail needs
 *   detail() - the full page
 */
class MovieResource extends JsonResource
{
    private bool $detailed = false;

    public static function card($resource): self
    {
        return new self($resource);
    }

    public static function detail($resource): self
    {
        $instance = new self($resource);
        $instance->detailed = true;

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

        if (! $this->detailed) {
            return $card;
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
