<?php

namespace Modules\Content\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Content\database\factories\PersonFactory;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $slug
 * @property ?string $bio
 * @property ?\Illuminate\Support\Carbon $birth_date
 * @property ?\Illuminate\Support\Carbon $death_date
 * @property ?string $photo_url
 * @property ?string $known_for
 */
class Person extends Model
{
    use HasFactory;

    protected $table = 'persons';

    protected $fillable = [
        'first_name',
        'last_name',
        'slug',
        'bio',
        'birth_date',
        'death_date',
        'photo_url',
        'known_for',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_person')
            ->withPivot(['role', 'character_name', 'display_order']);
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'show_person')
            ->withPivot(['role', 'character_name', 'display_order']);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    protected static function newFactory(): PersonFactory
    {
        return PersonFactory::new();
    }
}
