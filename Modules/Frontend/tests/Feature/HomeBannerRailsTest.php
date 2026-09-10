<?php

namespace Modules\Frontend\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Tests\TestCase;

/**
 * The two daily Top 10 banners, on `GET /api/v1/home`.
 *
 * The website has drawn these since 1.8.14 and the app could not, because
 * `verticalFeatured` and `tabSeries` never left `HomeRailsService` — the app's
 * home screen was the website's home screen minus its two biggest blocks.
 *
 * What is worth pinning here is not that the rails exist. It is the three ways
 * they can be quietly wrong:
 *
 *   1. **A rank the title did not earn.** Both shelves pad themselves from
 *      all-time popularity when a day is quiet, so a slide's POSITION is
 *      always 1..10 while its rank is sometimes nothing at all. Printing
 *      "#4 in Movies Today" over a padded title is the app inventing a
 *      statistic, which is exactly the fault the five invented stars were.
 *   2. **A banner carrying a hero's payload.** These slides draw no Tags and
 *      no Starring line, and ten of them are on the first paint of every
 *      session. A shape that quietly widens back to `hero()` costs bytes
 *      nobody renders on the request that matters most.
 *   3. **A heading appearing over a full-width banner**, because a rail
 *      normally has one and this one must not.
 */
class HomeBannerRailsTest extends TestCase
{
    use RefreshDatabase;

    private const VIDEO = 'https://origin.example.test/secret-master-file.mp4';

    /** The two banners reach the app at all, which is the reported bug. */
    public function test_both_daily_banners_reach_the_app(): void
    {
        $this->seedCatalogue();

        $banners = $this->banners();

        $this->assertSame(
            ['movies_today', 'series_today'],
            array_column($banners, 'style'),
            'Both daily banners must be on the home payload, movies first.',
        );

        foreach ($banners as $rail) {
            $this->assertSame('banner', $rail['kind']);
            $this->assertNotEmpty($rail['items']);
        }
    }

    /**
     * The website draws no heading over either banner. Sending one invites a
     * client to draw it, and then two surfaces disagree.
     */
    public function test_a_banner_sends_no_heading(): void
    {
        $this->seedCatalogue();

        foreach ($this->banners() as $rail) {
            $this->assertArrayNotHasKey('title', $rail);
        }
    }

    /**
     * 🔴 The assertion this feature exists to keep honest.
     *
     * One movie is watched inside the 24-hour window and the rest are padded
     * in from all-time popularity. The watched one is ranked; every other
     * slide says so by carrying `ranked_today: false`, which is what makes the
     * app draw "Popular on Jambo" instead of a number.
     */
    public function test_only_a_title_watched_today_claims_a_rank(): void
    {
        $this->seedCatalogue();

        $watched = Movie::query()->orderBy('id')->firstOrFail();
        $this->watch($watched, now()->subHours(2));

        $slides = $this->banner('movies_today')['items'];

        $earned = collect($slides)->firstWhere('id', $watched->id);
        $this->assertNotNull($earned, 'A movie watched today must be on the daily shelf.');
        $this->assertTrue($earned['ranked_today']);
        $this->assertSame(1, $earned['rank'], 'The only title with any activity ranks first.');

        $padded = collect($slides)->reject(fn (array $s) => $s['id'] === $watched->id);
        $this->assertNotEmpty($padded, 'The fixture must produce padded slides or this is vacuous.');

        foreach ($padded as $slide) {
            $this->assertFalse(
                $slide['ranked_today'],
                "Slide '{$slide['title']}' was padded in from all-time popularity and claims a rank it did not earn.",
            );
        }
    }

    /**
     * A view older than the window is not today's activity. Without this, the
     * 24-hour window could be widened to all time and every assertion above
     * would still pass.
     */
    public function test_a_view_from_last_week_earns_no_rank_today(): void
    {
        $this->seedCatalogue();

        $movie = Movie::query()->orderBy('id')->firstOrFail();
        $this->watch($movie, now()->subDays(6));

        $slides = $this->banner('movies_today')['items'];

        foreach ($slides as $slide) {
            $this->assertFalse($slide['ranked_today']);
        }
    }

    /** Rank is the position in this shelf, and it starts at one. */
    public function test_rank_is_the_position_in_the_shelf(): void
    {
        $this->seedCatalogue();

        foreach ($this->banners() as $rail) {
            $this->assertSame(
                range(1, count($rail['items'])),
                array_column($rail['items'], 'rank'),
                "Rail '{$rail['key']}' does not number its slides 1..n in order.",
            );
        }
    }

