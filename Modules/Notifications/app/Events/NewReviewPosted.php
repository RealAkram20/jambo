<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NewReviewPosted
{
    use Dispatchable;

    public function __construct(
        public readonly int $reviewId,
        public readonly string $reviewerUsername,
        public readonly string $contentTitle,
        public readonly int $rating = 0,
    ) {
    }
}
