<?php

namespace Modules\Referrals\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUALIFIED = 'qualified';

    public const SOURCE_COOKIE = 'cookie';
    public const SOURCE_CODE = 'code';

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'code_used',
        'source',
        'status',
        'qualified_payment_order_id',
        'discount_percent',
        'reward_percent',
        'original_amount',
        'discount_amount',
        'paid_amount',
        'reward_amount',
        'currency',
        'qualified_at',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'reward_percent' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'qualified_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
