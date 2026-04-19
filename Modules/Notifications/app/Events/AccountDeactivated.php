<?php

namespace Modules\Notifications\app\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class AccountDeactivated
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly string $reason = 'user_requested',
    ) {
    }
}
