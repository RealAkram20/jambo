<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Modules\Content\app\Models\Movie;
use Modules\Streaming\app\Models\Device;
use Tests\TestCase;

/**
 * Two defects found in the review pass over the finished API, both invisible to
 * the suite until it was pointed at real data.
 *
 * Pagination: a cursor on a non-unique column drops every row that ties. Seeded
 * and bulk-imported titles share a created_at to the second, and page 2 of
 * /collections/latest-movies came back EMPTY on the dev database with twenty
 * movies in it. Rails with a raw FIELD() order cannot build a cursor at all.
 *
 * Rate limits: the public catalogue was keyed on the address for guests, and
 * Google sign-in fell through to the address too. Behind carrier NAT that is
 * one bucket per cell tower.
 */
class PaginationAndLimitsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    // ── pagination ───────────────────────────────────────────────────

    /**
     * The exact failure from the review, reproduced: titles that share a
     * timestamp must all be reachable across pages.
     */
    public function test_a_collection_pages_through_titles_that_share_a_timestamp(): void
    {
        $sameSecond = now()->subDay();

        foreach (['Tied One', 'Tied Two', 'Tied Three'] as $title) {
            Movie::factory()->create([
                'title' => $title,
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => $sameSecond,
                'created_at' => $sameSecond,
                'video_url' => 'https://cdn.example.test/movie.mp4',
            ]);
        }

        $page1 = $this->getJson('/api/v1/collections/latest-movies?per_page=2')->assertOk();
        $page1->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.page', 1)
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.next_page', 2);

        $page2 = $this->getJson('/api/v1/collections/latest-movies?per_page=2&page=2')->assertOk();
        $page2->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.next_page', null);

        $seen = collect($page1->json('data.items'))->concat($page2->json('data.items'))->pluck('slug');
        $this->assertCount(3, $seen->unique(), 'Every tied title must appear exactly once across the pages.');
    }

    /** History is a cursor list; beats in the same second must not lose one. */
    public function test_history_pages_through_rows_that_share_a_timestamp(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);
        $sameSecond = now()->subHour();

        // One more than a page, all tied on watched_at.
        for ($i = 0; $i < 31; $i++) {
            $movie = Movie::factory()->create([
                'status' => Movie::STATUS_PUBLISHED,
                'published_at' => now()->subDay(),
                'video_url' => 'https://cdn.example.test/movie.mp4',
            ]);

            DB::table('watch_history')->insert([
                'user_id' => $user->id,
                'watchable_type' => $movie->getMorphClass(),
                'watchable_id' => $movie->id,
                'position_seconds' => 10,
                'completed' => false,
                'watched_at' => $sameSecond,
                'created_at' => $sameSecond,
                'updated_at' => $sameSecond,
            ]);
        }

        $page1 = $this->withFreshToken($token)->getJson('/api/v1/history')->assertOk();
        $this->assertCount(30, $page1->json('data.items'));
        $cursor = $page1->json('data.next_cursor');
        $this->assertNotNull($cursor, 'With 31 tied rows there must be a second page.');

        $page2 = $this->withFreshToken($token)->getJson('/api/v1/history?cursor=' . urlencode($cursor))->assertOk();
        $this->assertCount(1, $page2->json('data.items'), 'The tied row past the page boundary must not be dropped.');
    }

    public function test_the_cast_grid_pages_by_offset(): void
    {
        $this->getJson('/api/v1/cast')
            ->assertOk()
            ->assertJsonStructure(['data' => ['items', 'page', 'per_page', 'total', 'next_page']]);
    }

    // ── rate limits ──────────────────────────────────────────────────

    /**
     * Two handsets behind one address must not share one bucket. The app sends
     * X-Device-Id on every request; the limiter keys on it before the address.
     */
    public function test_guests_on_the_same_address_are_limited_per_device_not_per_address(): void
    {
        RateLimiter::clear('api|device-a');
        RateLimiter::clear('api|device-b');

        for ($i = 0; $i < 60; $i++) {
            $this->withHeader('X-Device-Id', 'device-a')->getJson('/api/v1/app/config')->assertOk();
        }

        $this->withHeader('X-Device-Id', 'device-a')->getJson('/api/v1/app/config')
            ->assertStatus(429);

        // A different handset, same address, is untouched.
        $this->withHeader('X-Device-Id', 'device-b')->getJson('/api/v1/app/config')->assertOk();
    }

    /**
     * Google sign-in sends no email and no challenge token. Without the
     * device fallback its throttle key collapsed to the address alone.
     */
    public function test_google_sign_in_is_throttled_per_device_not_per_address(): void
    {
        config(['services.google.client_id' => 'jambo-app-client-id']);

        $keyFor = fn (string $uuid) => $uuid . '|127.0.0.1';
        RateLimiter::clear($keyFor('phone-aaaaaaaa'));
        RateLimiter::clear($keyFor('phone-bbbbbbbb'));

        // No Google reachable in tests, so every attempt is a 401 - which
        // still counts against the bucket, exactly as a wrong password does.
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/google', [
                'id_token' => 'x', 'device' => ['uuid' => 'phone-aaaaaaaa', 'platform' => 'android'],
            ]);
        }

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'x', 'device' => ['uuid' => 'phone-aaaaaaaa', 'platform' => 'android'],
        ])->assertStatus(429);

        $this->postJson('/api/v1/auth/google', [
            'id_token' => 'x', 'device' => ['uuid' => 'phone-bbbbbbbb', 'platform' => 'android'],
        ])->assertStatus(401);
    }

    // ── the handler ──────────────────────────────────────────────────

    public function test_a_deliberate_401_abort_is_unauthenticated_not_a_server_error(): void
    {
        Route::middleware('api')->prefix('api/v1')->get('needs-login-for-testing', function () {
            abort(401, 'Sign in first.');
        });

        $this->getJson('/api/v1/needs-login-for-testing')
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-page01'): string
    {
        RateLimiter::clear(strtolower($user->email) . '|127.0.0.1');

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
            'device' => ['uuid' => $uuid, 'platform' => Device::PLATFORM_ANDROID],
        ])->assertOk()->json('data.token');
    }

    private function withFreshToken(string $token): self
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
