<?php

namespace Modules\Streaming\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Issues a long-lived `jambo_visitor` cookie to anyone without one.
 *
 * Used by the guest-view counter so we can dedupe view increments to
 * one-per-device without requiring a login. The cookie is plaintext
 * (added to EncryptCookies::$except) because:
 *
 *   1. The visitor_id is a random UUID, not sensitive.
 *   2. Encrypted cookies break across APP_KEY rotation, but a
 *      visitor cookie is meant to live for years — survives any
 *      future key rotation that way.
 *
 * Cookie lifetime is ~2 years. Browsers may garbage-collect long-
 * unused cookies sooner; that's fine — the worst case is that an
 * occasional viewer counts as a fresh device.
 */
class EnsureVisitorId
{
    public const COOKIE_NAME = 'jambo_visitor';
    public const TTL_MINUTES = 525_600 * 2;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->cookies->has(self::COOKIE_NAME)) {
            $id = (string) Str::uuid();

            // Make the new id available to anything reading $request->cookie()
            // later in this same request (controllers, downstream middleware).
            $request->cookies->set(self::COOKIE_NAME, $id);

            // Queue the Set-Cookie header on the outgoing response.
            Cookie::queue(
                self::COOKIE_NAME,
                $id,
                self::TTL_MINUTES,
                '/',
                null,
                $request->isSecure(),
                false,    // httpOnly: false (no JS access needed but no harm)
                false,
                'lax',
            );
        }

        return $next($request);
    }
}
