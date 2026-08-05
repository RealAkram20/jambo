<?php

namespace Modules\Monetization\app\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Earning facts, one per (viewer, title, month). Under the hybrid
 * accrual model minutes GROW as the paid viewer watches (topped up to
 * the full runtime at completion), so three accrual columns are
 * mutable: minutes_credited (monotonically increasing), completed_at
 * (stamped once), last_credited_at. Every identity column is frozen
 * and rows can never be deleted — the guards below turn accidental
 * writes into loud failures instead of silent financial corruption.
 *
 * @property int $id
 * @property ?int $user_id
 * @property string $watchable_type
 * @property int $watchable_id
 * @property ?int $show_id
 * @property \Illuminate\Support\Carbon $period_month
 * @property int $minutes_credited
 * @property \Illuminate\Support\Carbon $qualified_at
 * @property ?\Illuminate\Support\Carbon $completed_at
 * @property ?\Illuminate\Support\Carbon $last_credited_at
 */
class QualifiedView extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'qualified_views';

    protected $guarded = [];

    protected $casts = [
        'period_month' => 'date',
        'qualified_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_credited_at' => 'datetime',
    ];

    /** The only columns the accrual path may change after insert. */
    private const MUTABLE = ['minutes_credited', 'completed_at', 'last_credited_at'];

    protected static function booted(): void
    {
        static::updating(function (QualifiedView $view) {
            if (array_diff(array_keys($view->getDirty()), self::MUTABLE)) {
                throw new \LogicException('qualified_views identity columns are immutable.');
            }
            if ($view->isDirty('minutes_credited')
                && (int) $view->minutes_credited < (int) $view->getOriginal('minutes_credited')) {
                throw new \LogicException('qualified_views minutes can only increase.');
            }
        });

        static::deleting(function () {
            throw new \LogicException('qualified_views rows can never be deleted.');
        });
    }
}
