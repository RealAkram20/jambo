<?php

namespace Modules\Content\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Movie;

class MovieFactory extends Factory
{
    protected $model = Movie::class;

    public function definition(): array
    {
        $adjectives = ['Silent', 'Broken', 'Last', 'Dark', 'Eternal', 'Hidden', 'Crimson', 'Forgotten', 'Final', 'Burning'];
        $nouns = ['Horizon', 'Kingdom', 'Shadow', 'Legacy', 'Promise', 'Empire', 'Journey', 'Echo', 'Storm', 'Dawn'];
        $title = $this->faker->randomElement($adjectives) . ' ' . $this->faker->randomElement($nouns);
        if ($this->faker->boolean(30)) {
            $title .= ' ' . $this->faker->randomElement(['II', 'III', 'Returns', 'Reborn', 'Rising']);
        }
        $title .= ' ' . $this->faker->unique()->numberBetween(1, 99999);

        $published = $this->faker->boolean(80);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'synopsis' => $this->faker->paragraph(5),
            'year' => $this->faker->numberBetween(1970, 2026),
            'runtime_minutes' => $this->faker->numberBetween(80, 190),
            'rating' => $this->faker->randomElement(['G', 'PG', 'PG-13', 'R', 'NC-17']),
            'poster_url' => 'https://picsum.photos/seed/' . Str::random(8) . '/500/750',
            'backdrop_url' => 'https://picsum.photos/seed/' . Str::random(8) . '/1920/1080',
            'trailer_url' => 'https://www.youtube.com/watch?v=' . Str::random(11),
            'dropbox_path' => '/jambo/movies/' . Str::slug($title) . '.mp4',
            'tier_required' => $this->faker->randomElement([null, null, 'basic', 'premium']),
            'status' => $published ? Movie::STATUS_PUBLISHED : Movie::STATUS_DRAFT,
            'published_at' => $published ? $this->faker->dateTimeBetween('-2 years', 'now') : null,
            'views_count' => $this->faker->numberBetween(0, 1_000_000),
        ];
    }
}
