<?php

namespace Modules\Streaming\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $watchable_type
 * @property int $watchable_id
 * @property \Illuminate\Support\Carbon $added_at
 */
class WatchlistItem extends Model
{
    use HasFactory;

    protected $table = 'watchlist_items';

    protected $fillable = [
        'user_id',
        'watchable_type',
        'watchable_id',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function watchable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Add an item to the given user's watchlist. Idempotent — returns the
     * existing row if one already exists for (user, item).
     */
    public static function addFor(int $userId, Model $item): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'watchable_type' => $item->getMorphClass(),
                'watchable_id' => $item->getKey(),
            ],
            [
                'added_at' => now(),
            ],
        );
    }
}
