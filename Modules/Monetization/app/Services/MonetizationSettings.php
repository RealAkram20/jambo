<?php

namespace Modules\Monetization\app\Services;

use Illuminate\Support\Carbon;

/**
 * Typed accessors over the shared `settings` table (setting() helper).
 *
 * The per-request static cache matters: the accrual listener runs on
 * every 15s heartbeat from every concurrent viewer, and must not pay
 * repeated settings-table queries for its early-exit checks.
 */
class MonetizationSettings
{
    /** @var array<string, mixed> */
    protected static array $cache = [];

    public const KEYS = [
        'monetization.active',
        'monetization.activated_at',
        'monetization.pool_percent',
        'monetization.gateway_fee_percent',
        'monetization.infra_cost_monthly',
        'monetization.qualify_threshold_percent',
        'monetization.free_content_earns',
        'monetization.min_withdrawal',
        'monetization.daily_minutes_cap',
        'monetization.payout_change_cooldown_days',
        'monetization.finance_can_view',
    ];

    protected static function get(string $key, $default = null)
    {
        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = setting($key, $default);
        }

        return static::$cache[$key] ?? $default;
    }

    /** Forget the per-request cache (used by tests and after updates). */
    public static function flush(): void
    {
        static::$cache = [];
    }

    public static function active(): bool
    {
        return (string) static::get('monetization.active', '0') === '1';
    }

    public static function activatedAt(): ?Carbon
    {
        $raw = static::get('monetization.activated_at');

        return $raw ? Carbon::parse($raw) : null;
    }

    /** Program is on AND we're past the activation date. */
    public static function accruing(): bool
    {
        if (!static::active()) {
            return false;
        }

        $activatedAt = static::activatedAt();

        return $activatedAt !== null && now()->greaterThanOrEqualTo($activatedAt);
    }

    public static function poolPercent(): string
    {
        return (string) static::get('monetization.pool_percent', '30');
    }

    public static function gatewayFeePercent(): string
    {
        return (string) static::get('monetization.gateway_fee_percent', '3.5');
    }

    public static function infraCostMonthly(): string
    {
        return (string) static::get('monetization.infra_cost_monthly', '0');
    }

    public static function qualifyThresholdPercent(): int
    {
        return (int) static::get('monetization.qualify_threshold_percent', 70);
    }

    /**
     * Do FREE titles (tier_required = null) mint payable minutes?
     *
     * Default ON, and that default is deliberate: the pool is built
     * from subscription revenue, so a paid subscriber's money is
     * already in it no matter which shelf they watched from. Switching
     * this OFF is a commercial decision ("your deal covers the premium
     * catalogue only"), not a technical one — it does not save the
     * platform money, it just moves that slice of the pool from
     * partners to the platform.
     *
     * Note this is about free CONTENT, not free VIEWERS. A viewer with
     * no paid subscription never mints anything either way — that gate
     * lives in WatchAccrualService::qualify() and is not configurable,
     * because a free viewer contributes nothing to the pool they'd be
     * paid out of.
     *
     * SAFETY INTERLOCK: ActiveStream::countsFreeContent() reads this
     * same flag. Free titles count against the concurrent-device cap
     * exactly when they can earn. The two must never drift apart — if
     * free content could earn while sitting outside the device cap,
     * one paid account could open unlimited tabs of free titles and
     * farm a partner's catalogue in an afternoon.
     */
    public static function freeContentEarns(): bool
    {
        return (string) static::get('monetization.free_content_earns', '1') === '1';
    }

    public static function minWithdrawal(): string
    {
        return (string) static::get('monetization.min_withdrawal', '50000');
    }

    /**
     * Max payable minutes one account can credit per day. 480 (8h)
     * clears any realistic binge with room to spare while capping what
     * a scripted account can mint.
     */
    public static function dailyMinutesCap(): int
    {
        return (int) static::get('monetization.daily_minutes_cap', 480);
    }

    public static function payoutChangeCooldownDays(): int
    {
        return (int) static::get('monetization.payout_change_cooldown_days', 3);
    }

    public static function financeCanView(): bool
    {
        return (string) static::get('monetization.finance_can_view', '1') === '1';
    }

    /**
     * Everything the month-close math depends on, frozen into
     * monetization_periods.settings_snapshot.
     */
    public static function snapshotForClose(): array
    {
        return [
            'pool_percent' => static::poolPercent(),
            'gateway_fee_percent' => static::gatewayFeePercent(),
            'infra_cost_monthly' => static::infraCostMonthly(),
            'qualify_threshold_percent' => static::qualifyThresholdPercent(),
            // Both of these shape which minutes exist to be paid, so a
            // partner querying a past statement can be shown the exact
            // rules that produced it.
            'free_content_earns' => static::freeContentEarns(),
            'daily_minutes_cap' => static::dailyMinutesCap(),
            'activated_at' => optional(static::activatedAt())->toDateTimeString(),
        ];
    }
}
