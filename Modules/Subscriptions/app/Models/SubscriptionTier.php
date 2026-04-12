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
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'bool',
    ];

    public const ACCESS_FREE = 0;
    public const ACCESS_BASIC = 1;
    public const ACCESS_PREMIUM = 2;
    public const ACCESS_ULTRA = 3;

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
}
