<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NewCommentPosted
{
    use Dispatchable;

    public function __construct(
        public readonly int $commentId,
        public readonly string $commenterUsername,
        public readonly string $contentTitle,
        public readonly ?string $excerpt = null,
    ) {
    }
}
