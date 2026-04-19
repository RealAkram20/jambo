<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fire this event right after a movie is published / flipped to "active".
 * Example in an admin controller:
 *
 *   event(new MovieAdded($movie->id, $movie->title, $movie->slug, $movie->poster_url));
 */
class MovieAdded
{
    use Dispatchable;

    public function __construct(
        public readonly int $movieId,
        public readonly string $movieTitle,
        public readonly ?string $movieSlug = null,
        public readonly ?string $poster = null,
    ) {
    }
}
