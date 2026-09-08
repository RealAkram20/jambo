<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Behaviour pin for Google sign-in, written BEFORE SocialAuthController's
 * account resolution was extracted into SocialAccountResolver, and required to
 * pass unchanged after it.
 *
 * There were no tests on this path at all, which matters more here than
 * anywhere else in the API work: the callback carries an account-takeover
 * mitigation. Someone can register victim@gmail.com locally with a password
 * they chose and never verify it; the row just sits there. When the real owner
 * later signs in with Google — which proves the mailbox is theirs — the
 * squatter's password must stop working. That is four lines in a controller
 * and it is the single most valuable thing in this file.
 *
 * The site is live and this is auth code, so nothing here may change.
 */
class SocialAuthPinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-client-id']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** Pretend Google handed us this profile. */
    private function googleReturns(string $email, string $name = 'Ada Lovelace'): void
    {
        $social = Mockery::mock(SocialiteUser::class);
        $social->shouldReceive('getEmail')->andReturn($email);
        $social->shouldReceive('getName')->andReturn($name);
        $social->shouldReceive('getId')->andReturn('google-123');
        $social->shouldReceive('getNickname')->andReturn(null);
        $social->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver->user')->andReturn($social);
    }

    public function test_a_new_google_account_is_created_verified_with_a_usable_username(): void
    {
        $this->googleReturns('ada@example.test');

        $this->get('/auth/google/callback')->assertRedirect();

        $user = User::where('email', 'ada@example.test')->first();

        $this->assertNotNull($user, 'Google sign-in must create the account.');
        $this->assertNotNull($user->email_verified_at, 'Google proved the mailbox, so the account starts verified.');
        $this->assertSame('Ada', $user->first_name);
        $this->assertSame('Lovelace', $user->last_name);
        $this->assertNotEmpty($user->username);
    }

    /**
     * The mitigation. A squatter registers the victim's address locally and
     * never verifies it; when the real owner arrives through Google, the
     * squatter's password must be dead.
     */
    public function test_signing_in_with_google_severs_a_squatters_unverified_password(): void
    {
        $squatted = User::factory()->create([
            'email' => 'victim@example.test',
            'email_verified_at' => null,
            'password' => Hash::make('squatter-chosen-password'),
        ]);

        $this->googleReturns('victim@example.test');

        $this->get('/auth/google/callback')->assertRedirect();

        $squatted->refresh();

        $this->assertNotNull($squatted->email_verified_at, 'Google sign-in promotes the row to verified.');
        $this->assertFalse(
            Hash::check('squatter-chosen-password', $squatted->password),
            'The password chosen by whoever squatted this address must no longer work.'
        );
    }

    /**
     * A verified local account is a real account. Google sign-in adopts it as
     * it is and must NOT rewrite the owner's password.
     */
    public function test_a_verified_local_account_keeps_its_password(): void
    {
        $owner = User::factory()->create([
            'email' => 'owner@example.test',
            'email_verified_at' => now(),
            'password' => Hash::make('my-own-password'),
        ]);

        $this->googleReturns('owner@example.test');

        $this->get('/auth/google/callback')->assertRedirect();

        $owner->refresh();

        $this->assertTrue(
            Hash::check('my-own-password', $owner->password),
            'An account that already proved its email keeps the password its owner set.'
        );
    }

    public function test_a_deactivated_account_cannot_sign_in_with_google(): void
    {
        User::factory()->create([
            'email' => 'gone@example.test',
            'email_verified_at' => now(),
            'deactivated_at' => now(),
        ]);

        $this->googleReturns('gone@example.test');

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * The generated username becomes the account's referral code and sits at
     * /{username}, so "admin" from admin@gmail.com would be both a route
     * collision and an impersonation handle.
     */
    public function test_a_reserved_username_is_never_minted_from_an_email(): void
    {
        $this->googleReturns('admin@example.test', 'Admin Person');

        $this->get('/auth/google/callback')->assertRedirect();

        $user = User::where('email', 'admin@example.test')->first();

        $this->assertNotSame('admin', strtolower($user->username));
    }

    public function test_a_username_collision_gets_a_suffix_rather_than_failing(): void
    {
        User::factory()->create(['username' => 'ada']);

        $this->googleReturns('ada@example.test');

        $this->get('/auth/google/callback')->assertRedirect();

        $user = User::where('email', 'ada@example.test')->first();

        $this->assertNotNull($user);
        $this->assertNotSame('ada', $user->username);
    }
}
