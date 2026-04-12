<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Content\database\factories\SeasonFactory;

/**
 * @property int $id
 * @property int $show_id
 * @property int $number
 * @property ?string $title
 * @property ?string $synopsis
 * @property ?string $poster_url
 * @property ?\Illuminate\Support\Carbon $released_at
 */
class Season extends Model
{
    use HasFactory;

    protected $table = 'seasons';

    protected $fillable = [
        'show_id',
        'number',
        'title',
        'synopsis',
        'poster_url',
        'released_at',
    ];

    protected $casts = [
        'released_at' => 'date',
        'number' => 'integer',
    ];

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('number');
    }

    protected static function newFactory(): SeasonFactory
    {
        return SeasonFactory::new();
    }
}
