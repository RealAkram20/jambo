<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Payments\app\Models\PaymentOrder;

class PaymentFailed
{
    use Dispatchable;

    public function __construct(
        public readonly PaymentOrder $order,
        public readonly ?string $reason = null,
    ) {
    }
}
