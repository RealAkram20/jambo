<?php

namespace Modules\Monetization\app\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only earning facts. Application code inserts via
 * insertOrIgnore and never mutates rows — the saving/deleting guards
 * turn accidental writes into loud failures instead of silent
 * financial corruption.
 *
 * @property int $id
 * @property ?int $user_id
 * @property string $watchable_type
 * @property int $watchable_id
 * @property ?int $show_id
 * @property \Illuminate\Support\Carbon $period_month
 * @property int $minutes_credited
 * @property \Illuminate\Support\Carbon $qualified_at
 */
class QualifiedView extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'qualified_views';

    protected $guarded = [];

    protected $casts = [
        'period_month' => 'date',
        'qualified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('qualified_views rows are append-only.');
        });

        static::deleting(function () {
            throw new \LogicException('qualified_views rows are append-only.');
        });
    }
}
