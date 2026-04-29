<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the standard hardening headers on every response. These are the
 * defence-in-depth headers OWASP recommends:
 *
 *   - X-Frame-Options: SAMEORIGIN — clickjacking guard. Frontend never
 *     embeds itself in a foreign iframe.
 *   - X-Content-Type-Options: nosniff — stops the browser from
 *     re-interpreting (e.g.) an uploaded poster as a script.
 *   - Referrer-Policy: strict-origin-when-cross-origin — modern default;
 *     don't leak full URLs to other origins.
 *   - Permissions-Policy — turn off browser features we don't use.
 *   - Strict-Transport-Security — only over HTTPS, with a sensible TTL.
 *
 * No Content-Security-Policy is set here. CSP requires per-page tuning
 * against the video player + Vite assets and is best layered in a
 * second pass once the baseline is in place.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip when the response already set its own value (e.g. an
        // /admin/file-manager view that needs to embed Files Gallery in
        // an iframe — we leave its X-Frame-Options alone).
        if (!$response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }
        if (!$response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }
        if (!$response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        if (!$response->headers->has('Permissions-Policy')) {
            // Disable browser features Jambo doesn't use. Each entry is
            // an empty allowlist; `()` denies the feature for everyone.
            $response->headers->set(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
            );
        }
        // HSTS only over HTTPS. Setting it on a plain-HTTP response is
        // ignored by browsers but spammy in logs.
        if ($request->isSecure() && !$response->headers->has('Strict-Transport-Security')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=15552000; includeSubDomains'
            );
        }

        return $response;
    }
}
