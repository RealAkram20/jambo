<?php

namespace Modules\Notifications\app\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionExpiring
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly string $planName = 'Your',
        public readonly int $daysRemaining = 7,
    ) {
    }
}
