<?php

namespace Modules\Monetization\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Monetization\app\Services\AuditLogger;
use Modules\Monetization\app\Services\MonetizationSettings;

/**
 * Super-admin-only knobs that shape the money. Every change is
 * audit-logged as a before/after diff.
 */
class MonetizationSettingsController extends Controller
{
    public function index()
    {
        return view('monetization::admin.settings', [
            'values' => $this->currentValues(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'active' => 'required|boolean',
            'activated_at' => 'nullable|date|required_if:active,1',
            'pool_percent' => 'required|numeric|min:0|max:100',
            'gateway_fee_percent' => 'required|numeric|min:0|max:100',
            'infra_cost_monthly' => 'required|numeric|min:0',
            'qualify_threshold_percent' => 'required|integer|min:1|max:100',
            'free_content_earns' => 'required|boolean',
            'min_withdrawal' => 'required|numeric|min:0',
            'daily_minutes_cap' => 'required|integer|min:60|max:1440',
            'payout_change_cooldown_days' => 'required|integer|min:0|max:90',
            'finance_can_view' => 'required|boolean',
            'default_split_percent' => 'required|numeric|min:1|max:100',
        ]);

        $before = $this->currentValues();

        // Activation date is the accrual epoch — set once when the
        // program first goes live, then leave it alone (moving it
        // earlier can't conjure history; moving it later would orphan
        // already-earned facts).
        setting(['monetization.active', $data['active'] ? '1' : '0']);
        if (!empty($data['activated_at'])) {
            setting(['monetization.activated_at', $data['activated_at']]);
        }
        setting(['monetization.pool_percent', (string) $data['pool_percent']]);
        setting(['monetization.gateway_fee_percent', (string) $data['gateway_fee_percent']]);
        setting(['monetization.infra_cost_monthly', (string) $data['infra_cost_monthly']]);
        setting(['monetization.qualify_threshold_percent', (string) $data['qualify_threshold_percent']]);
        // Turning this on also tightens the concurrent-device cap onto
        // free titles — see ActiveStream::countsFreeContent(). The two
        // are one decision, not two.
        setting(['monetization.free_content_earns', $data['free_content_earns'] ? '1' : '0']);
        setting(['monetization.min_withdrawal', (string) $data['min_withdrawal']]);
        setting(['monetization.daily_minutes_cap', (string) $data['daily_minutes_cap']]);
        setting(['monetization.payout_change_cooldown_days', (string) $data['payout_change_cooldown_days']]);
        setting(['monetization.finance_can_view', $data['finance_can_view'] ? '1' : '0']);
        setting(['monetization.default_split_percent', (string) $data['default_split_percent']]);

        MonetizationSettings::flush();

        AuditLogger::logDiff('settings.updated', null, $before, $this->currentValues());

        return redirect()
            ->route('admin.monetization.settings')
            ->with('success', 'Monetization settings saved.');
    }

    protected function currentValues(): array
    {
        MonetizationSettings::flush();

        return [
            'active' => MonetizationSettings::active() ? '1' : '0',
            'activated_at' => optional(MonetizationSettings::activatedAt())->toDateString(),
            'pool_percent' => MonetizationSettings::poolPercent(),
            'gateway_fee_percent' => MonetizationSettings::gatewayFeePercent(),
            'infra_cost_monthly' => MonetizationSettings::infraCostMonthly(),
            'qualify_threshold_percent' => (string) MonetizationSettings::qualifyThresholdPercent(),
            'free_content_earns' => MonetizationSettings::freeContentEarns() ? '1' : '0',
            'min_withdrawal' => MonetizationSettings::minWithdrawal(),
            'daily_minutes_cap' => (string) MonetizationSettings::dailyMinutesCap(),
            'payout_change_cooldown_days' => (string) MonetizationSettings::payoutChangeCooldownDays(),
            'finance_can_view' => MonetizationSettings::financeCanView() ? '1' : '0',
            'default_split_percent' => MonetizationSettings::defaultSplitPercent(),
        ];
    }
}
