<?php

namespace Modules\Monetization\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * The partner withdrawal queue merged into the universal Wallet payout
 * queue (admin/wallet/withdrawals) — partner earnings and referral
 * commissions are clerked side by side there. These stubs keep old
 * bookmarks and stored notification links working.
 */
class WithdrawalAdminController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.wallet.withdrawals.index');
    }

    public function show(): RedirectResponse
    {
        return redirect()->route('admin.wallet.withdrawals.index');
    }
}
