<?php

namespace Modules\Content\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Tag;

class ContentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotent for lookup tables (genres, categories, tags) via firstOrCreate.
     * Movies, shows, persons, seasons, and episodes are factory-created and
     * will accumulate on re-runs — that's by design for Phase 1.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $genres = $this->seedGenres();
            $categories = $this->seedCategories();
            $tags = $this->seedTags();

            $persons = Person::factory()->count(20)->create();

            // 20 movies with classification + cast attachments.
            Movie::factory()->count(20)->create()->each(function (Movie $movie) use ($genres, $categories, $tags, $persons) {
                $movie->genres()->syncWithoutDetaching(
                    $genres->random(rand(2, 3))->pluck('id')->all()
                );
                $movie->categories()->syncWithoutDetaching(
                    $categories->random(rand(1, 2))->pluck('id')->all()
                );
                $movie->tags()->syncWithoutDetaching(
                    $tags->random(rand(2, 4))->pluck('id')->all()
                );

                $this->attachCast($movie, $persons, isShow: false);
            });

            // 5 shows, each with 2 seasons of 6 episodes.
            Show::factory()->count(5)->create()->each(function (Show $show) use ($genres, $categories, $tags, $persons) {
                $show->genres()->syncWithoutDetaching(
                    $genres->random(rand(2, 3))->pluck('id')->all()
                );
                $show->categories()->syncWithoutDetaching(
                    $categories->random(rand(1, 2))->pluck('id')->all()
                );
                $show->tags()->syncWithoutDetaching(
                    $tags->random(rand(2, 4))->pluck('id')->all()
                );

                $this->attachCast($show, $persons, isShow: true);

                for ($s = 1; $s <= 2; $s++) {
                    $season = Season::factory()->create([
                        'show_id' => $show->id,
                        'number' => $s,
                        'title' => "Season {$s}",
                    ]);

                    for ($e = 1; $e <= 6; $e++) {
                        Episode::factory()->create([
                            'season_id' => $season->id,
                            'number' => $e,
                        ]);
                    }
                }
            });
        });
    }

    private function seedGenres()
    {
        $data = [
            ['name' => 'Action',      'colour' => '#e53935'],
            ['name' => 'Drama',       'colour' => '#1e88e5'],
            ['name' => 'Comedy',      'colour' => '#fdd835'],
            ['name' => 'Thriller',    'colour' => '#6d4c41'],
            ['name' => 'Romance',     'colour' => '#d81b60'],
            ['name' => 'Sci-Fi',      'colour' => '#8e24aa'],
            ['name' => 'Horror',      'colour' => '#111111'],
            ['name' => 'Documentary', 'colour' => '#43a047'],
        ];

        foreach ($data as $row) {
            Genre::firstOrCreate(
                ['slug' => Str::slug($row['name'])],
                ['name' => $row['name'], 'colour' => $row['colour']]
            );
        }

        return Genre::whereIn('slug', array_map(fn ($r) => Str::slug($r['name']), $data))->get();
    }

    private function seedCategories()
    {
        $data = [
            'Trending',
            'New Releases',
            'Award Winners',
            'Featured',
            "Editor's Picks",
            'Most Watched',
        ];

        foreach ($data as $i => $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => "Curated {$name} shelf.",
                    'cover_url' => 'https://picsum.photos/seed/' . Str::slug($name) . '/1280/400',
                    'sort_order' => $i * 10,
                ]
            );
        }

        return Category::whereIn('slug', array_map(fn ($n) => Str::slug($n), $data))->get();
    }

    private function seedTags()
    {
        $data = ['4k', 'HD', 'Subtitles', 'Original', 'Exclusive', 'Family', 'Classic', 'Adventure', 'True Story', 'Based on Book'];

        foreach ($data as $name) {
            Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }

        return Tag::whereIn('slug', array_map(fn ($n) => Str::slug($n), $data))->get();
    }

    /**
     * Attach 4-6 persons as cast/crew with varied roles.
     * Each (title, person, role) triple must be unique because the pivot
     * uses a composite primary key on exactly those three columns.
     */
    private function attachCast($title, $persons, bool $isShow): void
    {
        $picked = $persons->shuffle()->take(rand(4, 6))->values();

        $rows = [];
        $order = 0;

        $actorCount = min(rand(1, 2), $picked->count());
        for ($i = 0; $i < $actorCount; $i++) {
            $rows[] = [
                'person_id' => $picked[$i]->id,
                'role' => 'actor',
                'character_name' => fake()->firstName() . ' ' . fake()->lastName(),
                'display_order' => $order++,
            ];
        }

        if ($picked->count() > $actorCount) {
            $rows[] = [
                'person_id' => $picked[$actorCount]->id,
                'role' => 'director',
                'character_name' => null,
                'display_order' => $order++,
            ];
        }

        if ($picked->count() > $actorCount + 1 && rand(0, 1) === 1) {
            $rows[] = [
                'person_id' => $picked[$actorCount + 1]->id,
                'role' => 'writer',
                'character_name' => null,
                'display_order' => $order++,
            ];
        }

        $table = $isShow ? 'show_person' : 'movie_person';
        $fk = $isShow ? 'show_id' : 'movie_id';
        $seen = [];

        foreach ($rows as $row) {
            $key = $row['person_id'] . '|' . $row['role'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            DB::table($table)->insertOrIgnore([
                $fk => $title->id,
                'person_id' => $row['person_id'],
                'role' => $row['role'],
                'character_name' => $row['character_name'],
                'display_order' => $row['display_order'],
            ]);
        }
    }
}
