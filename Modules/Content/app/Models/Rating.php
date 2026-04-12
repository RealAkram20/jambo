<?php

namespace Modules\Content\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $ratable_type
 * @property int $ratable_id
 * @property int $stars
 */
class Rating extends Model
{
    protected $table = 'ratings';

    protected $fillable = [
        'user_id',
        'ratable_type',
        'ratable_id',
        'stars',
    ];

    protected $casts = [
        'stars' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ratable(): MorphTo
    {
        return $this->morphTo();
    }
}
