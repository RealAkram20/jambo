<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private array $fileFields = ['logo', 'favicon', 'preloader'];

    public function index()
    {
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name'         => ['required', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'logo'             => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon'          => ['nullable', 'image', 'mimes:png,ico,x-icon,svg', 'max:512'],
            'preloader'        => ['nullable', 'mimes:gif,png,jpg,jpeg,webp,svg', 'max:2048'],
            'logo_url'         => ['nullable', 'string', 'max:1000'],
            'favicon_url'      => ['nullable', 'string', 'max:1000'],
            'preloader_url'    => ['nullable', 'string', 'max:1000'],
            // SMTP
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_encryption'   => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ]);

        Setting::set('app_name', $data['app_name']);
        Setting::set('meta_description', $data['meta_description'] ?? '');

        // SMTP — store non-secret fields in cleartext, encrypt the
        // password. An empty password field means "keep the existing
        // one" so admins can re-save this form without re-entering it.
        Setting::set('mail_host', $data['mail_host'] ?? '');
        Setting::set('mail_port', (string) ($data['mail_port'] ?? ''));
        Setting::set('mail_username', $data['mail_username'] ?? '');
        Setting::set('mail_encryption', $data['mail_encryption'] ?? '');
        Setting::set('mail_from_address', $data['mail_from_address'] ?? '');
        Setting::set('mail_from_name', $data['mail_from_name'] ?? '');

        if (!empty($data['mail_password'])) {
            Setting::set('mail_password', Crypt::encryptString($data['mail_password']));
        }

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
            ->with('status', __('messages.setting_save') ?? 'Settings updated.');
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
