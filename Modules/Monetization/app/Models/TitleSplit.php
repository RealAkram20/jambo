<?php

namespace Modules\Monetization\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $splittable_type
 * @property int $splittable_id
 * @property int $partner_id
 * @property string $percent
 */
class TitleSplit extends Model
{
    protected $table = 'title_splits';

    protected $fillable = [
        'splittable_type', 'splittable_id', 'partner_id', 'percent',
    ];

    protected $casts = [
        'percent' => 'decimal:2',
    ];

    public function splittable(): MorphTo
    {
        return $this->morphTo();
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(MonetizationPartner::class, 'partner_id');
    }
}
