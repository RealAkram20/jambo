<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Long-lived guest visitor ID for view-count dedupe. Kept
        // plaintext so it survives APP_KEY rotation — the value is
        // a random UUID with no sensitive content.
        \Modules\Streaming\app\Http\Middleware\EnsureVisitorId::COOKIE_NAME,
        // Referral attribution cookie — carries a public referral code,
        // plaintext for the same APP_KEY-rotation reason as above.
        \Modules\Referrals\app\Http\Middleware\CaptureReferralCode::COOKIE_NAME,
        // Files Gallery access token. Must stay plaintext so fm-guard.php
        // (raw PHP, outside the Laravel middleware stack) can read and
        // HMAC-verify it. The value is self-authenticating, not secret.
        \Modules\FileManager\app\Http\Controllers\FileManagerController::COOKIE_NAME,
    ];
}
