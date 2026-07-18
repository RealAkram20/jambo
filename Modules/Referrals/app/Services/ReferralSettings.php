<?php

namespace Modules\Referrals\app\Services;

/**
 * Typed accessors over the shared `settings` table (setting() helper),
 * mirroring MonetizationSettings. The per-request static cache keeps the
 * ?ref= capture middleware (which runs on every web request) from paying
 * a settings query per hit.
 */
class ReferralSettings
{
    /** @var array<string, mixed> */
    protected static array $cache = [];

    public const KEYS = [
        'referrals.active',
        'referrals.reward_percent',
        'referrals.discount_percent',
        'referrals.cookie_days',
        'referrals.min_withdrawal',
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
        return (string) static::get('referrals.active', '0') === '1';
    }

    /** Percent of the paid amount credited to the referrer. */
    public static function rewardPercent(): string
    {
        return (string) static::get('referrals.reward_percent', '10');
    }

    /** Percent knocked off the referred buyer's first payment. */
    public static function discountPercent(): string
    {
        return (string) static::get('referrals.discount_percent', '10');
    }

    public static function cookieDays(): int
    {
        return max(1, min(90, (int) static::get('referrals.cookie_days', 15)));
    }

    /** Smallest referral-wallet withdrawal the clerk will process. */
    public static function minWithdrawal(): string
    {
        return (string) static::get('referrals.min_withdrawal', '10000');
    }

    /**
     * The terms frozen into payment_orders.metadata at order time; the
     * completion listener honours these even if settings change while the
     * order is in flight.
     */
    public static function snapshotForOrder(): array
    {
        return [
            'discount_percent' => static::discountPercent(),
            'reward_percent' => static::rewardPercent(),
        ];
    }
}
