<?php

namespace Modules\Wallet\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A request to cash a wallet balance out (manual mobile-money payout).
 * One state machine for every owner type:
 *
 *   requested → approved → paid   (hold stays = permanent debit)
 *            ↘ rejected           (hold released back to the wallet)
 */
class WithdrawalRequest extends Model
{
    protected $table = 'wallet_withdrawal_requests';

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

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function holdEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'hold_entry_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
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

    /** Human label for the queue: username or partner display name. */
    public function ownerLabel(): string
    {
        $owner = $this->owner;

        return $owner->display_name
            ?? $owner->username
            ?? ('#' . $this->owner_id);
    }
}
