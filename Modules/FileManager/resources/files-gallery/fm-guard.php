<?php

/**
 * Files Gallery admin gate.
 *
 * The Files Gallery drop-in (index.php, which requires this file at its
 * very top) is served straight by Apache from /storage/media/index.php, so
 * Laravel's `auth` + `role:admin` route middleware never runs for it. This
 * shim is the authoritative gate.
 *
 * It validates the signed JAMBO_FM_SESSION token that
 * FileManagerController::issueToken() hands only to authenticated admins.
 * The token is "<userId>.<expiry>.<hmac>", where hmac = HMAC-SHA256 over
 * "<userId>.<expiry>" keyed with the Laravel app key. Because the guard
 * recomputes that HMAC, a fabricated cookie value can't pass — the previous
 * gate only checked that *a* cookie was present, which any client can forge.
 *
 * Runs in pure PHP with no dependence on mod_rewrite, so a host missing that
 * module (where the old .htaccess rule silently no-op'd) is still protected.
 *
 * Fails closed: any missing key, malformed token, bad signature, or expiry
 * in the past ends the request with 403 before a single line of gallery
 * code executes.
 */

(static function (): void {
    $deny = static function (): void {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Forbidden — open the file manager from the admin panel.');
    };

    // storage/app/public/media -> project root (four levels up).
    $root = dirname(__DIR__, 4);

    $appKey = fm_guard_app_key($root);
    if ($appKey === null || $appKey === '') {
        // No key to verify against — never fail open.
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('File manager unavailable.');
    }

    $token = $_COOKIE['JAMBO_FM_SESSION'] ?? '';
    if (! is_string($token) || substr_count($token, '.') !== 2) {
        $deny();
    }

    [$userId, $expiry, $sig] = explode('.', $token, 3);

    if (! ctype_digit($userId) || ! ctype_digit($expiry)) {
        $deny();
    }

    if ((int) $expiry < time()) {
        $deny(); // token past its 4-hour window
    }

    $expected = hash_hmac('sha256', $userId . '.' . $expiry, $appKey);

    if (! hash_equals($expected, (string) $sig)) {
        $deny(); // signature forged or app key rotated
    }
})();

/**
 * Read APP_KEY without booting the whole framework — the gallery fires many
 * requests (dir listings, thumbnails), and a full bootstrap per hit would
 * make it crawl. Parse .env directly; only if it's absent (e.g. a
 * config-cached production box with no .env) fall back to a real boot.
 */
function fm_guard_app_key(string $root): ?string
{
    $envFile = $root . '/.env';
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strncmp($line, 'APP_KEY=', 8) === 0) {
                return trim(substr($line, 8), " \t\"'");
            }
        }
    }

    if (is_file($root . '/vendor/autoload.php') && is_file($root . '/bootstrap/app.php')) {
        try {
            require_once $root . '/vendor/autoload.php';
            $app = require $root . '/bootstrap/app.php';
            $app->make(\Illuminate\Contracts\Http\Kernel::class)->bootstrap();

            return (string) config('app.key');
        } catch (\Throwable $e) {
            return null;
        }
    }

    return null;
}
