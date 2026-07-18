<?php

namespace Modules\Referrals\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Referrals\app\Services\ReferralSettings;

/**
 * Super-admin-only knobs that shape the referral money.
 */
class ReferralSettingsController extends Controller
{
    public function index()
    {
        return view('referrals::admin.settings', [
            'values' => $this->currentValues(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'active' => 'required|boolean',
            'reward_percent' => 'required|numeric|min:0|max:100',
            // Capped below 100: a full discount produces a zero-amount
            // order the payment gateway cannot process, which would block
            // every referred buyer at checkout.
            'discount_percent' => 'required|numeric|min:0|max:99',
            'cookie_days' => 'required|integer|min:1|max:90',
            'min_withdrawal' => 'required|numeric|min:0',
        ]);

        setting(['referrals.active', $data['active'] ? '1' : '0']);
        setting(['referrals.reward_percent', (string) $data['reward_percent']]);
        setting(['referrals.discount_percent', (string) $data['discount_percent']]);
        setting(['referrals.cookie_days', (string) $data['cookie_days']]);
        setting(['referrals.min_withdrawal', (string) $data['min_withdrawal']]);

        ReferralSettings::flush();

        return redirect()
            ->route('admin.referrals.settings')
            ->with('success', 'Referral settings saved.');
    }

    protected function currentValues(): array
    {
        ReferralSettings::flush();

        return [
            'active' => ReferralSettings::active() ? '1' : '0',
            'reward_percent' => ReferralSettings::rewardPercent(),
            'discount_percent' => ReferralSettings::discountPercent(),
            'cookie_days' => (string) ReferralSettings::cookieDays(),
            'min_withdrawal' => ReferralSettings::minWithdrawal(),
        ];
    }
}
