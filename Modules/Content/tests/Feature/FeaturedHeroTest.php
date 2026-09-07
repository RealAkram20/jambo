<?php

namespace Modules\Content\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\FeaturedItem;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Featured: the hand-picked OTT homepage hero.
 *
 * Three properties matter more than the CRUD:
 *
 *   1. **The homepage is never empty.** With nothing featured, the hero
 *      falls back to the automatic view-count pick that shipped before, so
 *      the feature is safe to deploy before anyone curates anything.
 *   2. **Only publishable titles reach the site.** A featured draft must
 *      not appear on the homepage, and must still be visible (flagged) on
 *      the admin screen, or nobody can work out why the site looks wrong.
 *   3. **Drag order is the render order.** That is the whole point of the
 *      screen.
 *
 * Scope guard: this drives the homepage hero only. The /movie and /series
 * banners are a separate component and one test pins that they do not move.
 */
class FeaturedHeroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
    }

    public function test_hero_falls_back_to_automatic_pick_when_nothing_is_featured(): void
    {
        $popular = $this->publishedMovie(['title' => 'Popular', 'views_count' => 9999]);

        $this->assertSame(0, FeaturedItem::count());

        $hero = FeaturedItem::heroItems();
        $this->assertTrue($hero->isEmpty(), 'No curation means the composer keeps its own automatic pick.');

        // And the homepage still renders that automatic pick.
        $this->get('/')->assertOk()->assertSee('Popular');
        $this->assertSame($popular->id, $popular->fresh()->id);
    }

    public function test_featured_titles_render_in_drag_order(): void
    {
        $first = $this->publishedMovie(['title' => 'Chosen first', 'views_count' => 1]);
        $second = $this->publishedMovie(['title' => 'Chosen second', 'views_count' => 5000]);

        // Deliberately inverse to view count: curation must beat popularity.
        $this->feature($second, 1);
        $this->feature($first, 0);

        $hero = FeaturedItem::heroItems();

        $this->assertCount(2, $hero);
        $this->assertSame($first->id, $hero->first()->id, 'sort_order wins over views_count.');
        $this->assertSame($second->id, $hero->get(1)->id);
    }

    public function test_hero_mixes_movies_and_series_and_flags_which_is_which(): void
    {
        $movie = $this->publishedMovie(['title' => 'A movie']);
        $show = $this->publishedShow(['title' => 'A series']);

        $this->feature($movie, 0);
        $this->feature($show, 1);

        $hero = FeaturedItem::heroItems();

        $this->assertFalse($hero->first()->_isShow, 'The hero partial branches on _isShow.');
        $this->assertTrue($hero->get(1)->_isShow);
    }

    public function test_unpublished_featured_title_never_reaches_the_homepage(): void
    {
        $draft = Movie::factory()->create(['title' => 'Not ready yet', 'status' => Movie::STATUS_DRAFT]);
        $live = $this->publishedMovie(['title' => 'Ready']);

        $this->feature($draft, 0);
        $this->feature($live, 1);

        $hero = FeaturedItem::heroItems();

        $this->assertCount(1, $hero, 'A draft holds its slot in admin but is dropped from the site.');
        $this->assertSame($live->id, $hero->first()->id);
    }

    public function test_admin_screen_still_lists_an_unpublished_featured_title(): void
    {
        $draft = Movie::factory()->create(['title' => 'Not ready yet', 'status' => Movie::STATUS_DRAFT]);
        $this->feature($draft, 0);

        $this->actingAs($this->admin())
            ->get(route('admin.featured.index'))
            ->assertOk()
            ->assertSee('Not ready yet')
            ->assertSee('Hidden'); // the warning badge, not a silent omission
    }

    public function test_admin_can_add_a_movie_and_a_series(): void
    {
        $admin = $this->admin();
        $movie = $this->publishedMovie(['title' => 'Add me']);
        $show = $this->publishedShow(['title' => 'Add me too']);

        $this->actingAs($admin)->post(route('admin.featured.store'), [
            'type' => 'movie', 'id' => $movie->id,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.featured.store'), [
            'type' => 'show', 'id' => $show->id,
        ])->assertRedirect();

        $this->assertSame(2, FeaturedItem::count());
        $this->assertSame([$movie->id, $show->id], FeaturedItem::ordered()->pluck('featurable_id')->all());
    }

    public function test_the_same_title_cannot_be_featured_twice(): void
    {
        $admin = $this->admin();
        $movie = $this->publishedMovie();

        $this->actingAs($admin)->post(route('admin.featured.store'), ['type' => 'movie', 'id' => $movie->id]);
        $this->actingAs($admin)->post(route('admin.featured.store'), ['type' => 'movie', 'id' => $movie->id])
            ->assertSessionHas('error');

        $this->assertSame(1, FeaturedItem::count(), 'A duplicate would show the same banner twice.');
    }

    public function test_reorder_endpoint_rewrites_sort_order(): void
    {
        $a = $this->feature($this->publishedMovie(['title' => 'A']), 0);
        $b = $this->feature($this->publishedMovie(['title' => 'B']), 1);
        $c = $this->feature($this->publishedMovie(['title' => 'C']), 2);

        $this->actingAs($this->admin())
            ->patchJson(route('admin.featured.reorder'), ['order' => [$c->id, $a->id, $b->id]])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(
            [$c->id, $a->id, $b->id],
            FeaturedItem::ordered()->pluck('id')->all(),
            'The dragged order is what the homepage renders.',
        );
    }

    public function test_admin_can_remove_a_featured_title(): void
    {
        $row = $this->feature($this->publishedMovie(['title' => 'Drop me']), 0);

        $this->actingAs($this->admin())
            ->delete(route('admin.featured.destroy', $row))
            ->assertRedirect();

        $this->assertSame(0, FeaturedItem::count());
    }

    public function test_a_deleted_title_stops_holding_a_hero_slot(): void
    {
        $movie = $this->publishedMovie(['title' => 'Gone']);
        $this->feature($movie, 0);
        $movie->delete();

        // Nothing publishable is left, so the site falls back rather than
        // rendering a blank banner.
        $this->assertTrue(FeaturedItem::heroItems()->isEmpty());

        // Loading the admin screen sweeps the orphan row.
        $this->actingAs($this->admin())->get(route('admin.featured.index'))->assertOk();
        $this->assertSame(0, FeaturedItem::count(), 'prune() clears rows whose title no longer exists.');
    }

    public function test_featuring_does_not_change_the_movies_listing_banner(): void
    {
        // Scope guard: Rio asked for the homepage hero only. /movie has its
        // own banner query and must not follow the featured list.
        $popular = $this->publishedMovie(['title' => 'Popular on listing', 'views_count' => 9999]);
        $curated = $this->publishedMovie(['title' => 'Curated for home', 'views_count' => 1]);
        $this->feature($curated, 0);

        $this->get(route('frontend.movie'))->assertOk()->assertSee('Popular on listing');
        $this->assertSame(9999, $popular->fresh()->views_count);
    }

    public function test_featured_series_with_no_playable_episode_is_held_back(): void
    {
        // The realistic mistake: an admin features a new series the day
        // before its episodes go up. A banner linking to nothing is worse
        // than no banner, so the hero skips it until an episode is live.
        $empty = Show::factory()->create([
            'title' => 'Announced, not aired',
            'status' => Show::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
        $this->feature($empty, 0);

        $this->assertTrue(FeaturedItem::heroItems()->isEmpty());

        // But the admin screen still shows it, flagged, so the reason is findable.
        $this->actingAs($this->admin())
            ->get(route('admin.featured.index'))
            ->assertOk()
            ->assertSee('Announced, not aired');
    }

    public function test_admin_screen_does_not_leak_a_model_into_the_layout(): void
    {
        // The admin header partial renders a $title variable. A Blade view
        // shares its variables with the layout, so a loop that assigned
        // $title dumped the whole Eloquent record as JSON across the top of
        // the page (Eloquent stringifies to JSON). Caught by rendering.
        $this->feature($this->publishedMovie(['title' => 'Leak check']), 0);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.featured.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('runtime_minutes', $html, 'A model was stringified into the page.');
        $this->assertStringNotContainsString('&quot;slug&quot;:', $html);
    }

    public function test_the_featured_screen_is_admin_only(): void
    {
        $this->get(route('admin.featured.index'))->assertRedirect();

        $viewer = User::factory()->create();
        $this->actingAs($viewer)->get(route('admin.featured.index'))->assertForbidden();
    }

    private function admin(): User
    {
        $user = User::factory()->create([
            'username' => 'admin_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@test.local',
        ]);
        $user->assignRole('admin');

        return $user;
    }

    private function publishedMovie(array $attributes = []): Movie
    {
        return Movie::factory()->create($attributes + [
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * A series counts as published only when it has an episode that is
     * itself published (Show::scopePublished), so the helper builds one.
     * A series without that is a dead-end banner, which is exactly what
     * test_featured_series_with_no_playable_episode_is_held_back covers.
     */
    private function publishedShow(array $attributes = []): Show
    {
        $show = Show::factory()->create($attributes + [
            'status' => Show::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'Season 1']);
        Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Episode 1',
            'published_at' => now()->subDay(),
        ]);

        return $show->refresh();
    }

    private function feature(Movie|Show $model, int $order): FeaturedItem
    {
        return FeaturedItem::create([
            'featurable_type' => $model->getMorphClass(),
            'featurable_id' => $model->id,
            'sort_order' => $order,
        ]);
    }
}
