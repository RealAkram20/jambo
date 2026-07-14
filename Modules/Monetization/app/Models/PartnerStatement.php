<?php

namespace Modules\Monetization\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $period_id
 * @property int $partner_id
 * @property string $qualified_minutes
 * @property string $weighted_minutes
 * @property string $amount
 * @property ?array $breakdown
 */
class PartnerStatement extends Model
{
    protected $table = 'partner_statements';

    protected $guarded = [];

    protected $casts = [
        'multiplier_used' => 'decimal:3',
        'qualified_minutes' => 'decimal:4',
        'weighted_minutes' => 'decimal:6',
        'share_ratio' => 'decimal:12',
        'amount' => 'decimal:2',
        'breakdown' => 'array',
    ];

    protected static function booted(): void
    {
        // Statements of a closed period are settled — immutable.
        $guard = function (self $statement) {
            $period = $statement->relationLoaded('period')
                ? $statement->period
                : $statement->period()->first();
            if ($period && $period->status === MonetizationPeriod::STATUS_CLOSED) {
                throw new \LogicException("Statement {$statement->id} belongs to a closed period and is immutable.");
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(MonetizationPeriod::class, 'period_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(MonetizationPartner::class, 'partner_id');
    }
}
