<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Payments\app\Models\PaymentOrder;

class OrderPlaced
{
    use Dispatchable;

    public function __construct(public readonly PaymentOrder $order)
    {
    }
}
