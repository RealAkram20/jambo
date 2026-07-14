<?php

namespace Modules\Monetization\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $period_month
 * @property string $status
 * @property string $pool_amount
 * @property string $partner_pool_amount
 * @property ?array $settings_snapshot
 */
class MonetizationPeriod extends Model
{
    protected $table = 'monetization_periods';

    protected $guarded = [];

    protected $casts = [
        'period_month' => 'date',
        'settings_snapshot' => 'array',
        'computed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CLOSED = 'closed';

    protected static function booted(): void
    {
        // A closed period is settled money — statements were credited
        // to wallets when it closed. Any further mutation is a bug.
        static::updating(function (self $period) {
            if ($period->getOriginal('status') === self::STATUS_CLOSED) {
                throw new \LogicException("Monetization period {$period->id} is closed and immutable.");
            }
        });

        static::deleting(function (self $period) {
            if ($period->status === self::STATUS_CLOSED) {
                throw new \LogicException("Monetization period {$period->id} is closed and cannot be deleted.");
            }
        });
    }

    public function statements(): HasMany
    {
        return $this->hasMany(PartnerStatement::class, 'period_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
