<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Content\database\factories\TagFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_tag');
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'show_tag');
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
