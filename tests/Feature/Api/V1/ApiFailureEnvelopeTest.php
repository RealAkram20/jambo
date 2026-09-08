<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * What the API answers when something goes wrong that nobody planned for.
 *
 * Both cases here were found by walking every endpoint against the real dev
 * database rather than the test suite — the suite fakes mail, and the dev
 * environment's SMTP host does not exist. That combination produced a bare
 * Laravel 500 with the exception class in it, and it produced it for real
 * accounts only, which is an enumeration oracle wearing a stack trace.
 */
class ApiFailureEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Any unhandled exception on a token API route must still answer in the
     * envelope. An app cannot read a Laravel error page, and in debug mode
     * that page carries the exception class and message.
     */
    public function test_an_unhandled_exception_answers_in_the_envelope_as_server_error(): void
    {
        Route::middleware('api')->prefix('api/v1')->get('boom-for-testing', function () {
            throw new \RuntimeException('simulated failure');
        });

        config(['app.debug' => false]);

        $this->getJson('/api/v1/boom-for-testing')
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'SERVER_ERROR')
            ->assertJsonMissingPath('exception');
    }

    /** In production the message must not carry the exception. */
    public function test_a_server_error_hides_the_exception_when_not_debugging(): void
    {
        Route::middleware('api')->prefix('api/v1')->get('boom-for-testing', function () {
            throw new \RuntimeException('a message that names an internal table');
        });

        config(['app.debug' => false]);

        $body = $this->getJson('/api/v1/boom-for-testing')->assertStatus(500)->content();

        $this->assertStringNotContainsString('names an internal table', $body);
        $this->assertStringNotContainsString('RuntimeException', $body);
    }

    /** A deliberate abort() keeps its own status but still gets the envelope. */
    public function test_an_http_abort_keeps_its_status_inside_the_envelope(): void
    {
        Route::middleware('api')->prefix('api/v1')->get('forbidden-for-testing', function () {
            abort(403, 'Not for you.');
        });

        $this->getJson('/api/v1/forbidden-for-testing')
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN')
            ->assertJsonPath('message', 'Not for you.');
    }

    /**
     * The one that matters. For an address with no account the broker sends
     * nothing; for a real one it tries. If mail is down, only the real one
     * throws — so a raw 500 would say "this email exists" to anyone probing.
     * The endpoint must answer the same neutral 200 either way.
     */
    public function test_forgot_password_stays_neutral_when_mail_transport_is_down(): void
    {
        User::factory()->create(['email' => 'real@example.test']);

        // Point mail at a host that does not exist, exactly as the dev box
        // does, and do NOT fake notifications — that would hide the throw.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'no-such-host.invalid',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 1,
        ]);

        $real = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'real@example.test']);
        $fake = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.test']);

        $real->assertOk();
        $fake->assertOk();
        $this->assertSame($real->json('message'), $fake->json('message'));
    }

    /** And when mail works, the real account still gets its link. */
    public function test_forgot_password_still_sends_when_mail_works(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'works@example.test']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'works@example.test'])->assertOk();

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
    }
}
