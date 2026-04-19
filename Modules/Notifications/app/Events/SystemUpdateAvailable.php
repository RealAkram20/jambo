<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SystemUpdateAvailable
{
    use Dispatchable;

    public function __construct(
        public readonly string $version,
        public readonly ?string $releaseNotesUrl = null,
    ) {
    }
}
