<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Monetization\app\Models\WithdrawalRequest;

class WithdrawalPaid
{
    use Dispatchable;

    public function __construct(
        public readonly WithdrawalRequest $withdrawal,
    ) {
    }
}
