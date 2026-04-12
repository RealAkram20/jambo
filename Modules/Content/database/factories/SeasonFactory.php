<?php

namespace Modules\Content\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'show_id' => Show::factory(),
            'number' => $this->faker->numberBetween(1, 10),
            'title' => 'Season ' . $this->faker->numberBetween(1, 10),
            'synopsis' => $this->faker->paragraph(3),
            'poster_url' => 'https://picsum.photos/seed/' . Str::random(8) . '/500/750',
            'released_at' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
        ];
    }
}
