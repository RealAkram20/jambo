<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Content\database\factories\CategoryFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property ?string $cover_url
 * @property int $sort_order
 */
class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_url',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'category_movie');
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'category_show');
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
