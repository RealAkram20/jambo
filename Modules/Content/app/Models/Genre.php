<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Content\database\factories\GenreFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $colour
 */
class Genre extends Model
{
    use HasFactory;

    protected $table = 'genres';

    protected $fillable = [
        'name',
        'slug',
        'colour',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'genre_movie');
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'genre_show');
    }

    protected static function newFactory(): GenreFactory
    {
        return GenreFactory::new();
    }
}
