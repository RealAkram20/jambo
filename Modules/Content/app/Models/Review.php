<?php

namespace Modules\Content\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $reviewable_type
 * @property int $reviewable_id
 * @property ?string $title
 * @property string $body
 * @property ?int $stars
 * @property bool $is_published
 */
class Review extends Model
{
    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'reviewable_type',
        'reviewable_id',
        'title',
        'body',
        'stars',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'bool',
        'stars' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
