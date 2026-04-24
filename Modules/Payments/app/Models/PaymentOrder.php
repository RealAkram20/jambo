<?php

namespace Modules\Payments\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property ?string $payable_type
 * @property ?int $payable_id
 * @property string $merchant_reference
 * @property ?string $order_tracking_id
 * @property ?string $confirmation_code
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property string $payment_gateway
 * @property ?string $payment_method
 * @property ?array $metadata
 * @property ?array $raw_response
 */
class PaymentOrder extends Model
{
    use HasFactory;

    protected $table = 'payment_orders';

    /**
     * URL-slug for route-model binding. Using the human-readable
     * merchant_reference (e.g. JAM-7-OZIAS76U4Y, MAN-42-ABCDEF01)
     * instead of the numeric id makes admin URLs like
     * `/admin/payments/orders/JAM-7-OZIAS76U4Y` self-describing and
     * matches what the rest of the payment flow already uses as the
     * canonical identifier (callback URLs, IPN webhooks, notification
     * messages, complete page `?ref=` query). `merchant_reference`
     * is unique per migration, so this is safe as a route key.
     */
    public function getRouteKeyName(): string
    {
        return 'merchant_reference';
    }

    protected $fillable = [
        'user_id',
        'payable_type',
        'payable_id',
        'merchant_reference',
        'order_tracking_id',
        'confirmation_code',
        'amount',
        'currency',
        'status',
        'payment_gateway',
        'payment_method',
        'metadata',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'raw_response' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
