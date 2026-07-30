<?php

namespace Modules\Content\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Subscriptions\app\Models\SubscriptionTier;
use Modules\Subscriptions\app\Support\ContentTiers;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bulk plan assignment for the admin catalogue, plus the list-state
 * threading that keeps an admin on their page after an edit.
 *
 * The monetisation-critical case is the series cascade: a show row's
 * `tier_required` is only half a paywall, because episodes carry their own
 * column and anything NULL there reads as free to TierGate.
 */
class BulkPlanAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);

        // Tiers live in a seeder, so RefreshDatabase leaves the table empty.
        // Mirrors the real spread: several distinct plans at one access level.
        foreach ([
            ['Free', 'free', 0, 0, 10],
            ['Day Pass', 'day-pass', 1500, 1, 15],
            ['Basic Monthly', 'basic', 15000, 1, 20],
            ['Basic Yearly', 'basic-yearly', 150000, 1, 22],
            ['Premium Monthly', 'premium', 30000, 2, 30],
        ] as [$name, $slug, $price, $level, $order]) {
            SubscriptionTier::create([
                'name' => $name,
                'slug' => $slug,
                'price' => $price,
                'currency' => 'UGX',
                'billing_period' => 'monthly',
                'access_level' => $level,
                'is_active' => true,
                'sort_order' => $order,
            ]);
        }
    }

    private function admin(): User
    {
        $user = User::factory()->create([
            'username' => 'admin_' . uniqid(),
            'email'    => 'admin_' . uniqid() . '@test.local',
        ]);

        $user->assignRole('admin');

        return $user;
    }

    private function seriesWithEpisodes(string $title, ?string $tier = null, int $episodes = 3): Show
    {
        $show = Show::create([
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'status' => 'published',
            'tier_required' => $tier,
        ]);

        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'Season 1']);

        for ($i = 1; $i <= $episodes; $i++) {
            Episode::create([
                'season_id' => $season->id,
                'number' => $i,
                'title' => "Episode $i",
                'tier_required' => null,
            ]);
        }

        return $show;
    }

    /* ------------------------------------------------------------------ */
    /* Movies                                                             */
    /* ------------------------------------------------------------------ */

    public function test_bulk_assigns_a_plan_to_the_selected_movies_only(): void
    {
        $a = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published']);
        $b = Movie::create(['title' => 'B', 'slug' => 'b', 'status' => 'published']);
        $untouched = Movie::create(['title' => 'C', 'slug' => 'c', 'status' => 'published']);

        $this->actingAs($this->admin())
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$a->id, $b->id],
                'tier_required' => 'premium',
            ])
            ->assertRedirect();

        $this->assertSame('premium', $a->fresh()->tier_required);
        $this->assertSame('premium', $b->fresh()->tier_required);
        $this->assertNull($untouched->fresh()->tier_required, 'Unselected movie must not change.');
    }

    public function test_free_choice_stores_null_rather_than_the_free_slug(): void
    {
        // Storing 'free' would be truthy, and roughly a dozen views treat a
        // non-null tier_required as "draw the PREMIUM ribbon".
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published', 'tier_required' => 'premium']);

        $this->actingAs($this->admin())
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$movie->id],
                'tier_required' => ContentTiers::FREE,
            ])
            ->assertRedirect();

        $this->assertNull($movie->fresh()->tier_required);
    }

    public function test_selecting_the_level_zero_tier_also_stores_null(): void
    {
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published', 'tier_required' => 'premium']);

        $this->actingAs($this->admin())
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$movie->id],
                'tier_required' => 'free',
            ]);

        $this->assertNull($movie->fresh()->tier_required);
    }

    public function test_an_empty_plan_choice_is_rejected_instead_of_setting_everything_free(): void
    {
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published', 'tier_required' => 'premium']);

        $this->actingAs($this->admin())
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$movie->id],
                'tier_required' => '',
            ])
            ->assertSessionHasErrors('tier_required');

        $this->assertSame('premium', $movie->fresh()->tier_required, 'A blank picker must be a no-op.');
    }

    public function test_an_unknown_plan_slug_is_rejected(): void
    {
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published']);

        $this->actingAs($this->admin())
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$movie->id],
                'tier_required' => 'not-a-tier',
            ])
            ->assertSessionHasErrors('tier_required');

        $this->assertNull($movie->fresh()->tier_required);
    }

    /* ------------------------------------------------------------------ */
    /* Series cascade                                                     */
    /* ------------------------------------------------------------------ */

    public function test_bulk_plan_on_a_series_cascades_to_every_episode(): void
    {
        $show = $this->seriesWithEpisodes('Cascade Show', null, 4);
        $other = $this->seriesWithEpisodes('Other Show', null, 2);

        $this->actingAs($this->admin())
            ->patch(route('admin.series.bulk-tier'), [
                'ids' => [$show->id],
                'tier_required' => 'premium',
            ])
            ->assertRedirect();

        $this->assertSame('premium', $show->fresh()->tier_required);

        $episodeTiers = Episode::whereIn(
            'season_id',
            Season::where('show_id', $show->id)->pluck('id')
        )->pluck('tier_required')->unique()->values()->all();

        $this->assertSame(['premium'], $episodeTiers, 'Every episode must inherit the series plan.');

        // The other series and its episodes stay untouched.
        $this->assertNull($other->fresh()->tier_required);
        $this->assertSame([null], Episode::whereIn(
            'season_id',
            Season::where('show_id', $other->id)->pluck('id')
        )->pluck('tier_required')->unique()->values()->all());
    }

    public function test_cascade_overwrites_a_stale_per_episode_plan(): void
    {
        $show = $this->seriesWithEpisodes('Stale Show', 'premium', 2);
        $seasonId = Season::where('show_id', $show->id)->value('id');

        // An episode left on an older, cheaper plan.
        $stale = Episode::where('season_id', $seasonId)->first();
        $stale->update(['tier_required' => 'basic']);

        $this->actingAs($this->admin())
            ->patch(route('admin.series.bulk-tier'), [
                'ids' => [$show->id],
                'tier_required' => 'premium',
            ]);

        $this->assertSame('premium', $stale->fresh()->tier_required);
    }

    public function test_setting_a_series_free_clears_its_episodes_too(): void
    {
        $show = $this->seriesWithEpisodes('Freed Show', 'premium', 3);
        Episode::whereIn('season_id', Season::where('show_id', $show->id)->pluck('id'))
            ->update(['tier_required' => 'premium']);

        $this->actingAs($this->admin())
            ->patch(route('admin.series.bulk-tier'), [
                'ids' => [$show->id],
                'tier_required' => ContentTiers::FREE,
            ]);

        $this->assertNull($show->fresh()->tier_required);
        $this->assertSame([null], Episode::whereIn(
            'season_id',
            Season::where('show_id', $show->id)->pluck('id')
        )->pluck('tier_required')->unique()->values()->all());
    }

    /* ------------------------------------------------------------------ */
    /* Episodes (per-season bulk)                                         */
    /* ------------------------------------------------------------------ */

    public function test_bulk_assigns_a_plan_to_selected_episodes_of_a_season(): void
    {
        $show = $this->seriesWithEpisodes('Ep Show', 'premium', 4);
        $season = Season::where('show_id', $show->id)->first();
        $episodes = Episode::where('season_id', $season->id)->orderBy('number')->get();

        $picked = $episodes->take(2);
        $left = $episodes->skip(2);

        $this->actingAs($this->admin())
            ->patch(route('admin.series.seasons.episodes.bulk-tier', [$show, $season]), [
                'ids' => $picked->pluck('id')->all(),
                'tier_required' => 'basic',
            ])
            ->assertRedirect(route('admin.series.seasons.edit', [$show, $season]));

        foreach ($picked as $e) {
            $this->assertSame('basic', $e->fresh()->tier_required);
        }
        foreach ($left as $e) {
            $this->assertNull($e->fresh()->tier_required, 'Unselected episodes must not change.');
        }
    }

    public function test_episode_bulk_cannot_touch_episodes_of_another_season(): void
    {
        // The endpoint is scoped to the season in the URL, so a posted id from
        // elsewhere in the catalogue must be ignored rather than repriced.
        $showA = $this->seriesWithEpisodes('Season Scope A', 'premium', 2);
        $showB = $this->seriesWithEpisodes('Season Scope B', 'premium', 2);

        $seasonA = Season::where('show_id', $showA->id)->first();
        $foreign = Episode::whereIn('season_id', Season::where('show_id', $showB->id)->pluck('id'))->first();
        $mine = Episode::where('season_id', $seasonA->id)->first();

        $this->actingAs($this->admin())
            ->patch(route('admin.series.seasons.episodes.bulk-tier', [$showA, $seasonA]), [
                'ids' => [$mine->id, $foreign->id],
                'tier_required' => 'basic',
            ]);

        $this->assertSame('basic', $mine->fresh()->tier_required);
        $this->assertNull($foreign->fresh()->tier_required, 'Foreign episode must be untouched.');
    }

    public function test_episode_bulk_can_set_a_free_pilot_inside_a_premium_run(): void
    {
        $show = $this->seriesWithEpisodes('Pilot Show', 'premium', 3);
        $season = Season::where('show_id', $show->id)->first();
        Episode::where('season_id', $season->id)->update(['tier_required' => 'premium']);

        $pilot = Episode::where('season_id', $season->id)->orderBy('number')->first();

        $this->actingAs($this->admin())
            ->patch(route('admin.series.seasons.episodes.bulk-tier', [$show, $season]), [
                'ids' => [$pilot->id],
                'tier_required' => ContentTiers::FREE,
            ]);

        $this->assertNull($pilot->fresh()->tier_required);
        $this->assertSame(
            2,
            Episode::where('season_id', $season->id)->where('tier_required', 'premium')->count(),
            'The rest of the run stays premium.'
        );
    }

    /* ------------------------------------------------------------------ */
    /* Attribution                                                        */
    /* ------------------------------------------------------------------ */

    public function test_a_bulk_plan_change_is_attributed_to_the_admin_who_made_it(): void
    {
        // Paywalling a title is a commercial decision; it needs an audit trail.
        $admin = $this->admin();
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published']);

        $this->actingAs($admin)
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$movie->id],
                'tier_required' => 'premium',
            ]);

        $this->assertSame($admin->id, $movie->fresh()->updated_by);

        $this->assertDatabaseHas('content_activity_log', [
            'actor_id' => $admin->id,
            'action' => 'updated',
            'content_type' => 'movie',
            'content_id' => $movie->id,
        ]);
    }

    public function test_a_series_cascade_stamps_the_admin_on_the_episodes_too(): void
    {
        $admin = $this->admin();
        $show = $this->seriesWithEpisodes('Attributed Show', null, 3);

        $this->actingAs($admin)
            ->patch(route('admin.series.bulk-tier'), [
                'ids' => [$show->id],
                'tier_required' => 'premium',
            ]);

        $episodeEditors = Episode::whereIn(
            'season_id',
            Season::where('show_id', $show->id)->pluck('id')
        )->pluck('updated_by')->unique()->values()->all();

        $this->assertSame([$admin->id], $episodeEditors);
    }

    /* ------------------------------------------------------------------ */
    /* Authorisation                                                      */
    /* ------------------------------------------------------------------ */

    public function test_a_non_admin_cannot_bulk_assign_plans(): void
    {
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$movie->id],
                'tier_required' => 'premium',
            ])
            ->assertForbidden();

        $this->assertNull($movie->fresh()->tier_required);
    }

    /* ------------------------------------------------------------------ */
    /* Plan labels                                                        */
    /* ------------------------------------------------------------------ */

    public function test_plan_label_reports_the_access_level_not_the_slug(): void
    {
        // The whole point: four different plans, one paywall. A poster must
        // not read "BASIC-YEARLY".
        $this->assertSame('Basic', ContentTiers::label('basic'));
        $this->assertSame('Basic', ContentTiers::label('basic-yearly'));
        $this->assertSame('Basic', ContentTiers::label('day-pass'));
        $this->assertSame('Premium', ContentTiers::label('premium'));
        $this->assertNull(ContentTiers::label(null));
        $this->assertNull(ContentTiers::label('free'), 'Level 0 is free, so no badge.');

        $movie = Movie::create([
            'title' => 'A', 'slug' => 'a', 'status' => 'published',
            'tier_required' => 'basic-yearly',
        ]);

        $this->assertSame('Basic', $movie->plan_label);
    }

    public function test_plan_labels_for_a_page_of_rows_cost_one_query(): void
    {
        // label() is called once per rendered card, so an uncached lookup made
        // a 15-row list 15 extra queries and the home page's rails dozens.
        Movie::factory()->count(15)->create(['tier_required' => 'premium']);
        $movies = Movie::query()->limit(15)->get();

        ContentTiers::flush();

        $tierQueries = 0;
        \Illuminate\Support\Facades\DB::listen(function ($q) use (&$tierQueries) {
            if (str_contains($q->sql, 'subscription_tiers')) {
                $tierQueries++;
            }
        });

        foreach ($movies as $m) {
            $m->plan_label;
        }

        $this->assertSame(1, $tierQueries, 'The tier table should be read once per request, not once per row.');
    }

    public function test_editing_a_tier_invalidates_the_memo(): void
    {
        // Without the flush hook, a renamed or deactivated plan would keep
        // resolving to its old value for the rest of the request.
        $this->assertSame('Premium', ContentTiers::label('premium'));

        SubscriptionTier::where('slug', 'premium')->first()
            ->update(['access_level' => SubscriptionTier::ACCESS_BASIC]);

        $this->assertSame('Basic', ContentTiers::label('premium'));
    }

    public function test_an_inactive_tier_still_resolves_its_access_level(): void
    {
        // Content can reference a plan an operator has since switched off; it
        // must keep gating rather than silently reading as free.
        SubscriptionTier::where('slug', 'premium')->first()->update(['is_active' => false]);

        $this->assertSame('Premium', ContentTiers::label('premium'));
        $this->assertNotContains('premium', ContentTiers::pickerOptions()->pluck('slug')->all());
    }

    /* ------------------------------------------------------------------ */
    /* List state (the "send me back to page 2" behaviour)                */
    /* ------------------------------------------------------------------ */

    public function test_edit_links_carry_the_current_page_and_filters(): void
    {
        Movie::factory()->count(20)->create(['status' => 'published']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.movies.index', ['page' => 2, 'status' => 'published']));

        $response->assertOk();
        // Every row's Edit link must preserve where the admin is standing.
        $response->assertSee('page=2', false);
        $response->assertSee('status=published', false);
    }

    public function test_saving_a_movie_returns_to_the_page_it_was_edited_from(): void
    {
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published']);
        $admin = $this->admin();

        // Visiting the list is what stashes the session fallback.
        $this->actingAs($admin)->get(route('admin.movies.index', ['page' => 2]));

        $response = $this->actingAs($admin)->put(
            route('admin.movies.update', ['movie' => $movie, 'page' => 2]),
            ['title' => 'A renamed', 'status' => 'published']
        );

        // Re-resolve the model: renaming re-slugs it, and the route key is
        // the slug. What matters here is that ?page=2 survived the save.
        $response->assertRedirect(route('admin.movies.edit', ['movie' => $movie->fresh(), 'page' => 2]));
    }

    public function test_bulk_action_returns_to_the_page_it_was_launched_from(): void
    {
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published']);
        $admin = $this->admin();

        // No query string on the POST — this is the case URL threading can't
        // cover, so the session fallback has to carry it.
        $this->actingAs($admin)->get(route('admin.movies.index', ['page' => 3, 'q' => 'a']));

        $this->actingAs($admin)
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$movie->id],
                'tier_required' => 'premium',
            ])
            ->assertRedirect(route('admin.movies.index', ['page' => 3, 'q' => 'a']));
    }

    public function test_page_one_is_not_pinned_into_urls(): void
    {
        $admin = $this->admin();
        $movie = Movie::create(['title' => 'A', 'slug' => 'a', 'status' => 'published']);

        $this->actingAs($admin)->get(route('admin.movies.index', ['page' => 1]));

        $this->actingAs($admin)
            ->patch(route('admin.movies.bulk-tier'), [
                'ids' => [$movie->id],
                'tier_required' => 'premium',
            ])
            ->assertRedirect(route('admin.movies.index'));
    }
}
