<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Support\IpLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Streaming\app\Models\Device;
use Tests\TestCase;

/**
 * Where a device is, and how many can watch at once.
 *
 * Both were added 2026-09-09 for the app's devices screen. Each replaces
 * something Rio's mockup asserted that the product could not: a city per row,
 * and a "4 of 6 devices" meter describing a cap Jambo does not have.
 *
 * **The location tests are mostly about NOT answering.** A wrong city on a
 * security screen is worse than no city — the line exists so somebody can say
 * "I have never been there" and act on it — so every path that cannot know
 * must return null rather than something plausible.
 */
class DeviceLocationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    protected function setUp(): void
    {
        parent::setUp();
        IpLocation::flush();
    }

    // ── the resolver ─────────────────────────────────────────────────

    /**
     * Asserted against `isPublic` rather than `describe`, and that is the point.
     *
     * The first version of this test asserted `describe('127.0.0.1')` was null
     * and **passed with the private-address check deleted** — because on a
     * machine with no geolocation database every path returns null, so the test
     * could not tell which one had answered. Mutation testing is what caught
     * it. The question is now asked of the guard directly, where removing it
     * changes the answer.
     */
    public function test_a_private_or_reserved_address_is_not_locatable(): void
    {
        // Every request in development, and every request behind a proxy that
        // forwards badly. Some databases answer these with a country anyway.
        $this->assertFalse(IpLocation::isPublic('127.0.0.1'));
        $this->assertFalse(IpLocation::isPublic('192.168.1.10'));
        $this->assertFalse(IpLocation::isPublic('10.0.2.2'));
        $this->assertFalse(IpLocation::isPublic('::1'));
        $this->assertFalse(IpLocation::isPublic('172.16.5.4'));

        // And the addresses that genuinely could have one.
        $this->assertTrue(IpLocation::isPublic('41.210.1.1'));
        $this->assertTrue(IpLocation::isPublic('8.8.8.8'));
    }

    public function test_a_private_address_still_resolves_to_nothing(): void
    {
        $this->assertNull(IpLocation::describe('127.0.0.1'));
        $this->assertNull(IpLocation::describe('192.168.1.10'));
    }

    public function test_nonsense_has_no_location(): void
    {
        $this->assertNull(IpLocation::describe('not-an-address'));
        $this->assertNull(IpLocation::describe(''));
        $this->assertNull(IpLocation::describe(null));
    }

    /**
     * With no database configured, a real public address still resolves to
     * nothing — and this is the state the repository ships in.
     */
    public function test_a_public_address_has_no_location_without_a_database(): void
    {
        config(['services.geoip.database' => null]);
        IpLocation::flush();

        $this->assertNull(IpLocation::describe('8.8.8.8'));
    }

    /** A configured path that is not a file must not throw. */
    public function test_a_missing_database_file_is_not_an_error(): void
    {
        config(['services.geoip.database' => '/no/such/file.mmdb']);
        IpLocation::flush();

        $this->assertNull(IpLocation::describe('8.8.8.8'));
    }

    // ── what the endpoint returns ────────────────────────────────────

    public function test_a_device_row_carries_a_location_key_even_when_it_is_null(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $response = $this->withFreshToken($token)->getJson('/api/v1/devices')->assertOk();

        // The key must exist whatever the answer: a client that branches on
        // its presence would render differently on a server with a database
        // and one without, which is the drift this asserts against.
        $this->assertArrayHasKey('location', $response->json('data.devices.0'));
    }

    /** The address is recorded on the app install, so there is something to resolve. */
    public function test_an_app_install_records_the_address_it_spoke_from(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        // The stamp is rate-limited to once a minute by the middleware, so age
        // the row before asking again.
        Device::query()->update(['last_seen_at' => now()->subHour(), 'last_ip' => null]);

        $this->withFreshToken($token)
            ->withServerVariables(['REMOTE_ADDR' => '41.210.1.1'])
            ->getJson('/api/v1/devices')
            ->assertOk();

        $this->assertSame('41.210.1.1', Device::query()->firstOrFail()->last_ip);
    }

    /**
     * Only the latest address is kept.
     *
     * The column is one value rather than a history on purpose: a device that
     * moves overwrites, and nothing accumulates a trail of where somebody has
     * been.
     */
    public function test_a_moved_device_overwrites_rather_than_accumulates(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        Device::query()->update(['last_seen_at' => now()->subHour()]);
        $this->withFreshToken($token)
            ->withServerVariables(['REMOTE_ADDR' => '41.210.1.1'])
            ->getJson('/api/v1/devices')->assertOk();

        Device::query()->update(['last_seen_at' => now()->subHour()]);
        $this->withFreshToken($token)
            ->withServerVariables(['REMOTE_ADDR' => '41.210.2.2'])
            ->getJson('/api/v1/devices')->assertOk();

        $this->assertSame('41.210.2.2', Device::query()->firstOrFail()->last_ip);
    }

    /**
     * The device list arrives as a JSON array, not an object.
     *
     * **Asserting the shape, not only the contents.** A PHP array with
     * non-sequential keys serialises as an object — `[0 => 'a', 2 => 'b']`
     * becomes `{"0":"a","2":"b"}` — and a client generated against a list then
     * receives an object and renders nothing, while the endpoint stays green.
     * `->values()` in the registry is what prevents it, and without this
     * assertion nothing was asking what that call was for.
     *
     * Added 2026-09-09 after a parallel session found exactly this hole in the
     * plans endpoint: two tests on the field, and neither could tell
     * `array_values($x)` from `$x ?? []`.
     */
    public function test_the_device_list_is_a_json_array_not_an_object(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        /*
         * The browser session must be OLDER than the app install, and that is
         * the whole setup rather than a detail.
         *
         * `all()` concatenates browser sessions and then app installs, so with
         * the browser newest the sort is a no-op and the keys stay sequential
         * whether or not anything reindexes — the first version of this test
         * did exactly that and passed with `->values()` deleted. Ageing the
         * session forces the sort to actually permute, which is the only state
         * in which a missing reindex is visible.
         */
        DB::table('sessions')->insert([
            'id' => 'sess-shape-1',
            'user_id' => $user->id,
            'ip_address' => '41.210.9.9',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120',
            'payload' => '',
            'last_activity' => now()->subMinutes(30)->timestamp,
        ]);

        $devices = $this->withFreshToken($token)->getJson('/api/v1/devices')
            ->assertOk()
            ->json('data.devices');

        $this->assertGreaterThan(1, count($devices), 'Needs more than one row to prove the keys.');
        $this->assertSame(
            'app',
            $devices[0]['kind'] ?? null,
            'The setup is wrong if the sort did not reorder: the newer app install must come first.',
        );
        $this->assertTrue(
            array_is_list($devices),
            'The device list must serialise as a JSON array. Non-sequential keys make it an object.',
        );
    }

    // ── the meter ────────────────────────────────────────────────────

    /**
     * The numbers are a concurrency limit, not a device limit.
     *
     * `watching` comes from the same method `TierGate` calls before letting a
     * stream start, so the bar cannot tell a viewer they have room while the
     * player is about to refuse them.
     */
    public function test_the_list_reports_how_many_can_watch_at_once(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $response = $this->withFreshToken($token)->getJson('/api/v1/devices')->assertOk();

        $this->assertArrayHasKey('streams', $response->json('data'));
        $this->assertArrayHasKey('watching', $response->json('data.streams'));
        $this->assertArrayHasKey('limit', $response->json('data.streams'));
        $this->assertIsInt($response->json('data.streams.watching'));
    }

    /**
     * No subscription means no cap to report, and null is not zero.
     *
     * Zero would draw a full bar and read as "you may watch on nothing".
     */
    public function test_no_subscription_reports_a_null_limit_rather_than_zero(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonPath('data.streams.limit', null);
    }

    /** A browser session counts, which is what the cap actually enforces. */
    public function test_a_browser_session_counts_towards_watching(): void
    {
        $user = $this->viewer();
        $token = $this->signIn($user);

        $before = $this->withFreshToken($token)->getJson('/api/v1/devices')
            ->assertOk()->json('data.streams.watching');

        DB::table('sessions')->insert([
            'id' => 'sess-devices-loc-1',
            'user_id' => $user->id,
            'ip_address' => '41.210.9.9',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $after = $this->withFreshToken($token)->getJson('/api/v1/devices')
            ->assertOk()->json('data.streams.watching');

        $this->assertSame($before + 1, $after);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-loc01'): string
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
