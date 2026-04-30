<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Optional Google reCAPTCHA verification, configured live from
 * /admin/settings (no .env edit required).
 *
 * Both v2 ("I'm not a robot" widget) and v3 (invisible scoring) are
 * supported; the admin form picks one. When recaptcha_enabled is
 * false (the default), every call to verify() returns true so forms
 * keep working unchanged. The honeypot + throttle defences run
 * unconditionally regardless of this setting.
 *
 * Secret key is stored encrypted at rest in the settings table; the
 * admin update path encrypts via Crypt::encryptString and we decrypt
 * here on demand. Site key is public (it ends up in the rendered
 * HTML), so it's stored in cleartext.
 */
class RecaptchaService
{
    public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public static function isEnabled(): bool
    {
        return (bool) setting('recaptcha_enabled')
            && trim((string) setting('recaptcha_site_key')) !== ''
            && trim((string) setting('recaptcha_secret_key')) !== '';
    }

    /** "v2" (default) or "v3". */
    public static function version(): string
    {
        return setting('recaptcha_version') === 'v3' ? 'v3' : 'v2';
    }

    public static function siteKey(): ?string
    {
        return self::isEnabled() ? (string) setting('recaptcha_site_key') : null;
    }

    /** v3 score threshold; ignored for v2. Default 0.5 per Google's recommendation. */
    public static function scoreThreshold(): float
    {
        $value = (float) setting('recaptcha_score_threshold', 0.5);
        // Clamp to a sane band so an admin typo (e.g. 5 instead of 0.5)
        // doesn't accidentally accept everything or nothing.
        return max(0.1, min(0.9, $value ?: 0.5));
    }

    /**
     * Verify the token submitted with the form. When disabled, always
     * returns true so installs without keys keep working.
     *
     * @param  string|null $token             Value of `g-recaptcha-response`.
     * @param  string|null $expectedAction    v3 only — must match the action
     *                                        the JS used at execute() time.
     */
    public static function verify(?string $token, ?string $expectedAction = null): bool
    {
        if (!self::isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $secret = (string) setting('recaptcha_secret_key');
        // Try to decrypt — the admin update flow encrypts on save.
        // Fall through if the value is plaintext (e.g. set via tinker
        // or a future migration) so we never hard-fail on a value that
        // simply wasn't run through Crypt.
        try {
            $secret = Crypt::decryptString($secret);
        } catch (\Throwable) {
            // leave as-is
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => request()->ip(),
                ])
                ->json();
        } catch (\Throwable $e) {
            Log::warning('[recaptcha] verify call failed', ['error' => $e->getMessage()]);
            return false;
        }

        if (empty($response['success'])) {
            // error-codes is an array of strings — log so the operator
            // can spot misconfiguration (invalid-input-secret etc.).
            Log::info('[recaptcha] verify rejected', [
                'errors' => $response['error-codes'] ?? [],
                'version' => self::version(),
            ]);
            return false;
        }

        if (self::version() === 'v3') {
            $score = (float) ($response['score'] ?? 0);
            if ($score < self::scoreThreshold()) {
                Log::info('[recaptcha] v3 score below threshold', [
                    'score' => $score,
                    'threshold' => self::scoreThreshold(),
                    'action' => $response['action'] ?? null,
                ]);
                return false;
            }
            if ($expectedAction && ($response['action'] ?? null) !== $expectedAction) {
                Log::info('[recaptcha] v3 action mismatch', [
                    'expected' => $expectedAction,
                    'got' => $response['action'] ?? null,
                ]);
                return false;
            }
        }

        return true;
    }
}
