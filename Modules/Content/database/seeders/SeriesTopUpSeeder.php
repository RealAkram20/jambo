<?php

namespace Modules\Content\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Tag;

/**
 * Idempotent top-up: brings the catalogue to >= 10 published series,
 * with each series owning 3-6 seasons and each season 6-10 episodes.
 *
 * Running this more than once only fills gaps — existing series, seasons,
 * and episodes are left alone.
 */
class SeriesTopUpSeeder extends Seeder
{
    public function run(): void
    {
        $targetSeriesCount = 10;

        $existingCount = Show::count();
        $toCreate = max(0, $targetSeriesCount - $existingCount);

        $genres = Genre::all();
        $categories = Category::all();
        $tags = Tag::all();
        $persons = Person::all();

        if ($toCreate > 0) {
            Show::factory()->count($toCreate)->create()->each(function (Show $show) use ($genres, $categories, $tags, $persons) {
                $show->update([
                    'status' => Show::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(rand(10, 300)),
                ]);
                if ($genres->count())     $show->genres()->syncWithoutDetaching($genres->random(rand(2, 3))->pluck('id')->all());
                if ($categories->count()) $show->categories()->syncWithoutDetaching($categories->random(rand(1, 2))->pluck('id')->all());
                if ($tags->count())       $show->tags()->syncWithoutDetaching($tags->random(rand(2, 4))->pluck('id')->all());
                $this->attachCast($show, $persons);
            });
        }

        // Top up seasons + episodes on every show so each series has 3-6 seasons.
        Show::all()->each(function (Show $show) {
            $targetSeasons = rand(3, 6);
            $existingSeasons = $show->seasons()->count();

            for ($n = $existingSeasons + 1; $n <= $targetSeasons; $n++) {
                $season = Season::factory()->create([
                    'show_id' => $show->id,
                    'number' => $n,
                    'title' => "Season {$n}",
                    'released_at' => now()->subMonths(rand(1, 60)),
                ]);

                $epCount = rand(6, 10);
                for ($e = 1; $e <= $epCount; $e++) {
                    Episode::factory()->create([
                        'season_id' => $season->id,
                        'number' => $e,
                    ]);
                }
            }

            // Make sure existing seasons all have 6-10 episodes.
            $show->seasons()->get()->each(function (Season $season) {
                $epCount = $season->episodes()->count();
                $target = rand(6, 10);
                for ($e = $epCount + 1; $e <= $target; $e++) {
                    Episode::factory()->create([
                        'season_id' => $season->id,
                        'number' => $e,
                    ]);
                }
            });
        });

        // Ensure every show is published (requirement: 10 listed series).
        Show::query()
            ->whereNull('published_at')
            ->orWhere('status', '!=', Show::STATUS_PUBLISHED)
            ->get()
            ->each(function (Show $show) {
                $show->update([
                    'status' => Show::STATUS_PUBLISHED,
                    'published_at' => $show->published_at ?? now()->subDays(rand(10, 300)),
                ]);
            });

        $this->command->info('Shows: ' . Show::count() . ' total, ' . Show::published()->count() . ' published');
        $this->command->info('Seasons: ' . Season::count() . ', Episodes: ' . Episode::count());
    }

    private function attachCast(Show $show, $persons): void
    {
        if ($persons->isEmpty()) return;

        $picked = $persons->shuffle()->take(rand(4, 6))->values();
        $rows = [];
        $order = 0;

        $actorCount = min(rand(2, 3), $picked->count());
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

        $seen = [];
        foreach ($rows as $row) {
            $key = $row['person_id'] . '|' . $row['role'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            \DB::table('show_person')->insertOrIgnore([
                'show_id' => $show->id,
                'person_id' => $row['person_id'],
                'role' => $row['role'],
                'character_name' => $row['character_name'],
                'display_order' => $row['display_order'],
            ]);
        }
    }
}
