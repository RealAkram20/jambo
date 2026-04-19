<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SeasonAdded
{
    use Dispatchable;

    public function __construct(
        public readonly string $showTitle,
        public readonly int $seasonNumber,
        public readonly ?string $showSlug = null,
        public readonly ?string $poster = null,
    ) {
    }
}