    /**
     * The movies slide is a hero minus what its blade does not draw. Both
     * halves are asserted: the fields it must carry, and the two it must not.
     */
    public function test_a_movies_slide_carries_the_banner_shape_and_not_the_heros(): void
    {
        $this->seedCatalogue();

        $slide = $this->banner('movies_today')['items'][0];

        foreach (['backdrop_url', 'synopsis', 'genres', 'rank', 'ranked_today'] as $field) {
            $this->assertArrayHasKey($field, $slide);
        }

        // vertical-banner.blade.php renders neither, and ten unread cast
        // arrays on a first paint is what the separate shape avoids.
        $this->assertArrayNotHasKey('tags', $slide);
        $this->assertArrayNotHasKey('cast', $slide);

        // Nor any of detail()'s.
        $this->assertArrayNotHasKey('views_count', $slide);
        $this->assertArrayNotHasKey('categories', $slide);
    }

    /**
     * Four genres, not the hero's three, because the blade slices this list
     * with `take(4)`. A shared constant would be one chip short of the website
     * on any movie carrying four, which is a difference nobody would trace
     * back to a constant.
     */
    public function test_a_movies_slide_carries_the_blades_four_genres(): void
    {
        $this->seedCatalogue();

        $movie = Movie::query()->orderBy('id')->firstOrFail();

        foreach (['Action', 'Drama', 'Comedy', 'Horror', 'Romance'] as $name) {
            $movie->genres()->attach(
                Genre::firstOrCreate(['slug' => strtolower($name)], ['name' => $name])->id
            );
        }

        $this->watch($movie, now()->subHour());

        $slide = collect($this->banner('movies_today')['items'])->firstWhere('id', $movie->id);

        $this->assertCount(4, $slide['genres']);
    }

    /**
     * The series slide draws a release month and a season count where the
     * movie draws genres and a runtime, so it carries different fields.
     */
    public function test_a_series_slide_carries_its_release_and_season_count(): void
    {
        $this->seedCatalogue();

        $slide = $this->banner('series_today')['items'][0];

        $this->assertArrayHasKey('published_at', $slide);
        $this->assertNotNull($slide['published_at'], 'Show::published() guarantees this.');
        $this->assertSame(1, $slide['seasons_count']);

        // The blade's episode list lives in a `d-none d-lg-block` column that
        // no phone draws, so it is not on the wire.
        $this->assertArrayNotHasKey('seasons', $slide);
    }

    /**
     * The other half of the same change: presentation moved from a hardcoded
     * set of rail keys in the app to a field on the rail.
     */
    public function test_the_weekly_top_ten_rails_say_they_are_numbered(): void
    {
        $this->seedCatalogue();

        $rails = collect($this->rails())->keyBy('key');

        $this->assertSame('numbered', $rails['top_movies']['style']);
        $this->assertSame('numbered', $rails['top_series']['style']);

        // And an ordinary rail says nothing, which is what "draw it the
        // ordinary way" looks like on the wire. Only on Jambo stands in for
        // Latest Movies here, which was retired from the home page on
        // 2026-09-11.
        $this->assertArrayNotHasKey('style', $rails['exclusives']);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    private function rails(): array
    {
        return $this->getJson('/api/v1/home')->assertOk()->json('data.rails');
    }

    /** @return array<int, array<string, mixed>> */
    private function banners(): array
    {
        return array_values(array_filter(
            $this->rails(),
            fn (array $rail) => ($rail['kind'] ?? null) === 'banner',
        ));
    }

    /** @return array<string, mixed> */
    private function banner(string $style): array
    {
        $rail = collect($this->banners())->firstWhere('style', $style);

        $this->assertNotNull($rail, "There is no '{$style}' banner on the home payload.");

        return $rail;
    }

    private function watch(Movie $movie, \Illuminate\Support\Carbon $when): void
    {
        WatchHistoryItem::create([
            'user_id' => User::factory()->create()->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'position_seconds' => 60,
            'duration_seconds' => 600,
            'watched_at' => $when,
        ]);
    }

    /**
     * Enough catalogue for both shelves to fill.
     *
     * Four movies rather than one, because the padded tail is where half these
     * assertions live: with a single movie there is nothing to pad and
     * `ranked_today: false` would never be exercised.
     */
    private function seedCatalogue(): void
    {
        for ($i = 0; $i < 4; $i++) {
            Movie::factory()->create([
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => now()->subDays($i + 1),
                'views_count' => 100 - $i,
                'video_url' => self::VIDEO,
            ]);
        }

        // Show::published() needs a published episode, or the series banner is
        // silently empty and its assertions would pass for nothing.
        for ($i = 0; $i < 2; $i++) {
            $show = Show::create([
                'title' => 'Banner Series ' . $i,
                'slug' => 'banner-series-' . $i . '-' . uniqid(),
                'status' => 'published',
                'published_at' => now()->subDays($i + 1),
            ]);

            $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'S1']);
            Episode::create([
                'season_id' => $season->id,
                'number' => 1,
                'title' => 'Pilot',
                'published_at' => now()->subDays($i + 1),
                'video_url' => self::VIDEO,
            ]);
        }
    }
}
