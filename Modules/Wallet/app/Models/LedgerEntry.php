<?php

namespace Modules\Wallet\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The ONE money ledger. Every credit and debit on the platform —
 * VJ/partner statement earnings, referral commissions, refunds,
 * withdrawal holds, spends — is a row here.
 *
 * Owner is polymorphic: App\Models\User for regular members,
 * Modules\Monetization\...\MonetizationPartner for partner profiles
 * (which may have no login account of their own).
 *
 * Append-only: rows are inserted once and never updated or deleted.
 */
class LedgerEntry extends Model
{
    protected $table = 'wallet_ledger_entries';

    public const UPDATED_AT = null;

    /** Credits */
    public const TYPE_STATEMENT_CREDIT = 'statement_credit';
    public const TYPE_REFERRAL_REWARD = 'referral_reward';
    /** Staff pay-per-upload (movie/show/episode), credited per content_activity_log row. */
    public const TYPE_PERFORMANCE_CREDIT = 'performance_credit';
    public const TYPE_REFUND = 'refund';
    public const TYPE_HOLD_RELEASE = 'hold_release';
    /** Debits (negative amounts) */
    public const TYPE_SPEND = 'spend';
    public const TYPE_WITHDRAWAL_HOLD = 'withdrawal_hold';
    /** Signed either way, super-admin corrections */
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('wallet_ledger_entries rows are append-only.');
        });
        static::deleting(function () {
            throw new \LogicException('wallet_ledger_entries rows are append-only.');
        });
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
