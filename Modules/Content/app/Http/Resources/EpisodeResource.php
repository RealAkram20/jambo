<?php

namespace Modules\Content\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One episode. Allow-listed, so video_url and video_url_low stay behind
 * POST /playback/sessions where the entitlement check is.
 *
 * `tier_required` is the episode's OWN value and is usually null, because the
 * admin Shows form sets the plan on the series. The app must not read a null
 * here as "free" — that exact mistake is what let premium episodes stream for
 * nothing before 1.8.22. `effective_tier_required` is the resolved answer,
 * computed the same way the server gates it, and is the field to draw a lock
 * from.
 */
class EpisodeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'episode',
            'id' => $this->id,
            'season_id' => $this->season_id,
            'number' => $this->number,
            'title' => $this->title,
            'synopsis' => $this->synopsis,
            'runtime_minutes' => $this->runtime_minutes,
            'still_url' => media_url($this->still_url),
            'published_at' => optional($this->published_at)->toIso8601String(),

            // The episode's own column: usually null. Kept so a client can
            // tell "overridden" from "inherited" if it ever needs to.
            'tier_required' => $this->tier_required,

            // What actually governs playback. Draw the lock from this one.
            'effective_tier_required' => app(\Modules\Streaming\app\Services\PlaybackAuthorizer::class)
                ->requiredTierSlug($this->resource),
        ];
    }
}
