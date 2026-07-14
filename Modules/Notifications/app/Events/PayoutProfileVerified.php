<?php

namespace Modules\Notifications\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Monetization\app\Models\MonetizationPartner;

class PayoutProfileVerified
{
    use Dispatchable;

    public function __construct(
        public readonly MonetizationPartner $partner,
    ) {
    }
}
