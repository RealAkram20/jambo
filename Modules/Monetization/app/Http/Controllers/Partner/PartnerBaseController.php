<?php

namespace Modules\Monetization\app\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Modules\Monetization\app\Models\MonetizationPartner;

/**
 * Ownership scoping for the whole partner console: every query in
 * every child controller starts from the partner row resolved off the
 * AUTHENTICATED user. No route ever accepts a partner id — a partner
 * cannot address another partner's data.
 */
abstract class PartnerBaseController extends Controller
{
    protected function partner(): MonetizationPartner
    {
        return MonetizationPartner::query()
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }
}
