<?php

namespace Modules\Notifications\app\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionActivated
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly string $planName = 'Jambo premium',
        public readonly ?string $expiresOn = null,
    ) {
    }
}
