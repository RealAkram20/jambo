<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WatchlistAvailable
{
    use Dispatchable;

    /**
     * @param  array<int>  $userIds  users who watchlisted this item
     */
    public function __construct(
        public readonly array $userIds,
        public readonly string $title,
        public readonly string $kind = 'movie',
        public readonly ?string $slug = null,
        public readonly ?string $poster = null,
    ) {
    }
}
