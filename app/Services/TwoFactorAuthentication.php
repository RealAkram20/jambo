<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Thin wrapper around pragmarx/google2fa that keeps TOTP concerns in
 * one place:
 *   - secret + recovery-code lifecycle (encrypted at rest)
 *   - QR provisioning URI + SVG render
 *   - code verification (handles ±1 step drift, one-shot recovery
 *     codes, and strips the saved 2FA state on full disable)
 *
 * Callers never touch the `two_factor_*` columns directly.
 */
class TwoFactorAuthentication
{
    public function __construct(private Google2FA $engine)
    {
    }

    /**
     * Generate a fresh secret + 8 single-use recovery codes and stash
     * them on the user. The user is NOT considered enabled yet —
     * `two_factor_confirmed_at` stays null until they prove they can
     * produce a valid OTP via `confirm()`.
     */
    public function generatePendingSetup(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'         => Crypt::encryptString($this->engine->generateSecretKey()),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($this->freshRecoveryCodes())),
            'two_factor_confirmed_at'   => null,
        ])->save();
    }

    /**
     * Flip the feature ON once a valid code is provided during setup.
     * Returns false if the code is wrong — caller re-prompts.
     */
    public function confirm(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) return false;

        if (!$this->verifyTotp($user, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        return true;
    }

    /**
     * Wipes all 2FA state — used by the user's "Disable 2FA" button
     * and by admin account recovery.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();
    }

    /**
     * Check a 6-digit TOTP code. Uses a ±1 window (≈30s each side) to
     * forgive clock drift on the user's phone.
     */
    public function verifyTotp(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) return false;

        $secret = Crypt::decryptString($user->two_factor_secret);

        return (bool) $this->engine->verifyKey($secret, preg_replace('/\s+/', '', $code), 1);
    }

    /**
     * Recovery codes are strings like "XK2Q3L9M-ABCD1234" (stored as
     * an array after stripping the hyphen on compare). Consuming one
     * removes it from the stored set so the same code can't be
     * reused.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->getRecoveryCodes($user);
        if (empty($codes)) return false;

        $normalised = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code));

        foreach ($codes as $i => $stored) {
            if (hash_equals(strtoupper(preg_replace('/[^A-Z0-9]/i', '', $stored)), $normalised)) {
                unset($codes[$i]);
                $this->setRecoveryCodes($user, array_values($codes));
                return true;
            }
        }
        return false;
    }

    public function getRecoveryCodes(User $user): array
    {
        if (!$user->two_factor_recovery_codes) return [];
        return json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?: [];
    }

    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->freshRecoveryCodes();
        $this->setRecoveryCodes($user, $codes);
        return $codes;
    }

    /**
     * QR provisioning URI consumed by Google Authenticator, Authy,
     * 1Password, etc. Label uses the app name + email so users can
     * tell Jambo apart from any other TOTP they have saved.
     */
    public function qrCodeSvg(User $user): string
    {
        $secret = Crypt::decryptString($user->two_factor_secret);
        $issuer = config('app.name', 'Jambo');

        $url = $this->engine->getQRCodeUrl($issuer, $user->email, $secret);

        $writer = new Writer(
            new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd())
        );
        return $writer->writeString($url);
    }

    public function secretForManualEntry(User $user): ?string
    {
        return $user->two_factor_secret
            ? Crypt::decryptString($user->two_factor_secret)
            : null;
    }

    /* -------------------- private helpers ----------------------- */

    private function freshRecoveryCodes(int $count = 8): array
    {
        return array_map(
            fn () => strtoupper(Str::random(8)) . '-' . strtoupper(Str::random(8)),
            range(1, $count)
        );
    }

    private function setRecoveryCodes(User $user, array $codes): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codes)),
        ])->save();
    }
}
