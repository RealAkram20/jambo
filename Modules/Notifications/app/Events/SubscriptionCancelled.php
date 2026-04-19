<?php

namespace Modules\Notifications\app\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionCancelled
{
    use Dispatchable;

    public function __construct(public readonly User $user)
    {
    }
}
