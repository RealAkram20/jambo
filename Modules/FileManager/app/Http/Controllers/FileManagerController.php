<?php

namespace Modules\FileManager\app\Http\Controllers;

use App\Http\Controllers\Controller;

class FileManagerController extends Controller
{
    /**
     * Cookie the Files Gallery drop-in is gated on. Excepted from
     * EncryptCookies so fm-guard.php can read the signed token verbatim —
     * the token authenticates itself via HMAC, it carries no secret.
     */
    public const COOKIE_NAME = 'JAMBO_FM_SESSION';

    /** Access window granted by one page load, in minutes. */
    private const TTL_MINUTES = 4 * 60;

    public function index()
    {
        $mediaDir = storage_path('app/public/media');
        $installFile = $mediaDir . DIRECTORY_SEPARATOR . 'install.php';
        $indexFile = $mediaDir . DIRECTORY_SEPARATOR . 'index.php';

        $state = match (true) {
            file_exists($installFile) => 'install',
            file_exists($indexFile)   => 'ready',
            default                    => 'missing',
        };

        // The Files Gallery drop-in lives at /storage/media/index.php,
        // which Apache serves directly — bypassing Laravel's role:admin
        // gate. fm-guard.php (prepended to that index.php) is the real
        // gate: it validates the signed token issued below. Only an
        // authenticated admin reaching this action gets a valid token,
        // and because the token is an HMAC over "<userId>.<expiry>" keyed
        // with the app key, a fabricated cookie value can't pass — unlike
        // the old presence-only check. Re-issued on every hit so the
        // 4-hour clock resets while the admin is active.
        return response()
            ->view('filemanager::index', compact('state'))
            ->cookie(
                self::COOKIE_NAME,
                $this->issueToken(),
                self::TTL_MINUTES,
                '/',                            // path — iframe loads at /storage/...
                null,                           // domain — default
                config('session.secure', false), // secure on HTTPS
                true,                           // httpOnly
                false,                          // raw
                'Lax',                          // sameSite
            );
    }

    /**
     * Build a self-authenticating access token: "<userId>.<expiry>.<hmac>".
     * fm-guard.php recomputes the HMAC with the same app key and rejects
     * anything that doesn't match or has passed its expiry. Kept to
     * cookie-safe characters (digits, dots, hex) so no encoding is needed.
     */
    private function issueToken(): string
    {
        $expiry = now()->addMinutes(self::TTL_MINUTES)->timestamp;
        $data = auth()->id() . '.' . $expiry;
        $sig = hash_hmac('sha256', $data, (string) config('app.key'));

        return $data . '.' . $sig;
    }
}
