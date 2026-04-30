<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Minishlink\WebPush\VAPID;

class SettingController extends Controller
{
    private array $fileFields = ['logo', 'favicon', 'preloader'];

    public function index()
    {
        return view('admin.settings.index');
    }

    /**
     * Update only the General card fields (app name + meta description).
     * Per-section saves so an admin editing General isn't blocked by a
     * validation error elsewhere on the page.
     */
    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'app_name'         => ['required', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::set('app_name', $data['app_name']);
        Setting::set('meta_description', $data['meta_description'] ?? '');
        Setting::flushCache();

        return redirect()->route('admin.settings.index')
            ->with('status_general', 'General settings saved.');
    }

    public function updateBranding(Request $request)
    {
        // SVG was previously accepted on logo / favicon / preloader. An
        // SVG file can carry inline <script>; navigating to its URL
        // executes that script in the app origin and steals admin
        // sessions. Drop it from the allowlist. Existing SVG files
        // already saved keep displaying — this only blocks new SVG
        // uploads. If you need vector logos later, add
        // `enshrined/svg-sanitize` and re-allow with a sanitiser pass.
        $request->validate([
            'logo'          => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon'       => ['nullable', 'image', 'mimes:png,ico,x-icon', 'max:512'],
            'preloader'     => ['nullable', 'mimes:gif,png,jpg,jpeg,webp', 'max:2048'],
            'logo_url'      => ['nullable', 'string', 'max:1000'],
            'favicon_url'   => ['nullable', 'string', 'max:1000'],
            'preloader_url' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($this->fileFields as $field) {
            if ($request->hasFile($field)) {
                $old = Setting::get($field);
                if ($old && str_starts_with($old, '/storage/branding/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $old));
                }
                $path = $request->file($field)->store('branding', 'public');
                Setting::set($field, Storage::url($path));
                continue;
            }

            $urlKey = $field . '_url';
            if ($request->filled($urlKey)) {
                $url = $this->normalizeMediaUrl($request->input($urlKey));
                $old = Setting::get($field);
                if ($old && $old !== $url && str_starts_with($old, '/storage/branding/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $old));
                }
                Setting::set($field, $url);
            }
        }

        Setting::flushCache();

        return redirect()->route('admin.settings.index')
            ->with('status_branding', 'Branding saved.');
    }

    public function updateSmtp(Request $request)
    {
        $data = $request->validate([
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_encryption'   => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ]);

        Setting::set('mail_host', $data['mail_host'] ?? '');
        Setting::set('mail_port', (string) ($data['mail_port'] ?? ''));
        Setting::set('mail_username', $data['mail_username'] ?? '');
        Setting::set('mail_encryption', $data['mail_encryption'] ?? '');
        Setting::set('mail_from_address', $data['mail_from_address'] ?? '');
        Setting::set('mail_from_name', $data['mail_from_name'] ?? '');

        // Blank password = keep existing; non-blank = re-encrypt and
        // overwrite. Admins can re-save any other field without
        // re-entering the SMTP password.
        if (!empty($data['mail_password'])) {
            Setting::set('mail_password', Crypt::encryptString($data['mail_password']));
        }

        Setting::flushCache();

        return redirect()->route('admin.settings.index')
            ->with('status_smtp', 'SMTP settings saved.');
    }

    /**
     * Send a test email to the currently authenticated admin using
     * whatever SMTP configuration is active right now (overridden at
     * boot from the settings table — see AppServiceProvider). Lets
     * admins verify their SMTP config without waiting for a real
     * notification to fire.
     */
    public function sendTestEmail(Request $request)
    {
        $admin = $request->user();
        if (empty($admin->email)) {
            return back()->with('smtp_error', 'Your admin account has no email address set.');
        }

        try {
            Mail::raw(
                "This is a test email from " . config('app.name') . ".\n\n" .
                "If you're reading this in your inbox, SMTP is configured correctly.\n" .
                "Sent at " . now()->toDateTimeString() . '.',
                function ($m) use ($admin) {
                    $m->to($admin->email)
                      ->subject('SMTP test — ' . config('app.name'));
                }
            );
        } catch (\Throwable $e) {
            return back()->with('smtp_error', 'Send failed: ' . $e->getMessage());
        }

        return back()->with('smtp_status', "Test email sent to {$admin->email}. Check your inbox (and spam folder).");
    }

    /**
     * Save VAPID credentials (public, private, subject) to the settings
     * table. The private key is Crypt-encrypted at rest. AppServiceProvider
     * overrides config('webpush.*') from these values at boot, so the
     * change takes effect on the next request with no .env edit.
     *
     * Pass an empty private key to keep the existing one (same pattern
     * the mail SMTP form uses).
     */
    public function updateVapid(Request $request)
    {
        $data = $request->validate([
            'vapid_public_key'  => ['required', 'string', 'max:255'],
            'vapid_private_key' => ['nullable', 'string', 'max:255'],
            'vapid_subject'     => ['required', 'string', 'max:255', 'regex:/^(mailto:|https?:\/\/)/i'],
        ], [
            'vapid_subject.regex' => 'Subject must start with mailto: or https://',
        ]);

        Setting::set('webpush_vapid_public_key', trim($data['vapid_public_key']));
        Setting::set('webpush_vapid_subject',    trim($data['vapid_subject']));

        if (!empty($data['vapid_private_key'])) {
            Setting::set('webpush_vapid_private_key', Crypt::encryptString(trim($data['vapid_private_key'])));
        }

        Setting::flushCache();

        return redirect()->route('admin.settings.index')
            ->with('status_vapid', 'VAPID credentials saved. New keys are active immediately.');
    }

    /**
     * Generate a fresh VAPID keypair server-side. Requires an openssl
     * build with EC curve support (prime256v1). On XAMPP/Windows boxes
     * without the right openssl.cnf this throws — the flash explains the
     * fallback (paste keys generated elsewhere via `php artisan webpush:vapid`).
     */
    public function generateVapid(Request $request)
    {
        try {
            $pair = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            Log::warning('[settings] VAPID generation failed', ['error' => $e->getMessage()]);
            return back()->with('vapid_error',
                'Could not generate keys on this server (' . $e->getMessage() . '). Paste a keypair generated elsewhere instead.');
        }

        Setting::set('webpush_vapid_public_key',  $pair['publicKey']);
        Setting::set('webpush_vapid_private_key', Crypt::encryptString($pair['privateKey']));
        if (empty(Setting::get('webpush_vapid_subject'))) {
            Setting::set('webpush_vapid_subject', 'mailto:admin@example.com');
        }

        Setting::flushCache();

        return redirect()->route('admin.settings.index')
            ->with('status_vapid', 'New VAPID keypair generated. Existing push subscriptions must re-subscribe.');
    }

    /**
     * Toggle maintenance mode + persist the message and optional
     * "back by" datetime. Admin role bypasses the MaintenanceMode
     * middleware entirely, so this controller stays reachable while
     * the site is dark.
     */
    /**
     * Save Google reCAPTCHA configuration. Site key is public (it ends
     * up in the rendered HTML); secret is encrypted at rest like the
     * SMTP password / Pesapal consumer secret. Sending a blank secret
     * means "keep the current value" — the same UX as updateSmtp().
     */
    public function updateRecaptcha(Request $request)
    {
        $data = $request->validate([
            'recaptcha_enabled'         => ['required', 'boolean'],
            'recaptcha_version'         => ['nullable', 'in:v2,v3'],
            'recaptcha_site_key'        => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key'      => ['nullable', 'string', 'max:255'],
            'recaptcha_score_threshold' => ['nullable', 'numeric', 'min:0.1', 'max:0.9'],
        ]);

        Setting::set('recaptcha_enabled', $data['recaptcha_enabled'] ? '1' : '0', 'boolean');
        Setting::set('recaptcha_version', $data['recaptcha_version'] ?? 'v2');
        Setting::set('recaptcha_site_key', trim((string) ($data['recaptcha_site_key'] ?? '')));
        Setting::set('recaptcha_score_threshold', (string) ($data['recaptcha_score_threshold'] ?? '0.5'));

        // Blank secret = keep existing. Non-blank = encrypt and overwrite.
        if (!empty($data['recaptcha_secret_key'])) {
            Setting::set('recaptcha_secret_key', Crypt::encryptString($data['recaptcha_secret_key']));
        }

        Setting::flushCache();

        return redirect()->route('admin.settings.index')
            ->with('status_recaptcha', 'reCAPTCHA settings saved.');
    }

    public function updateMaintenance(Request $request)
    {
        $data = $request->validate([
            'maintenance_enabled' => ['required', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
            'maintenance_until'   => ['nullable', 'date'],
        ]);

        Setting::set('maintenance_enabled', $data['maintenance_enabled'] ? '1' : '0', 'boolean');
        Setting::set('maintenance_message', $data['maintenance_message'] ?? '');
        Setting::set('maintenance_until',   $data['maintenance_until'] ?? '');
        Setting::flushCache();

        $msg = $data['maintenance_enabled']
            ? 'Maintenance mode is now ON — non-admin visitors are seeing the maintenance page.'
            : 'Maintenance mode is OFF — the site is live for everyone.';

        return redirect()->route('admin.settings.index')->with('status_maintenance', $msg);
    }

    /**
     * Accept absolute URLs or media paths copied from the File Manager.
     * Examples:
     *   https://site.test/storage/media/logos/x.png  → /storage/media/logos/x.png
     *   /storage/media/logos/x.png                   → unchanged
     *   media/logos/x.png                            → /storage/media/logos/x.png
     */
    private function normalizeMediaUrl(string $url): string
    {
        $url = trim($url);
        $appUrl = rtrim(config('app.url'), '/');
        if ($appUrl && str_starts_with($url, $appUrl)) {
            $url = substr($url, strlen($appUrl));
        }
        if (str_starts_with($url, 'media/') || str_starts_with($url, 'branding/')) {
            $url = '/storage/' . $url;
        }
        return $url;
    }
}
