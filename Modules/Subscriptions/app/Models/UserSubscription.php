<?php

namespace Modules\Subscriptions\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $subscription_tier_id
 * @property ?int $payment_order_id
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon $ends_at
 * @property string $status
 * @property bool $auto_renew
 * @property ?\Illuminate\Support\Carbon $cancelled_at
 */
class UserSubscription extends Model
{
    use HasFactory;

    protected $table = 'user_subscriptions';

    protected $fillable = [
        'user_id',
        'subscription_tier_id',
        'payment_order_id',
        'starts_at',
        'ends_at',
        'status',
        'auto_renew',
        'cancelled_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_renew' => 'bool',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING = 'pending';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'subscription_tier_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->ends_at !== null
            && $this->ends_at->greaterThanOrEqualTo(now());
    }

    public function scopeCurrent($q)
    {
        return $q->where('status', self::STATUS_ACTIVE)
            ->where('ends_at', '>', now());
    }
}
