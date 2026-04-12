<?php

namespace Modules\Content\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Person;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();
        $roles = $this->faker->randomElements(
            ['actor', 'director', 'writer', 'producer', 'cinematographer'],
            $this->faker->numberBetween(1, 3)
        );

        return [
            'first_name' => $first,
            'last_name' => $last,
            'slug' => Str::slug("{$first} {$last}") . '-' . Str::random(5),
            'bio' => $this->faker->paragraph(4),
            'birth_date' => $this->faker->dateTimeBetween('-80 years', '-20 years')->format('Y-m-d'),
            'death_date' => null,
            'photo_url' => 'https://i.pravatar.cc/400?u=' . Str::random(10),
            'known_for' => implode(',', $roles),
        ];
    }
}
