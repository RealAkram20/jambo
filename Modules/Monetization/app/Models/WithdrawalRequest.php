<?php

namespace Modules\Monetization\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $partner_id
 * @property string $amount
 * @property string $status
 * @property string $payout_msisdn_snapshot
 * @property ?int $hold_entry_id
 */
class WithdrawalRequest extends Model
{
    protected $table = 'withdrawal_requests';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';

    /** Statuses that still tie up wallet funds / block a new request. */
    public const OPEN_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_APPROVED,
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(MonetizationPartner::class, 'partner_id');
    }

    public function holdEntry(): BelongsTo
    {
        return $this->belongsTo(WalletEntry::class, 'hold_entry_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
