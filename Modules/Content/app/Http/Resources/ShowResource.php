<?php

namespace Modules\Content\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A series, as the app is allowed to see it. Allow-listed for the same reason
 * MovieResource is: nothing that could become a file URL leaves here.
 *
 * `tier_required` on a show is what its episodes inherit when they carry none
 * of their own, which is the normal case — so the app can draw the lock badge
 * on a series card from this field alone, exactly as PlaybackAuthorizer
 * resolves it server-side.
 */
class ShowResource extends JsonResource
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
            'type' => 'series',
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'year' => $this->year,
            'rating' => $this->rating,
            'poster_url' => media_url($this->poster_url),
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
            'cast' => $this->whenLoaded('cast', fn () => $this->cast->map(fn ($p) => [
                'slug' => $p->slug,
                'name' => $p->full_name,
                'photo_url' => media_url($p->photo_url),
            ])->values()),
            'seasons' => SeasonResource::collection($this->whenLoaded('seasons')),
        ];
    }
}
