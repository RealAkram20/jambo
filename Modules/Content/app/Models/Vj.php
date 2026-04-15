<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $colour
 * @property ?string $description
 */
class Vj extends Model
{
    protected $table = 'vjs';

    protected $fillable = [
        'name',
        'slug',
        'colour',
        'description',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_vj');
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'show_vj');
    }
}
