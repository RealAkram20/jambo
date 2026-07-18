<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Wallet\app\Models\WithdrawalRequest;

class WithdrawalRejected
{
    use Dispatchable;

    public function __construct(
        public readonly WithdrawalRequest $withdrawal,
    ) {
    }
}
