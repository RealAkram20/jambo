<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ShowAdded
{
    use Dispatchable;

    public function __construct(
        public readonly int $showId,
        public readonly string $showTitle,
        public readonly ?string $showSlug = null,
        public readonly ?string $poster = null,
    ) {
    }
}
