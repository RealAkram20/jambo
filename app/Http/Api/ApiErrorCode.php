<?php

namespace App\Http\Api;

/**
 * Every machine-readable failure code /api/v1 can return.
 *
 * One enum, because the app branches on `code` and never on `message`: message
 * text is translated, reworded and A/B'd, and a client that reads it breaks
 * the first time someone fixes a typo. Adding a case is additive and safe;
 * changing or removing one is a breaking API change and needs a new version.
 *
 * The playback codes are mirrored from PlaybackDenial, which is the domain
 * enum the entitlement service answers with. They are not re-derived here, and
 * ApiErrorCodeTest asserts the two cannot drift.
 */
enum ApiErrorCode: string
{
    // ── auth ─────────────────────────────────────────────────────────
    case Unauthenticated = 'UNAUTHENTICATED';
    case InvalidCredentials = 'INVALID_CREDENTIALS';
    case AccountDeactivated = 'ACCOUNT_DEACTIVATED';
    case TwoFactorRequired = 'TWO_FACTOR_REQUIRED';
    case TwoFactorInvalid = 'TWO_FACTOR_INVALID';
    case TwoFactorChallengeExpired = 'TWO_FACTOR_CHALLENGE_EXPIRED';

    // ── devices ──────────────────────────────────────────────────────
    case DeviceRevoked = 'DEVICE_REVOKED';
    case DeviceNotFound = 'DEVICE_NOT_FOUND';

    // ── playback (mirrors PlaybackDenial) ────────────────────────────
    case LoginRequired = 'LOGIN_REQUIRED';
    case SubscriptionRequired = 'SUBSCRIPTION_REQUIRED';
    case UpgradeRequired = 'UPGRADE_REQUIRED';
    case StreamLimit = 'STREAM_LIMIT';
    case ContentUnavailable = 'CONTENT_UNAVAILABLE';

    // ── general ──────────────────────────────────────────────────────
    case ValidationFailed = 'VALIDATION_FAILED';
    case NotFound = 'NOT_FOUND';
    case Forbidden = 'FORBIDDEN';
    case RateLimited = 'RATE_LIMITED';
    case AppUpdateRequired = 'APP_UPDATE_REQUIRED';
    case ServerError = 'SERVER_ERROR';

    /** The HTTP status this code should be returned with. */
    public function status(): int
    {
        return match ($this) {
            self::Unauthenticated,
            self::InvalidCredentials,
            self::LoginRequired,
            self::TwoFactorChallengeExpired => 401,

            self::AccountDeactivated,
            self::DeviceRevoked,
            self::SubscriptionRequired,
            self::UpgradeRequired,
            self::Forbidden => 403,

            self::DeviceNotFound,
            self::NotFound,
            self::ContentUnavailable => 404,

            // The viewer is entitled; the account is simply busy elsewhere.
            // 409 rather than 403 so the app can tell "you cannot" from
            // "not right now" without reading the code twice.
            self::StreamLimit => 409,

            self::TwoFactorRequired,
            self::TwoFactorInvalid,
            self::ValidationFailed => 422,

            self::RateLimited => 429,
            self::AppUpdateRequired => 426,
            self::ServerError => 500,
        };
    }
}
