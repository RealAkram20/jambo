<?php

namespace Modules\Frontend\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Frontend\app\Services\RailArchiveCatalog;
use Tests\TestCase;

/**
 * Behaviour pin for the "see all" rail archives, written BEFORE their
 * definitions were extracted into RailArchiveCatalog for the API to share.
 *
 * The definitions decide what a viewer sees behind every rail on the homepage.
 * A second copy in the API would show a different list than the website for
 * the same rail, which is the drift this phase has spent its time removing.
 */
class RailArchivePinTest extends TestCase
{
    use RefreshDatabase;

    /** Every rail key the website answers on. Losing one is a 404 for a viewer. */
    private const RAILS = [
        'top-picks', 'smart-shuffle', 'fresh-picks', 'popular-movies',
        'only-on-streamit', 'latest-movies', 'popular-series', 'latest-series',
    ];

    /**
     * The three personalised rails pin their own items to the front with
     * MySQL's FIELD(), which SQLite does not implement.
     *
     * Production is MariaDB so they work there, but it means these three have
     * never been coverable by this suite and never will be while tests run on
     * SQLite. Recorded rather than worked around: a rewrite to portable SQL is
     * a real change to a live ranking, not a test fix.
     */
    private const MYSQL_ONLY = ['top-picks', 'smart-shuffle', 'fresh-picks'];

    public function test_every_portable_rail_archive_renders(): void
    {
        $this->seedCatalogue();

        foreach (array_diff(self::RAILS, self::MYSQL_ONLY) as $rail) {
            $this->get("/collection/{$rail}")
                ->assertOk("The /collection/{$rail} archive must still render.");
        }
    }

    /**
     * The rail KEYS are the contract even where the query cannot run here: a
     * missing key is a 404 for a viewer, and that is checkable without
     * executing the pinned ordering.
     */
    public function test_the_personalised_rails_are_still_defined(): void
    {
        $catalog = app(RailArchiveCatalog::class);

        foreach (self::RAILS as $rail) {
            $this->assertTrue($catalog->has($rail), "Rail '{$rail}' disappeared from the catalog.");
            $this->assertNotEmpty($catalog->title($rail));
        }
    }

    public function test_an_unknown_rail_is_a_404(): void
    {
        $this->seedCatalogue();

        $this->get('/collection/not-a-real-rail')->assertNotFound();
    }

    public function test_the_exclusives_archive_only_carries_gated_titles(): void
    {
        $this->seedCatalogue();
        $free = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'tier_required' => null,
            'title' => 'Definitely Free Title',
        ]);

        $this->get('/collection/only-on-streamit')
            ->assertOk()
            ->assertDontSee($free->title, false);
    }

    public function test_a_draft_never_reaches_an_archive(): void
    {
        $this->seedCatalogue();
        $draft = Movie::factory()->create([
            'status' => Movie::STATUS_DRAFT,
            'published_at' => null,
            'title' => 'Draft Not For Archives',
        ]);

        $this->get('/collection/latest-movies')
            ->assertOk()
            ->assertDontSee($draft->title, false);
    }

    private function seedCatalogue(): void
    {
        for ($i = 0; $i < 3; $i++) {
            Movie::factory()->create([
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => now()->subDays($i + 1),
                'tier_required' => $i === 0 ? 'premium' : null,
            ]);
        }

        $show = Show::create([
            'title' => 'Archive Series',
            'slug' => 'archive-series-' . uniqid(),
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'S1']);
        Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Pilot',
            'published_at' => now()->subDay(),
            'video_url' => 'https://cdn.example.test/ep.mp4',
        ]);
    }
}
