<?php

namespace Tests\Feature\Api\V1;

use App\Http\Api\ApiErrorCode;
use Modules\Streaming\app\Playback\PlaybackDenial;
use Tests\TestCase;

/**
 * The API's error codes and the entitlement service's denial reasons are two
 * enums that must say the same words.
 *
 * They are separate on purpose — PlaybackDenial is a domain concept the web
 * middleware uses and knows nothing about HTTP — but a code the app branches
 * on that exists in only one of them is a client bug nobody sees until a
 * viewer is stuck. Three copies of the entitlement rules is what this whole
 * phase started by deleting; this is the cheap guard that stops the same
 * thing happening to the vocabulary.
 */
class ApiErrorCodeTest extends TestCase
{
    public function test_every_playback_denial_has_a_matching_api_error_code(): void
    {
        foreach (PlaybackDenial::cases() as $denial) {
            $this->assertNotNull(
                ApiErrorCode::tryFrom($denial->value),
                "PlaybackDenial::{$denial->name} ('{$denial->value}') has no ApiErrorCode case. "
                . 'Add it to ApiErrorCode, or the app will receive a code it cannot branch on.'
            );
        }
    }

    public function test_every_code_maps_to_a_sane_http_status(): void
    {
        foreach (ApiErrorCode::cases() as $code) {
            $status = $code->status();

            $this->assertGreaterThanOrEqual(400, $status, "{$code->name} is an error, so it cannot be a 2xx or 3xx.");
            $this->assertLessThan(600, $status, "{$code->name} has a status outside the HTTP range.");
        }
    }

    /**
     * Codes are the API's contract. Renaming or removing a value breaks every
     * shipped APK that branches on it — and an APK cannot be hot-fixed the way
     * a web page can. This pins the ones the app is being written against, so
     * a rename shows up as a failing test rather than as a stuck viewer.
     */
    public function test_the_published_codes_have_not_changed_value(): void
    {
        $published = [
            'UNAUTHENTICATED', 'INVALID_CREDENTIALS', 'ACCOUNT_DEACTIVATED',
            'TWO_FACTOR_REQUIRED', 'TWO_FACTOR_INVALID', 'TWO_FACTOR_CHALLENGE_EXPIRED',
            'DEVICE_REVOKED', 'DEVICE_NOT_FOUND',
            'LOGIN_REQUIRED', 'SUBSCRIPTION_REQUIRED', 'UPGRADE_REQUIRED',
            'STREAM_LIMIT', 'CONTENT_UNAVAILABLE',
            'VALIDATION_FAILED', 'NOT_FOUND', 'FORBIDDEN', 'RATE_LIMITED',
            'APP_UPDATE_REQUIRED', 'SERVER_ERROR',
        ];

        foreach ($published as $value) {
            $this->assertNotNull(
                ApiErrorCode::tryFrom($value),
                "The published error code '{$value}' no longer exists. Removing or renaming a "
                . 'code is a breaking API change and needs a new API version, not an edit.'
            );
        }
    }
}
