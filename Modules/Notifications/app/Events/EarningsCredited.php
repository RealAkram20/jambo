<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Monetization\app\Models\PartnerStatement;

class EarningsCredited
{
    use Dispatchable;

    public function __construct(
        public readonly PartnerStatement $statement,
    ) {
    }
}
