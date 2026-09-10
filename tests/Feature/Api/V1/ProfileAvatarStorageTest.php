<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Modules\Streaming\app\Models\Device;
use Tests\TestCase;

/**
 * Where a profile photo is stored, and what shape its URL comes back in.
 *
 * Written after Rio uploaded a photo on his phone and got his initial instead.
 * The upload had worked. Two separate things were wrong, and both are pinned
 * here because both were silent — nothing failed, nothing logged, and the
 * screen rendered a perfectly reasonable fallback.
 *
 * **`avatar_url` must be a path.** It was an absolute URL built from
 * `config('app.url')`, which is the website's address and need not be the host
 * a client is talking to. A phone on `10.0.2.2:8095` cannot fetch
 * `http://localhost/Jambo/...`, and it cannot repair it either, because it
 * cannot know which part was host and which was path. Every other image in
 * this API is a path; this is the one that was not.
 *
 * **It must land on the `profiles` disk.** The default media-library disk
 * writes under `storage/app/public`, served through the `public/storage`
 * symlink — a link that is not present on every install of this project. Where
 * it is missing, uploads succeed and are never served.
 *
 * There is deliberately no URL-normalising helper in the controller. The shape
 * is decided once, by the disk's own `url`, and this test is what stops that
 * quietly changing.
 *
 * A note on what is NOT asserted here, because it looks like a gap. There is
 * no test that the returned url literally begins `/storage/profiles/`, and
 * there was one until it failed: `Storage::fake()` replaces the disk's `url`
 * with the fake's, so such a test asserts what Laravel's fake does rather than
 * what this application is configured to do. The two halves are pinned
 * instead — the file lands on the `profiles` disk, and that disk serves from
 * `/storage/profiles` — which is the same guarantee without a test that
 * cannot fail for the right reason.
 */
class ProfileAvatarStorageTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery';

    /**
     * The one that would have caught Rio's bug.
     *
     * A relative path, and specifically NOT something a client has to parse a
     * host out of.
     */
    public function test_the_avatar_url_is_a_path_and_never_names_a_host(): void
    {
        Storage::fake('profiles');

        $user = $this->viewer();
        $token = $this->signIn($user);

        $url = $this->withFreshToken($token)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('face.jpg', 400, 400),
            ])
            ->assertOk()
            ->json('data.profile.avatar_url');

        $this->assertIsString($url);
        $this->assertStringStartsWith('/', $url);
        $this->assertStringNotContainsString('http://', $url);
        $this->assertStringNotContainsString('https://', $url);
    }

    /** The file itself is on the profiles disk, not wherever media-library
     *  would default to. */
    public function test_the_file_is_written_to_the_profiles_disk(): void
    {
        Storage::fake('profiles');

        $user = $this->viewer();

        $this->withFreshToken($this->signIn($user))
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('face.jpg', 400, 400),
            ])
            ->assertOk();

        $media = $user->refresh()->getFirstMedia('profile_image');

        $this->assertNotNull($media);
        $this->assertSame('profiles', $media->disk);
        Storage::disk('profiles')->assertExists($media->id . '/' . $media->file_name);
    }

    /**
     * Most accounts have never uploaded one, and the screens draw the viewer's
     * initial for exactly this shape. An empty string would render as a broken
     * image instead of as no image.
     */
    public function test_an_account_with_no_photo_returns_null_rather_than_an_empty_string(): void
    {
        $user = $this->viewer();

        $this->withFreshToken($this->signIn($user))
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.profile.avatar_url', null);
    }

    /** Single file: a second upload replaces the first rather than piling up. */
    public function test_a_second_upload_replaces_the_first(): void
    {
        Storage::fake('profiles');

        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('one.jpg', 400, 400),
            ])
            ->assertOk();

        $this->withFreshToken($token)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('two.jpg', 400, 400),
            ])
            ->assertOk();

        $this->assertCount(1, $user->refresh()->getMedia('profile_image'));
    }

    /** Removing the photo puts the account back to its initial. */
    public function test_deleting_the_photo_returns_the_url_to_null(): void
    {
        Storage::fake('profiles');

        $user = $this->viewer();
        $token = $this->signIn($user);

        $this->withFreshToken($token)
            ->postJson('/api/v1/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('face.jpg', 400, 400),
            ])
            ->assertOk();

        $this->withFreshToken($token)
            ->deleteJson('/api/v1/profile/avatar')
            ->assertOk()
            ->assertJsonPath('data.profile.avatar_url', null);
    }

    /**
     * The disk is configured to serve from a path, not from `APP_URL`.
     *
     * Asserted on the config rather than through a request, because this is
     * the single place the URL shape is decided and a change here silently
     * changes every avatar in the API.
     */
    public function test_the_profiles_disk_serves_from_a_relative_path(): void
    {
        $url = config('filesystems.disks.profiles.url');

        $this->assertSame('/storage/profiles', $url);
        $this->assertStringStartsWith('/', (string) $url);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function viewer(): User
    {
        return User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
    }

    private function signIn(User $user, string $uuid = 'device-uuid-avatar1'): string
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
