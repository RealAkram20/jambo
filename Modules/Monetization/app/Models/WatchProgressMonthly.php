<?php

namespace Modules\Monetization\app\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Month-scoped accrual accumulator. Written exclusively by
 * WatchAccrualService on the heartbeat hot path; read by nothing
 * financial (qualified_views is the earning fact table).
 *
 * @property int $id
 * @property int $user_id
 * @property string $watchable_type
 * @property int $watchable_id
 * @property \Illuminate\Support\Carbon $period_month
 * @property int $seconds_watched
 * @property int $last_position_seconds
 * @property ?\Illuminate\Support\Carbon $last_beat_at
 * @property bool $qualified
 */
class WatchProgressMonthly extends Model
{
    protected $table = 'watch_progress_monthly';

    protected $fillable = [
        'user_id', 'watchable_type', 'watchable_id', 'period_month',
        'seconds_watched', 'last_position_seconds', 'last_beat_at',
        'qualified', 'session_id', 'ip',
    ];

    protected $casts = [
        'period_month' => 'date',
        'last_beat_at' => 'datetime',
        'qualified' => 'bool',
    ];
}
