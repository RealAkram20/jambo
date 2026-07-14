<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The entire app uses Bootstrap 5 (admin via the Streamit
        // template, frontend via the same template's public theme).
        // Laravel's default pagination renderer is Tailwind since v9,
        // which ships inline SVG arrows sized with Tailwind utility
        // classes that don't exist here — the result is enormous
        // unconstrained chevrons. Switching the default to Bootstrap-5
        // markup makes every `->links()` call render as a proper
        // `.pagination .page-item .page-link` list the Streamit CSS
        // already styles correctly.
        Paginator::useBootstrapFive();

        $this->overrideMailConfigFromSettings();
        $this->overrideWebPushConfigFromSettings();
        $this->overrideGoogleAuthConfigFromSettings();
        $this->overrideVideoCdnConfigFromSettings();
    }

    /**
     * Video CDN pull-zone credentials saved via /admin/settings take
     * precedence over the .env fallbacks baked into
     * Modules/Streaming/config/config.php. CdnUrlResolver reads
     * config('streaming.cdn.zones'), so overriding it here is what makes
     * an admin-entered hostname or token key take effect with no deploy.
     *
     * Token keys are Crypt-encrypted at rest; a decrypt failure (APP_KEY
     * rotated since save) falls through to the .env value rather than
     * 500-ing playback. Empty settings never override — the zone keeps
     * whatever config/.env already provided.
     */
    private function overrideVideoCdnConfigFromSettings(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $this->applyCdnZoneSettings('backblaze', 'cdn_b2_', ['bucket' => 'cdn_b2_bucket']);
        $this->applyCdnZoneSettings('dropbox', 'cdn_dropbox_');
    }

    /**
     * Push one zone's saved settings onto config('streaming.cdn.zones.*').
     * $extra maps additional plain (non-secret) config keys to their
     * setting name — e.g. the Backblaze bucket.
     */
    private function applyCdnZoneSettings(string $zone, string $prefix, array $extra = []): void
    {
        $base = "streaming.cdn.zones.$zone";

        if ($hostname = Setting::get($prefix.'hostname')) {
            Config::set("$base.hostname", trim($hostname));
        }
        if ($ttl = Setting::get($prefix.'token_ttl')) {
            Config::set("$base.token_ttl", (int) $ttl);
        }
        foreach ($extra as $configKey => $settingName) {
            if ($value = Setting::get($settingName)) {
                Config::set("$base.$configKey", trim($value));
            }
        }
        if ($encKey = Setting::get($prefix.'token_key')) {
            try {
                Config::set("$base.token_key", Crypt::decryptString($encKey));
            } catch (\Throwable $e) {
                // APP_KEY rotated since save — leave the .env fallback.
            }
        }
    }

    /**
     * Google OAuth (Socialite) credentials saved via /admin/settings
     * take precedence over the .env fallbacks. The login/register
     * views show the "Continue with Google" button whenever
     * config('services.google.client_id') is non-empty, so this also
     * drives button visibility: switch OFF → button hidden even if
     * .env still carries credentials.
     */
    private function overrideGoogleAuthConfigFromSettings(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        // Explicit OFF (saved as false) hides Google sign-in entirely.
        // Never-saved (null) leaves whatever .env provides untouched.
        $enabled = Setting::get('google_auth_enabled');
        if ($enabled === false) {
            Config::set('services.google.client_id', null);
            return;
        }

        if ($clientId = Setting::get('google_client_id')) {
            Config::set('services.google.client_id', trim($clientId));
        }
        if ($encSecret = Setting::get('google_client_secret')) {
            try {
                Config::set('services.google.client_secret', Crypt::decryptString($encSecret));
            } catch (\Throwable $e) {
                // APP_KEY rotated since save — leave the .env fallback.
            }
        }
    }

    /**
     * If the admin has saved SMTP credentials via /admin/settings, use
     * them at runtime instead of the .env / config fallbacks. Runs on
     * every request, but `Setting::getAllSettings()` is cached forever
     * (invalidated on write), so it's one memory hit per request.
     *
     * Wrapped in try/catch + schema check so the installer/setup wizard
     * can still boot before the settings table exists.
     */
    private function overrideMailConfigFromSettings(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $host = Setting::get('mail_host');
        if (empty($host)) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);

        if ($port = Setting::get('mail_port')) {
            Config::set('mail.mailers.smtp.port', (int) $port);
        }
        if ($username = Setting::get('mail_username')) {
            Config::set('mail.mailers.smtp.username', $username);
        }
        if ($encryption = Setting::get('mail_encryption')) {
            Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
        }
        if ($encPassword = Setting::get('mail_password')) {
            try {
                Config::set('mail.mailers.smtp.password', Crypt::decryptString($encPassword));
            } catch (\Throwable $e) {
                // decryption failed (APP_KEY rotated?) — leave the
                // existing config value so the caller gets a real auth
                // error from the SMTP server instead of a 500 here.
            }
        }
        if ($fromAddress = Setting::get('mail_from_address')) {
            Config::set('mail.from.address', $fromAddress);
        }
        if ($fromName = Setting::get('mail_from_name')) {
            Config::set('mail.from.name', $fromName);
        }
    }

    /**
     * Override webpush VAPID credentials with admin-saved settings if
     * present. Same shape as overrideMailConfigFromSettings — the saved
     * private key is Crypt-encrypted at rest.
     */
    private function overrideWebPushConfigFromSettings(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        if ($public = Setting::get('webpush_vapid_public_key')) {
            Config::set('webpush.vapid.public_key', $public);
        }
        if ($subject = Setting::get('webpush_vapid_subject')) {
            Config::set('webpush.vapid.subject', $subject);
        }
        if ($encPrivate = Setting::get('webpush_vapid_private_key')) {
            try {
                Config::set('webpush.vapid.private_key', Crypt::decryptString($encPrivate));
            } catch (\Throwable $e) {
                // APP_KEY rotated since save — leave the .env fallback in place.
            }
        }
    }
}
