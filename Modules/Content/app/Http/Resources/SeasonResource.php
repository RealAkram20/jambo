<?php

namespace Modules\Content\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A season, and its episodes when they have been loaded.
 *
 * Seasons carry no tier of their own — gating lives on the show, and an
 * episode may override it. Nothing here is gated, so nothing here is secret.
 */
class SeasonResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'synopsis' => $this->synopsis,
            'poster_url' => media_url($this->poster_url),
            'episodes' => EpisodeResource::collection($this->whenLoaded('episodes')),
        ];
    }
}
