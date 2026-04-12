<?php

namespace Modules\Content\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Show;

class ShowFactory extends Factory
{
    protected $model = Show::class;

    public function definition(): array
    {
        $prefixes = ['The', 'Breaking', 'Beyond', 'Under', 'Inside'];
        $nouns = ['Wire', 'Crown', 'Throne', 'House', 'Office', 'Island', 'City', 'Line', 'Sky', 'Road'];
        $title = $this->faker->randomElement($prefixes) . ' ' . $this->faker->randomElement($nouns)
            . ' ' . $this->faker->unique()->numberBetween(1, 99999);

        $published = $this->faker->boolean(80);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'synopsis' => $this->faker->paragraph(5),
            'year' => $this->faker->numberBetween(1995, 2026),
            'rating' => $this->faker->randomElement(['PG', 'PG-13', 'R']),
            'poster_url' => 'https://picsum.photos/seed/' . Str::random(8) . '/500/750',
            'backdrop_url' => 'https://picsum.photos/seed/' . Str::random(8) . '/1920/1080',
            'trailer_url' => 'https://www.youtube.com/watch?v=' . Str::random(11),
            'tier_required' => $this->faker->randomElement([null, null, 'basic', 'premium']),
            'status' => $published ? Show::STATUS_PUBLISHED : Show::STATUS_DRAFT,
            'published_at' => $published ? $this->faker->dateTimeBetween('-3 years', 'now') : null,
            'views_count' => $this->faker->numberBetween(0, 5_000_000),
        ];
    }
}
