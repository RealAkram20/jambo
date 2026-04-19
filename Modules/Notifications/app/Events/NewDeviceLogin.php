<?php

namespace Modules\Notifications\app\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class NewDeviceLogin
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {
    }
}
