<?php

namespace App\Providers;

use App\Models\Setting;
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
        $this->overrideMailConfigFromSettings();
        $this->overrideWebPushConfigFromSettings();
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
