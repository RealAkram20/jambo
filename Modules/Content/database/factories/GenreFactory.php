<?php

namespace Modules\Content\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Genre;

class GenreFactory extends Factory
{
    protected $model = Genre::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Action', 'Adventure', 'Animation', 'Biography', 'Comedy',
            'Crime', 'Documentary', 'Drama', 'Family', 'Fantasy',
            'History', 'Horror', 'Mystery', 'Romance', 'Sci-Fi',
            'Sport', 'Thriller', 'War', 'Western',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'colour' => $this->faker->hexColor(),
        ];
    }
}
