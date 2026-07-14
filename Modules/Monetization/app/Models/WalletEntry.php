<?php

namespace Modules\Monetization\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only ledger row. Insert through WalletService::append() only —
 * it holds the partner-row lock that makes balance_after trustworthy.
 *
 * @property int $id
 * @property int $partner_id
 * @property string $type
 * @property string $amount
 * @property string $balance_after
 * @property ?string $reference_type
 * @property ?int $reference_id
 */
class WalletEntry extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'wallet_entries';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public const TYPE_STATEMENT_CREDIT = 'statement_credit';
    public const TYPE_WITHDRAWAL_HOLD = 'withdrawal_hold';
    public const TYPE_HOLD_RELEASE = 'hold_release';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('wallet_entries rows are append-only.');
        });

        static::deleting(function () {
            throw new \LogicException('wallet_entries rows are append-only.');
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(MonetizationPartner::class, 'partner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
