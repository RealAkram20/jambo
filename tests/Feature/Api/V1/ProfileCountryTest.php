<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Support\Countries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Streaming\app\Models\Device;
use Tests\TestCase;

/**
 * The country on a viewer's profile.
 *
 * Added 2026-09-09 with the profile screen. The mockup showed "Country:
 * Uganda" against a field that existed nowhere in this system, and the whole
 * point of this slice is that the row is now real rather than fabricated — so
 * the cases that matter are the ones that stop it becoming a fabrication
 * again: an account with no country must read as null all the way to the
 * screen, and a code the list does not contain must be refused rather than
 * stored and later shown back as itself.
 */
class ProfileCountryTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    /**
     * The load-bearing one. Every existing account has no country, and the
     * screen renders an em dash for exactly this shape. A server that
     * defaulted it to "UG" because Jambo is Ugandan would put a fact on
     * somebody's profile that they never told it.
     */
    public function test_an_account_with_no_country_reads_null_for_both_fields(): void
    {
        $user = $this->viewer();
        $this->assertNull($user->country);

        $this->withFreshToken($this->signIn($user))
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.profile.country', null)
            ->assertJsonPath('data.profile.country_name', null);
    }

    public function test_setting_a_country_returns_the_code_and_its_name(): void
    {
        $user = $this->viewer();

        $this->withFreshToken($this->signIn($user))
            ->patchJson('/api/v1/profile', $this->form($user, ['country' => 'UG']))
            ->assertOk()
            ->assertJsonPath('data.profile.country', 'UG')
            ->assertJsonPath('data.profile.country_name', 'Uganda');
    }

    /** A client sending "ug" means Uganda. Refusing it would help nobody, and
     *  storing it as-is would give one country two rows. */
    public function test_a_lower_case_code_is_accepted_and_stored_upper_case(): void
    {
        $user = $this->viewer();

        $this->withFreshToken($this->signIn($user))
            ->patchJson('/api/v1/profile', $this->form($user, ['country' => 'ke']))
            ->assertOk()
            ->assertJsonPath('data.profile.country', 'KE')
            ->assertJsonPath('data.profile.country_name', 'Kenya');

        $this->assertSame('KE', $user->refresh()->country);
    }

    /**
     * The guard against the row becoming a fabrication again.
     *
     * "ZZ" is two letters and passes a naive `size:2`, but it is not an
     * assigned country. Stored, it would come back out of `Countries::name`
     * as either "Unknown Region" or "ZZ" and be printed on somebody's profile
     * as though it were where they live.
     */
    public function test_a_code_that_is_not_a_country_is_refused(): void
    {
        $user = $this->viewer();

        $this->withFreshToken($this->signIn($user))
            ->patchJson('/api/v1/profile', $this->form($user, ['country' => 'ZZ']))
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertNull($user->refresh()->country);
    }

    public function test_a_country_can_be_cleared(): void
    {
        $user = $this->viewer();
        $user->forceFill(['country' => 'UG'])->save();

        $this->withFreshToken($this->signIn($user))
            ->patchJson('/api/v1/profile', $this->form($user, ['country' => null]))
            ->assertOk()
            ->assertJsonPath('data.profile.country', null)
            ->assertJsonPath('data.profile.country_name', null);
    }

    // ── the picker's list ────────────────────────────────────────────

    /** Public on purpose: the registration form needs it before there is a
     *  token, and it is the same list for everybody. */
    public function test_the_country_list_is_readable_without_signing_in(): void
    {
        $this->getJson('/api/v1/countries')
            ->assertOk()
            ->assertJsonPath('data.countries.0.code', 'UG')
            ->assertJsonPath('data.countries.0.name', 'Uganda');
    }

    /**
     * Every code the picker offers must be one the profile endpoint accepts.
     *
     * This is the test that catches the two lists drifting apart, which is the
     * failure that would let a viewer choose a country and then be told it is
     * invalid — with no way to tell which of the two was wrong.
     */
    public function test_every_offered_country_is_one_the_profile_will_accept(): void
    {
        $offered = collect($this->getJson('/api/v1/countries')->json('data.countries'))
            ->pluck('code')
            ->all();

        $this->assertNotEmpty($offered);

        foreach ($offered as $code) {
            $this->assertTrue(
                Countries::isValid($code),
                "The picker offers {$code} but the validator rejects it.",
            );
        }
    }

    /** Names, not codes, decide the order below the suggested block —
     *  somebody scanning is looking for Kenya, not for KE. */
    public function test_the_suggested_countries_come_first_and_the_rest_are_sorted_by_name(): void
    {
        $data = $this->getJson('/api/v1/countries')->assertOk()->json('data');

        $codes = collect($data['countries'])->pluck('code')->all();
        $this->assertSame($data['suggested'], array_slice($codes, 0, count($data['suggested'])));

        $rest = array_slice($data['countries'], count($data['suggested']));
        $names = array_column($rest, 'name');
        $sorted = $names;
        usort($sorted, 'strcoll');

        $this->assertSame($sorted, $names);
    }

    /** No country is listed twice, and none of the suggested ones reappear
     *  below the divider. */
    public function test_no_country_appears_twice(): void
    {
        $codes = collect($this->getJson('/api/v1/countries')->json('data.countries'))
            ->pluck('code')
            ->all();

        $this->assertSame(count($codes), count(array_unique($codes)));
    }

    // ── helpers ──────────────────────────────────────────────────────

    /**
     * `PATCH /profile` is not a partial update — it requires the four identity
     * fields together — so every case sends them and overrides one.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(User $user, array $overrides = []): array
    {
        return array_merge([
            'first_name' => $user->first_name ?? 'Grace',
            'last_name' => $user->last_name ?? 'Hopper',
            'username' => $user->username,
            'email' => $user->email,
        ], $overrides);
    }

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-country1'): string
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
