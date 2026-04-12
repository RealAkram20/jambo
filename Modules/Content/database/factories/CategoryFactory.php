<?php

namespace Modules\Content\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Trending Now', 'Hidden Gems', 'Staff Picks', 'Cult Classics',
            'Must Watch', 'Weekend Binge', 'Rising Stars', 'Critics Choice',
        ]) . ' ' . $this->faker->unique()->numberBetween(1, 9999);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(5),
            'description' => $this->faker->sentence(12),
            'cover_url' => 'https://picsum.photos/seed/' . Str::random(8) . '/1280/400',
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
