<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * What the app needs to know before it knows who is using it.
 *
 * Called unauthenticated on launch. It exists so that a shipped APK can be
 * told things after it shipped: that it is too old to talk to this server,
 * that a feature is off, or what the server thinks the time is.
 *
 * The clock matters more than it looks. Offline licences are enforced against
 * the device's own clock (ADR-0003), and a viewer who winds it back must not
 * gain a month of downloads. The app anchors on the last server timestamp it
 * saw and refuses offline playback if the wall clock has moved backwards from
 * it, so this endpoint is where that anchor is refreshed.
 */
class AppConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return ApiResponse::ok([
            // Below this, the app must show an update wall rather than trying
            // to talk to endpoints it may not understand. A setting rather
            // than a constant, so raising it does not need a deploy.
            'min_app_version' => (string) setting('app.min_version', '1.0.0'),
            'server_time' => now()->toIso8601String(),
            'features' => [
                // Downloads are Phase 3. The flag exists now so the app can
                // ship its Downloads screen dark and have it lit server-side,
                // rather than needing a Play review to turn it on.
                'downloads' => (bool) setting('app.downloads_enabled', false),
                // ADR-0004: the Play build may not mention payment. The build
                // itself decides that, but the server can force it off.
                'in_app_subscribe' => (bool) setting('app.in_app_subscribe', false),
                // Whether this server can complete `POST /auth/google` at all.
                // The sign-in screen has to know this *before* anyone signs in,
                // which is why it lives here and not in /account/security: a
                // Google button on a server with no client id is a button that
                // can only fail, and the viewer has no way to know why.
                'google_sign_in' => (bool) config('services.google.client_id'),
            ],
            'support' => [
                'site_url' => config('app.url'),
            ],
            // The admin's own logo and preloader, so branding can change
            // without shipping an APK. See brandingUrls() for why the URLs
            // carry a version.
            'branding' => $this->brandingUrls(),
        ]);
    }

    /**
     * Absolute, versioned URLs for the branding the app renders.
     *
     * Two properties the app depends on, and both are the point of this
     * method rather than incidental:
     *
     * **Absolute.** The settings are stored however the admin's file manager
     * wrote them — often root-relative, and on a subdirectory install carrying
     * the install's own path prefix. A handset has no idea what host that is
     * relative to, so it is resolved here rather than guessed there.
     *
     * **Versioned.** The app caches these images on disk and reuses them
     * offline, which is the whole point of downloading them. A cache keyed on
     * a URL that never changes would pin the first logo it ever saw for the
     * life of the install. Appending a fingerprint of the current file means a
     * replaced logo is simply a different URL, so it is fetched once, cached,
     * and served from disk from then on — no polling, no cache-busting
     * protocol, and it degrades to "keep what you have" when offline.
     *
     * The launcher icon and the splash are deliberately absent: Android reads
     * both out of the APK before any JavaScript runs, so no endpoint can
     * change them.
     */
    private function brandingUrls(): array
    {
        return [
            'logo_url' => $this->versionedBranding(branded_logo()),
            'preloader_url' => $this->versionedBranding(
                branding_asset('preloader', 'frontend/images/loader.gif')
            ),
        ];
    }

    private function versionedBranding(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        // A branding asset on another host is passed through as-is. It cannot
        // be fingerprinted because its mtime is not ours to read, which is the
        // honest answer rather than a guess.
        if (str_starts_with($url, 'http') && ! str_starts_with($url, (string) config('app.url'))) {
            return $url;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
        $relative = $this->locateUnderPublic($path);

        if ($relative === null) {
            return str_starts_with($url, 'http') ? $url : url($url);
        }

        // Rebuilt from the file we actually found rather than from the stored
        // setting. The two differ more often than one would like: the file
        // manager writes the URL as the *browser* saw it, so on a subdirectory
        // install the setting carries that install's path prefix — which is
        // wrong for any other way of serving the same code, and wrong for a
        // handset. Resolving to the file and re-deriving the URL makes this
        // correct on every host, which is what a shipped APK needs.
        return url($relative) . '?v=' . filemtime(public_path($relative));
    }

    /**
     * Find a stored asset path as a real file under public/, ignoring however
     * many leading path segments the stored URL happens to carry.
     */
    private function locateUnderPublic(string $path): ?string
    {
        $segments = array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));

        // Drop one leading segment at a time: "/Jambo/storage/gallery/x.png"
        // is tried, then "storage/gallery/x.png", which is the one that exists.
        for ($i = 0; $i < count($segments); $i++) {
            $candidate = implode('/', array_slice($segments, $i));

            if (str_contains($candidate, '..')) {
                return null;
            }

            if (is_file(public_path($candidate))) {
                return $candidate;
            }
        }

        return null;
    }
}
