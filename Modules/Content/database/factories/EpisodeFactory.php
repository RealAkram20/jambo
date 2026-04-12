<?php

namespace Modules\Content\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Season;

class EpisodeFactory extends Factory
{
    protected $model = Episode::class;

    public function definition(): array
    {
        $title = ucfirst($this->faker->words(3, true));

        return [
            'season_id' => Season::factory(),
            'number' => $this->faker->numberBetween(1, 24),
            'title' => $title,
            'synopsis' => $this->faker->paragraph(3),
            'runtime_minutes' => $this->faker->numberBetween(22, 70),
            'still_url' => 'https://picsum.photos/seed/' . Str::random(8) . '/1280/720',
            'dropbox_path' => '/jambo/episodes/' . Str::slug($title) . '-' . Str::random(6) . '.mp4',
            'tier_required' => $this->faker->randomElement([null, null, 'basic', 'premium']),
            'published_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
