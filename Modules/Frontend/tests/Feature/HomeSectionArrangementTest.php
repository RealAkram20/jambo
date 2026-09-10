<?php

namespace Modules\Frontend\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Frontend\app\Models\HomeSection;
use Modules\Streaming\app\Models\Device;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The admin's arrangement of the app's home screen.
 *
 * Four properties matter more than the CRUD, and each of them is a way this
 * feature could quietly ruin a home screen rather than fail loudly:
 *
 *   1. **Deploying it changes nothing.** The seeded arrangement is exactly the
 *      order the controller already built, so the home screen is identical
 *      until an admin drags something.
 *   2. **A drag is the render order.** That is the whole point.
 *   3. **A section switched off disappears from the app and nothing else
 *      does.** In particular, disabling Continue Watching hides a shelf; it
 *      must not remove the ability to resume, which is a separate endpoint.
 *   4. **A rail with no row still renders, last.** A rail added in code before
 *      its row exists must never vanish, or a deploy order silently loses a
 *      section from the home screen and nobody gets an error.
 *
 * Since ADR-0007 the same rows govern the WEBSITE's home page, so a fifth
 * property joins them: **both surfaces obey one list.** The way this feature
 * fails is not an exception, it is one surface quietly drifting from the
 * other, which is the state it was built to end. The website tests at the
 * bottom are that guard.
 *
 * Nothing under mobile/ is involved. The app already renders whatever ordered
 * list it is given.
 */
class HomeSectionArrangementTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    private const VIDEO = 'https://origin.example.test/secret-master-file.mp4';

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['title' => 'Administrator']);
    }

    /**
     * Property 1. The table ships holding today's order, so shipping it is
     * invisible. Proved by comparing the arranged response against the order
     * the controller builds with no table at all — if the seed drifts from the
     * controller's literal list, these stop matching.
     */
    public function test_the_seeded_arrangement_is_exactly_the_order_the_code_already_built(): void
    {
        $this->seedCatalogue();

        $arranged = $this->railKeys();
        $this->assertNotEmpty($arranged, 'The fixture must produce rails or every assertion here is vacuous.');

        // With no rows at all, arrange() falls back to the controller's own
        // order. That is the "before this feature" baseline.
        HomeSection::query()->delete();
        $codeOrder = $this->railKeys();

        $this->assertSame(
            $codeOrder,
            $arranged,
            'The seeded arrangement must be exactly what the controller built before this table existed.',
        );
    }

    /**
     * 🔴 The one that would have caught what this session shipped.
     *
     * The code landed before the migration ran and `GET /api/v1/home` returned
     * a raw QueryException to every device in the field. A deploy that runs
     * code before migrations must degrade to the built-in order, not fail: the
     * home screen is the first request an app makes, and there is no screen
     * behind it to fall back to.
     */
    public function test_home_still_serves_todays_order_when_the_table_does_not_exist(): void
    {
        $this->seedCatalogue();

        $expected = $this->railKeys();
        $this->assertNotEmpty($expected);

        // Exactly the state of a server between the code deploy and `migrate`.
        Schema::drop('home_sections');
        $this->assertFalse(Schema::hasTable('home_sections'));

        $response = $this->getJson('/api/v1/home')->assertOk();

        $this->assertSame(
            $expected,
            collect($response->json('data.rails'))->pluck('key')->all(),
            'With no table, the home screen must serve the order the controller builds.',
        );
    }

    /**
     * The admin screen names each section from HomeSection::DEFAULTS while the
     * controller still builds its headings from literal `__()` calls. This
     * pins the two together: a heading changed in one place and not the other
     * would otherwise show an admin a name the app does not use.
     */
    public function test_every_rail_carries_the_heading_its_section_row_names(): void
    {
        $this->seedCatalogue();

        $rails = $this->getJson('/api/v1/home')->assertOk()->json('data.rails');
        $checked = 0;

        $banners = 0;

        foreach ($rails as $rail) {
            // A category shelf carries the category's own name, not the
            // grouped row's, and that is deliberate.
            if (str_starts_with($rail['key'], 'category:')) {
                continue;
            }

            $this->assertArrayHasKey(
                $rail['key'],
                HomeSection::DEFAULTS,
                "Rail '{$rail['key']}' has no row in HomeSection::DEFAULTS, so the admin screen cannot name it.",
            );

            // A banner is the width of the screen and carries no shelf title
            // on the website, so it sends none — the admin row still names it,
            // which is why the assertion above applies to banners too. Sending
            // a heading nothing should draw is how one ends up drawn.
            if (($rail['kind'] ?? null) === 'banner') {
                $this->assertArrayNotHasKey(
                    'title',
                    $rail,
                    "Banner rail '{$rail['key']}' sends a heading no surface draws.",
                );
                $banners++;

                continue;
            }

            $this->assertSame(
                HomeSection::defaultTitle($rail['key']),
                $rail['title'],
                "Rail '{$rail['key']}' renders a heading the admin screen does not show.",
            );

            $checked++;
        }

        $this->assertGreaterThan(0, $checked);
        $this->assertSame(2, $banners, 'Both daily banners must reach the app.');
    }

    /**
     * Property 2, through the real endpoint rather than by writing positions
     * directly — the drag contract is the thing under test.
     */
    public function test_a_dragged_order_is_the_order_the_app_receives(): void
    {
        $this->seedCatalogue();

        $before = $this->railKeys();

        // The last non-category rail on the screen. Category rails are skipped
        // because they answer to one grouped row, which this test is not about.
        $target = collect($before)
            ->reject(fn (string $key) => str_starts_with($key, 'category:'))
            ->last();

        $this->assertNotNull($target);
        $this->assertNotSame($before[0], $target, 'The fixture must give us something that is not already first.');

        $ids = HomeSection::ordered()->pluck('id', 'key');
        $targetId = $ids[$target];

        // Drag it to the top: every id in the new display order, exactly the
        // contract FeaturedController::reorder() and CategoryController use.
        $order = $ids->values()
            ->reject(fn (int $id) => $id === $targetId)
            ->values()
            ->prepend($targetId)
            ->all();

        $this->actingAs($this->admin(), 'web')
            ->patchJson(route('admin.home-sections.reorder'), ['order' => $order])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $after = $this->railKeys();

        $this->assertSame($target, $after[0], 'The dragged section must be first on the app.');
        $this->assertSame(
            count($before),
            count($after),
            'Rearranging must move sections, never lose one.',
        );
        $this->assertEqualsCanonicalizing($before, $after);
    }

    /** Property 3. Switched off means gone from the app, not deleted. */
    public function test_a_disabled_section_does_not_reach_the_app(): void
    {
        $this->seedCatalogue();

        $victim = collect($this->railKeys())
            ->reject(fn (string $key) => str_starts_with($key, 'category:'))
            ->first();

        $this->assertNotNull($victim);

        $section = HomeSection::where('key', $victim)->firstOrFail();

        $this->actingAs($this->admin(), 'web')
            ->patchJson(route('admin.home-sections.toggle', $section), ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('enabled', false);

        $this->assertNotContains($victim, $this->railKeys(), 'A disabled section must not reach the app.');

        // Disabling never deletes: the arrangement survives being switched on.
        $this->assertDatabaseHas('home_sections', ['key' => $victim, 'enabled' => false]);

        $this->actingAs($this->admin(), 'web')
            ->patchJson(route('admin.home-sections.toggle', $section), ['enabled' => true])
            ->assertOk();

        $this->assertContains($victim, $this->railKeys(), 'Switching it back on must restore the shelf.');
    }

    /**
     * Property 4, and the one that protects a deploy. A rail whose row has not
     * been created yet must still render — last, so it cannot displace an
     * arrangement an admin has made, but present, so nothing is lost.
     */
    public function test_a_rail_with_no_section_row_sorts_last_rather_than_vanishing(): void
    {
        $this->seedCatalogue();

        $before = $this->railKeys();

        $orphan = collect($before)
            ->reject(fn (string $key) => str_starts_with($key, 'category:'))
            ->first();

        $this->assertNotNull($orphan);
        $this->assertNotSame($orphan, end($before), 'Pick a rail that is not already last or this proves nothing.');

        // Exactly the state a deploy produces: the rail exists in code, its
        // row does not exist yet.
        HomeSection::where('key', $orphan)->delete();

        $after = $this->railKeys();

        $this->assertContains($orphan, $after, 'A rail with no row must never be dropped.');
        $this->assertSame($orphan, end($after), 'A rail with no row sorts last.');
        $this->assertSame(count($before), count($after));
    }

    /**
     * Disabling Continue Watching hides a shelf. It must not touch resume:
     * the app's Continue Watching screen is a separate route calling
     * GET /continue-watching directly, and a viewer who loses the ability to
     * resume because an admin tidied the home screen is a support ticket
     * nobody would connect to this feature.
     */
    public function test_disabling_continue_watching_leaves_resume_working(): void
    {
        $this->seedCatalogue();

        $user = $this->viewer();
        $movie = Movie::published()->first();

        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'position_seconds' => 300,
            'duration_seconds' => 3600,
            'completed' => false,
            'watched_at' => now(),
        ]);

        $token = $this->signIn($user);

        // It is on the home screen to begin with, or the rest proves nothing.
        $this->assertContains('continue_watching', $this->railKeys($token));

        HomeSection::where('key', 'continue_watching')->update(['enabled' => false]);

        $this->assertNotContains('continue_watching', $this->railKeys($token));

        $items = $this->withFreshToken($token)
            ->getJson('/api/v1/continue-watching')
            ->assertOk()
            ->json('data.items');

        $this->assertCount(1, $items, 'Resume must survive an admin hiding the shelf.');
        $this->assertSame('movie', $items[0]['resume']['type']);
        $this->assertSame($movie->id, $items[0]['resume']['id']);
    }

    /** The screen writes the home page. It is admin-only, on every verb. */
    public function test_the_arrangement_screen_and_its_writes_are_admin_only(): void
    {
        $this->seedCatalogue();
        $section = HomeSection::query()->firstOrFail();

        // A browser is redirected to login; a JSON caller gets 401, because
        // the admin screen sends its reorder and toggle as JSON.
        $this->get(route('admin.home-sections.index'))->assertRedirect();
        $this->patchJson(route('admin.home-sections.reorder'), ['order' => [$section->id]])->assertUnauthorized();
        $this->patchJson(route('admin.home-sections.toggle', $section), ['enabled' => false])->assertUnauthorized();
        $this->assertDatabaseHas('home_sections', ['id' => $section->id, 'enabled' => true]);

        // A signed-in viewer is not an admin.
        $this->actingAs($this->viewer(), 'web')->get(route('admin.home-sections.index'))->assertForbidden();

        // The allowed case is not asserted here on purpose: switching users
        // inside one test makes AuthenticateSession invalidate the session and
        // redirect, which would fail for a reason that has nothing to do with
        // authorization. An admin reaching the screen is pinned by
        // test_the_admin_screen_lists_every_section_including_disabled_ones.
    }

    /**
     * The admin screen lists every section the app can render, including ones
     * that are switched off — hiding those would leave nobody able to work out
     * why a shelf stopped appearing.
     */
    public function test_the_admin_screen_lists_every_section_including_disabled_ones(): void
    {
        $this->seedCatalogue();
        HomeSection::where('key', 'latest_movies')->update(['enabled' => false]);

        $html = $this->actingAs($this->admin(), 'web')
            ->get(route('admin.home-sections.index'))
            ->assertOk()
            ->assertSee('latest_movies')
            ->assertSee('Hidden')
            ->content();

        foreach (array_keys(HomeSection::DEFAULTS) as $key) {
            $this->assertStringContainsString($key, $html, "The screen must list the '{$key}' section.");
        }

        // The Featured screen shipped a bug where a model reached the layout's
        // $title and printed as JSON across the page. Same shell, same guard.
        $this->assertStringNotContainsString('{"id":', $html);
    }

    /**
     * A rail added in code after this table was created gets its row the next
     * time an admin opens the screen, rather than needing its own migration.
     */
    public function test_the_admin_screen_creates_a_row_for_a_section_that_has_none(): void
    {
        $this->seedCatalogue();
        HomeSection::where('key', 'genres')->delete();

        $this->actingAs($this->admin(), 'web')->get(route('admin.home-sections.index'))->assertOk();

        $genres = HomeSection::where('key', 'genres')->firstOrFail();

        $this->assertTrue($genres->enabled);
        $this->assertSame(
            (int) HomeSection::query()->max('position'),
            $genres->position,
            'A newly discovered section lands at the end, where it cannot displace an arrangement.',
        );
    }

    // ── fixtures ─────────────────────────────────────────────────────

    /** @return array<int, string> */
    /**
     * Property 5, and the one that keeps the two surfaces from drifting again.
     *
     * A section is one row in DEFAULTS and one in WEB_VIEWS. If a key reaches
     * only one of them, one surface can render it and the other cannot, and
     * nothing anywhere would say so — which is the exact shape of the bug this
     * feature replaced, when the website drew twelve shelves and the app drew
     * eighteen under partly different names.
     */
    public function test_every_section_is_drawable_on_both_surfaces(): void
    {
        $this->assertSame(
            array_keys(HomeSection::DEFAULTS),
            array_keys(HomeSection::WEB_VIEWS),
            'Every section needs a heading key AND a website partial, in the same order.'
        );

        foreach (HomeSection::WEB_VIEWS as $key => $spec) {
            $view = 'frontend::components.sections.'.$spec['view'];

            $this->assertTrue(
                view()->exists($view),
                "Section {$key} maps to {$view}, which does not exist.",
            );
        }
    }

    /**
     * The drag is the website's order too.
     *
     * Proved by moving a shelf rather than by reading today's order: the
     * assertion is that swapping two rows swaps the two headings on the page,
     * so it cannot pass against a page that happens to be hard-coded that way.
     */
    public function test_a_dragged_order_is_the_order_the_website_renders(): void
    {
        $this->seedCatalogue();

        $before = $this->headingPositions();
        $this->assertLessThan(
            $before['popular'],
            $before['latest'],
            'Precondition: Latest Movies sits above Popular Movies in the seeded order.',
        );

        $latest = HomeSection::where('key', 'latest_movies')->firstOrFail();
        $popular = HomeSection::where('key', 'popular_movies')->firstOrFail();
        [$latest->position, $popular->position] = [$popular->position, $latest->position];
        $latest->save();
        $popular->save();

        $after = $this->headingPositions();
        $this->assertLessThan(
            $after['latest'],
            $after['popular'],
            'Dragging Popular Movies above Latest Movies must move it on the website.',
        );
    }

    /** A section switched off leaves the website as well as the app. */
    public function test_a_disabled_section_does_not_reach_the_website(): void
    {
        $this->seedCatalogue();

        $this->get('/')->assertOk()->assertSee(__('sectionTitle.popular_movies'), false);

        HomeSection::where('key', 'popular_movies')->update(['enabled' => false]);

        $this->get('/')->assertOk()->assertDontSee(__('sectionTitle.popular_movies'), false);
    }

    /** An admin's label renames the heading on the website, not just in the app. */
    public function test_an_admin_label_renames_the_website_heading(): void
    {
        $this->seedCatalogue();

        HomeSection::where('key', 'popular_movies')->update(['label' => 'Uganda Loves These']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Uganda Loves These', false)
            ->assertDontSee(__('sectionTitle.popular_movies'), false);
    }

    /**
     * Where two headings sit on the rendered home page.
     *
     * @return array{latest: int, popular: int}
     */
    private function headingPositions(): array
    {
        $html = $this->get('/')->assertOk()->getContent();

        $latest = strpos($html, __('sectionTitle.latest_movies'));
        $popular = strpos($html, __('sectionTitle.popular_movies'));

        $this->assertIsInt($latest, 'Latest Movies did not render at all.');
        $this->assertIsInt($popular, 'Popular Movies did not render at all.');

        return ['latest' => $latest, 'popular' => $popular];
    }

    private function railKeys(?string $token = null): array
    {
        $request = $token ? $this->withFreshToken($token) : $this;

        return collect($request->getJson('/api/v1/home')->assertOk()->json('data.rails'))
            ->pluck('key')
            ->all();
    }

    private function seedCatalogue(): void
    {
        $category = Category::create([
            'name' => 'Home Shelf',
            'slug' => 'home-shelf',
            'visible_home' => true,
            'sort_order' => 1,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $movie = Movie::factory()->create([
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => now()->subDays($i + 1),
                'tier_required' => $i === 0 ? 'premium' : null,
                'video_url' => self::VIDEO,
            ]);
            $movie->categories()->attach($category->id);
        }

        // Show::published() needs a published episode, or the series rails are
        // silently empty and half these assertions would pass for nothing.
        for ($i = 0; $i < 2; $i++) {
            $show = Show::create([
                'title' => 'Home Series ' . $i,
                'slug' => 'home-series-' . $i . '-' . uniqid(),
                'status' => 'published',
                'published_at' => now()->subDays($i + 1),
            ]);
            $show->categories()->attach($category->id);

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

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => 'device-uuid-sections1', 'platform' => Device::PLATFORM_ANDROID],
        ])->assertOk()->json('data.token');
    }

    /** See AuthAndDevicesTest: the guard caches its user across requests. */
    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
