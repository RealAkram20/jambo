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

    public static function minWithdrawal(): string
    {
        return (string) static::get('monetization.min_withdrawal', '50000');
    }

    public static function dailyMinutesCap(): int
    {
        return (int) static::get('monetization.daily_minutes_cap', 1080);
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
            'activated_at' => optional(static::activatedAt())->toDateTimeString(),
        ];
    }
}
