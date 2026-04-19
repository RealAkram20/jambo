<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ContentReported
{
    use Dispatchable;

    public function __construct(
        public readonly string $contentType,
        public readonly int $contentId,
        public readonly string $contentTitle,
        public readonly string $reporterUsername,
        public readonly ?string $reasonText = null,
    ) {
    }
}
