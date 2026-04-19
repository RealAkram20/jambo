<?php

namespace Modules\Subscriptions\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property float $price
 * @property string $currency
 * @property string $billing_period
 * @property int $access_level
 * @property ?array $features
 * @property bool $is_active
 * @property int $sort_order
 */
class SubscriptionTier extends Model
{
    use HasFactory;

    protected $table = 'subscription_tiers';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'access_level',
        'max_concurrent_streams',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'bool',
        'max_concurrent_streams' => 'int',
    ];

    public const ACCESS_FREE = 0;
    public const ACCESS_BASIC = 1;
    public const ACCESS_PREMIUM = 2;
    public const ACCESS_ULTRA = 3;

    public const PERIOD_DAILY = 'daily';
    public const PERIOD_WEEKLY = 'weekly';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_YEARLY = 'yearly';

    public const PERIODS = [
        self::PERIOD_DAILY,
        self::PERIOD_WEEKLY,
        self::PERIOD_MONTHLY,
        self::PERIOD_YEARLY,
    ];

    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('access_level');
    }

    /**
     * How many calendar days this tier's billing period covers. Used by
     * the "activate a subscription on payment" listener to compute
     * starts_at/ends_at without caring which period was chosen.
     *
     * `yearly` maps to 365 days and `monthly` to 30 days — close enough
     * for a streaming SVOD; we do not need calendar-accurate month
     * boundaries.
     */
    public function durationInDays(): int
    {
        return match ($this->billing_period) {
            self::PERIOD_DAILY => 1,
            self::PERIOD_WEEKLY => 7,
            self::PERIOD_MONTHLY => 30,
            self::PERIOD_YEARLY => 365,
            default => 30,
        };
    }

    public function periodLabel(): string
    {
        return match ($this->billing_period) {
            self::PERIOD_DAILY => 'per day',
            self::PERIOD_WEEKLY => 'per week',
            self::PERIOD_MONTHLY => 'per month',
            self::PERIOD_YEARLY => 'per year',
            default => $this->billing_period,
        };
    }
}
