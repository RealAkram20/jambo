<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Content\app\Models\Category;
use Modules\Content\app\Models\Episode;
use Modules\Content\app\Models\Genre;
use Modules\Content\app\Models\Movie;
use Modules\Content\app\Models\Person;
use Modules\Content\app\Models\Season;
use Modules\Content\app\Models\Show;
use Modules\Content\app\Models\Vj;
use Modules\Streaming\app\Models\Device;
use Modules\Streaming\app\Models\WatchHistoryItem;
use Modules\Streaming\app\Models\WatchlistItem;
use Tests\TestCase;

/**
 * The taxonomy archive screens and the viewer's own lists.
 *
 * Two things carry most of the weight here. Taxonomy pages must list only
 * published titles — an archive is the easiest place to leak a draft, because
 * the relation is the obvious query and the scope is the easy thing to forget.
 * And the watchlist writes must be idempotent, because the app retries over a
 * connection that drops, and a toggle would silently undo itself.
 */
class TaxonomyAndWatchlistTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';
    private const VIDEO = 'https://origin.example.test/secret-master-file.mp4';

    // ── taxonomy ─────────────────────────────────────────────────────

    public function test_genres_list_and_detail(): void
    {
        [$movie] = $this->seedCatalogue();
        $genre = Genre::create(['name' => 'Drama', 'slug' => 'drama']);
        $movie->genres()->attach($genre->id);

        $this->getJson('/api/v1/genres')
            ->assertOk()
            ->assertJsonPath('data.genres.0.slug', 'drama')
            ->assertJsonPath('data.genres.0.movies_count', 1);

        $this->getJson('/api/v1/genres/drama')
            ->assertOk()
            ->assertJsonPath('data.genre.name', 'Drama')
            ->assertJsonCount(1, 'data.movies')
            ->assertJsonPath('data.movies.0.slug', $movie->slug);
    }

    public function test_a_genre_page_never_lists_a_draft_title(): void
    {
        $this->seedCatalogue();
        $genre = Genre::create(['name' => 'Horror', 'slug' => 'horror']);

        $draft = Movie::factory()->create([
            'status' => Movie::STATUS_DRAFT,
            'published_at' => null,
            'title' => 'Unreleased Horror',
        ]);
        $draft->genres()->attach($genre->id);

        $response = $this->getJson('/api/v1/genres/horror')->assertOk();

        $this->assertCount(0, $response->json('data.movies'));
        $this->assertStringNotContainsString('Unreleased Horror', $response->content());
    }

    public function test_categories_list_and_detail(): void
    {
        [$movie] = $this->seedCatalogue();
        $category = Category::create([
            'name' => 'Ugandan Classics',
            'slug' => 'ugandan-classics',
            'visible_home' => true,
            'sort_order' => 1,
        ]);
        $movie->categories()->attach($category->id);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.categories.0.slug', 'ugandan-classics')
            ->assertJsonPath('data.categories.0.visible_home', true);

        $this->getJson('/api/v1/categories/ugandan-classics')
            ->assertOk()
            ->assertJsonCount(1, 'data.movies');
    }

    public function test_vjs_list_only_includes_those_with_published_work(): void
    {
        [$movie] = $this->seedCatalogue();

        $working = Vj::create(['name' => 'VJ Junior', 'slug' => 'vj-junior']);
        $movie->vjs()->attach($working->id);

        Vj::create(['name' => 'VJ Nobody', 'slug' => 'vj-nobody']);

        $slugs = collect($this->getJson('/api/v1/vjs')->assertOk()->json('data.vjs'))->pluck('slug');

        $this->assertContains('vj-junior', $slugs->all());
        $this->assertNotContains(
            'vj-nobody',
            $slugs->all(),
            'A VJ whose entire catalogue is draft must not be listed.'
        );
    }

    public function test_a_vj_page_carries_their_titles_and_links(): void
    {
        [$movie] = $this->seedCatalogue();
        $vj = Vj::create([
            'name' => 'VJ Junior',
            'slug' => 'vj-junior',
            'youtube_url' => 'https://youtube.com/@vjjunior',
        ]);
        $movie->vjs()->attach($vj->id);

        $this->getJson('/api/v1/vjs/vj-junior')
            ->assertOk()
            ->assertJsonPath('data.vj.name', 'VJ Junior')
            ->assertJsonPath('data.vj.links.youtube', 'https://youtube.com/@vjjunior')
            ->assertJsonCount(1, 'data.movies');
    }

    public function test_a_cast_page_uses_the_full_name(): void
    {
        [$movie] = $this->seedCatalogue();
        $person = Person::create([
            'first_name' => 'Ashraf',
            'last_name' => 'Ssemwogerere',
            'slug' => 'ashraf-ssemwogerere',
        ]);
        $movie->cast()->attach($person->id, ['role' => 'actor']);

        $this->getJson('/api/v1/cast/ashraf-ssemwogerere')
            ->assertOk()
            ->assertJsonPath('data.person.name', 'Ashraf Ssemwogerere')
            ->assertJsonCount(1, 'data.movies');
    }

    public function test_unknown_taxonomy_slugs_are_clean_not_founds(): void
    {
        foreach (['genres', 'categories', 'vjs'] as $kind) {
            $this->getJson("/api/v1/{$kind}/no-such-thing")
                ->assertStatus(404)
                ->assertJsonPath('code', 'NOT_FOUND');
        }

        $this->getJson('/api/v1/cast/no-such-person')->assertStatus(404);
    }

    public function test_taxonomy_pages_never_contain_a_video_url(): void
    {
        [$movie] = $this->seedCatalogue();
        $genre = Genre::create(['name' => 'Drama', 'slug' => 'drama']);
        $movie->genres()->attach($genre->id);

        $body = $this->getJson('/api/v1/genres/drama')->assertOk()->content();

        $this->assertStringNotContainsString(self::VIDEO, $body);
        $this->assertStringNotContainsString('video_url', $body);
    }

    // ── watchlist ────────────────────────────────────────────────────

    public function test_the_watchlist_needs_a_signed_in_viewer(): void
    {
        $this->getJson('/api/v1/watchlist')
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_adding_and_listing_a_title(): void
    {
        [$movie] = $this->seedCatalogue();
        $token = $this->signIn($this->viewer());

        $this->withFreshToken($token)
            ->postJson('/api/v1/watchlist', ['type' => 'movie', 'id' => $movie->id])
            ->assertOk()
            ->assertJsonPath('data.in_list', true);

        $this->withFreshToken($token)
            ->getJson('/api/v1/watchlist')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.slug', $movie->slug);
    }

    /**
     * The app retries over a connection that drops. A second add must not
     * create a duplicate, and a second remove must not fail — that is the
     * whole reason this is not the website's toggle endpoint.
     */
    public function test_adding_twice_is_safe_and_creates_one_row(): void
    {
        [$movie] = $this->seedCatalogue();
        $user = $this->viewer();
        $token = $this->signIn($user);

        foreach ([1, 2] as $ignored) {
            $this->withFreshToken($token)
                ->postJson('/api/v1/watchlist', ['type' => 'movie', 'id' => $movie->id])
                ->assertOk()
                ->assertJsonPath('data.in_list', true);
        }

        $this->assertSame(1, WatchlistItem::where('user_id', $user->id)->count());
    }

    public function test_removing_twice_is_safe(): void
    {
        [$movie] = $this->seedCatalogue();
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->postJson('/api/v1/watchlist', ['type' => 'movie', 'id' => $movie->id]);

        foreach ([1, 2] as $ignored) {
            $this->withFreshToken($token)
                ->deleteJson("/api/v1/watchlist/movie/{$movie->id}")
                ->assertOk()
                ->assertJsonPath('data.in_list', false);
        }

        $this->assertSame(0, WatchlistItem::where('user_id', $user->id)->count());
    }

    public function test_one_viewers_watchlist_is_not_anothers(): void
    {
        [$movie] = $this->seedCatalogue();
        $mine = $this->signIn($this->viewer(), 'device-uuid-mine1');
        $theirs = $this->signIn($this->viewer(), 'device-uuid-thei1');

        $this->withFreshToken($mine)->postJson('/api/v1/watchlist', ['type' => 'movie', 'id' => $movie->id]);

        $this->withFreshToken($theirs)
            ->getJson('/api/v1/watchlist')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    public function test_an_unknown_title_cannot_be_added(): void
    {
        $this->seedCatalogue();

        $this->withFreshToken($this->signIn($this->viewer()))
            ->postJson('/api/v1/watchlist', ['type' => 'movie', 'id' => 999999])
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    // ── continue watching and history ────────────────────────────────

    public function test_continue_watching_matches_the_home_rail(): void
    {
        [$movie] = $this->seedCatalogue();
        $user = $this->viewer();
        $token = $this->signIn($user);

        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'position_seconds' => 250,
            'duration_seconds' => 3000,
            'completed' => false,
            'watched_at' => now(),
        ]);

        $this->withFreshToken($token)
            ->getJson('/api/v1/continue-watching')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.resume.type', 'movie')
            ->assertJsonPath('data.items.0.resume.id', $movie->id)
            ->assertJsonPath('data.items.0.position_seconds', 250);
    }

    /**
     * History is a record, not a to-do list: unlike Continue Watching it
     * keeps what the viewer already finished.
     */
    public function test_history_includes_finished_titles(): void
    {
        [$movie] = $this->seedCatalogue();
        $user = $this->viewer();
        $token = $this->signIn($user);

        WatchHistoryItem::create([
            'user_id' => $user->id,
            'watchable_type' => $movie->getMorphClass(),
            'watchable_id' => $movie->id,
            'position_seconds' => 3000,
            'duration_seconds' => 3000,
            'completed' => true,
            'watched_at' => now(),
        ]);

        $this->withFreshToken($token)
            ->getJson('/api/v1/continue-watching')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->withFreshToken($token)
            ->getJson('/api/v1/history')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.completed', true)
            ->assertJsonPath('data.items.0.item.slug', $movie->slug);
    }

    // ── fixtures ─────────────────────────────────────────────────────

    /** @return array{0: Movie, 1: Show} */
    private function seedCatalogue(): array
    {
        $movie = Movie::factory()->create([
            'status' => Movie::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'video_url' => self::VIDEO,
        ]);

        $show = Show::create([
            'title' => 'Taxonomy Series',
            'slug' => 'taxonomy-series-' . uniqid(),
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $season = Season::create(['show_id' => $show->id, 'number' => 1, 'title' => 'S1']);
        Episode::create([
            'season_id' => $season->id,
            'number' => 1,
            'title' => 'Pilot',
            'published_at' => now()->subDay(),
            'video_url' => self::VIDEO,
        ]);

        return [$movie, $show];
    }

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-tax01'): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => $uuid, 'platform' => Device::PLATFORM_ANDROID],
        ])->assertOk()->json('data.token');
    }

    /** See AuthAndDevicesTest: the guard caches its user across requests. */
    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
