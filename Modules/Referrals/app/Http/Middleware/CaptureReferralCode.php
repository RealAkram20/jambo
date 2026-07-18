<?php

namespace Modules\Referrals\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Modules\Referrals\app\Services\ReferralSettings;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captures `?ref=<code>` on any web request into the `jambo_ref` cookie
 * so the referral survives until signup/checkout (window length is a
 * super-admin setting). Last-touch: a newer link always overwrites.
 *
 * The cookie is plaintext (EncryptCookies::$except) — the value is a
 * public referral code, and keeping it unencrypted means it survives
 * APP_KEY rotation like the visitor cookie does. The code is NOT
 * resolved against the DB here; ownership is checked where the cookie
 * is consumed (registration / checkout).
 */
class CaptureReferralCode
{
    public const COOKIE_NAME = 'jambo_ref';

    public function handle(Request $request, Closure $next): Response
    {
        $code = trim((string) $request->query('ref', ''));

        if ($code !== ''
            && strlen($code) <= 50
            && preg_match('/^[a-zA-Z0-9_.\-]+$/', $code) === 1
            && ReferralSettings::active()
        ) {
            // Visible to anything reading $request->cookie() later in
            // this same request.
            $request->cookies->set(self::COOKIE_NAME, $code);

            Cookie::queue(
                self::COOKIE_NAME,
                $code,
                ReferralSettings::cookieDays() * 1440,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            );
        }

        return $next($request);
    }
}
