<?php

namespace Modules\Monetization\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Content\app\Models\Vj;

/**
 * @property int $id
 * @property string $type
 * @property ?int $user_id
 * @property ?int $vj_id
 * @property string $display_name
 * @property string $status
 * @property string $multiplier
 * @property ?\Illuminate\Support\Carbon $enrolled_at
 * @property ?string $payout_msisdn
 * @property ?string $payout_name
 * @property ?string $payout_network
 * @property string $payout_status
 * @property ?\Illuminate\Support\Carbon $payout_verified_at
 * @property ?int $payout_verified_by
 * @property ?\Illuminate\Support\Carbon $payout_locked_until
 */
class MonetizationPartner extends Model
{
    protected $table = 'monetization_partners';

    protected $fillable = [
        'type', 'user_id', 'vj_id', 'display_name', 'status', 'multiplier',
        'can_edit_content', 'can_delete_content',
        'enrolled_at', 'payout_msisdn', 'payout_name', 'payout_network',
        'payout_status', 'payout_verified_at', 'payout_verified_by',
        'payout_locked_until',
    ];

    protected $casts = [
        'multiplier' => 'decimal:3',
        'can_edit_content' => 'bool',
        'can_delete_content' => 'bool',
        'enrolled_at' => 'datetime',
        'payout_verified_at' => 'datetime',
        'payout_locked_until' => 'datetime',
    ];

    public const TYPE_VJ = 'vj';
    public const TYPE_PRODUCTION_COMPANY = 'production_company';
    public const TYPE_CREATOR = 'creator';

    public const TYPES = [
        self::TYPE_VJ,
        self::TYPE_PRODUCTION_COMPANY,
        self::TYPE_CREATOR,
    ];

    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_SUSPENDED = 'suspended';

    public const PAYOUT_NONE = 'none';
    public const PAYOUT_PENDING_REVIEW = 'pending_review';
    public const PAYOUT_VERIFIED = 'verified';

    public const NETWORKS = ['mtn', 'airtel'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vj(): BelongsTo
    {
        return $this->belongsTo(Vj::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(TitleSplit::class, 'partner_id');
    }

    public function statements(): HasMany
    {
        return $this->hasMany(PartnerStatement::class, 'partner_id');
    }

    public function walletEntries(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Modules\Wallet\app\Models\LedgerEntry::class, 'owner');
    }

    public function withdrawals(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Modules\Wallet\app\Models\WithdrawalRequest::class, 'owner');
    }

    public function scopeEnrolled($q)
    {
        return $q->where('status', self::STATUS_ENROLLED);
    }

    public function isEnrolled(): bool
    {
        return $this->status === self::STATUS_ENROLLED;
    }

    public function payoutVerified(): bool
    {
        return $this->payout_status === self::PAYOUT_VERIFIED;
    }

    public function payoutLocked(): bool
    {
        return $this->payout_locked_until !== null
            && $this->payout_locked_until->isFuture();
    }

    /**
     * Authoritative balance = SUM over the append-only universal
     * ledger (platform currency). Callers inside money transitions
     * must hold the partner-row lock first (Wallet Ledger does) so the
     * sum can't move under them.
     */
    public function walletBalance(): string
    {
        return app(\Modules\Wallet\app\Services\Ledger::class)->balanceFor($this);
    }
}
