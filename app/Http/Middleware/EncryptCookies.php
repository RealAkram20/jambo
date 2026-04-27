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
    ];
}
