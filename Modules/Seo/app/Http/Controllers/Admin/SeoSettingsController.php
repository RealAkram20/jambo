<?php

namespace Modules\Seo\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin form for analytics IDs, Search Console verification, and
 * default Open Graph / Twitter Card values. All values are stored in
 * the global `settings` table under the `seo.*` key prefix so they
 * benefit from the same flush-on-write cache the Setting model
 * already implements (no extra cache layer here).
 *
 * Two POST routes split the form so a typo in the OG image path
 * doesn't lose the operator's GA4 ID and vice versa — each section
 * has its own status flash so the admin sees confirmation under
 * exactly the section they just saved.
 */
class SeoSettingsController extends Controller
{
    public function index(): View
    {
        return view('seo::admin.settings', [
            'tracking' => [
                'enabled'           => (bool) setting('seo.tracking_enabled', false),
                'exclude_admins'    => (bool) setting('seo.exclude_admins', true),
                'ga4_id'            => setting('seo.ga4_id', ''),
                'gtm_id'            => setting('seo.gtm_id', ''),
                'gsc_verification'  => setting('seo.gsc_verification', ''),
                'sitemap_enabled'   => (bool) setting('seo.sitemap_enabled', true),
            ],
            'social' => [
                'og_default_image'       => setting('seo.og_default_image', ''),
                'og_default_description' => setting('seo.og_default_description', ''),
                'twitter_handle'         => setting('seo.twitter_handle', ''),
            ],
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tracking_enabled'  => ['nullable', 'in:0,1'],
            'exclude_admins'    => ['nullable', 'in:0,1'],
            'sitemap_enabled'   => ['nullable', 'in:0,1'],
            // GA4 IDs look like "G-XXXXXXXXXX"; GTM is "GTM-XXXXXXX". Both
            // are short ASCII tokens — strict regex prevents accidental
            // pastes of full HTML snippets that would break the page.
            'ga4_id'            => ['nullable', 'string', 'max:30', 'regex:/^G-[A-Z0-9]{4,}$/i'],
            'gtm_id'            => ['nullable', 'string', 'max:30', 'regex:/^GTM-[A-Z0-9]{4,}$/i'],
            // GSC verification token is base64url; alphanumeric + - _.
            'gsc_verification'  => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_\-]+$/'],
        ]);

        // setting() takes [key, value] pairs (positional), not an
        // associative array — one call per key. The helper's signature
        // matches the existing PaymentSettingsController pattern.
        setting(['seo.tracking_enabled', $data['tracking_enabled'] ?? '0']);
        setting(['seo.exclude_admins',   $data['exclude_admins']   ?? '0']);
        setting(['seo.sitemap_enabled',  $data['sitemap_enabled']  ?? '0']);
        setting(['seo.ga4_id',           trim((string) ($data['ga4_id'] ?? ''))]);
        setting(['seo.gtm_id',           trim((string) ($data['gtm_id'] ?? ''))]);
        setting(['seo.gsc_verification', trim((string) ($data['gsc_verification'] ?? ''))]);

        Setting::flushCache();

        return redirect()
            ->route('admin.seo.index')
            ->with('status_seo_general', 'Analytics & Search Console settings saved.');
    }

    public function updateSocial(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'og_default_image'       => ['nullable', 'string', 'max:500'],
            'og_default_description' => ['nullable', 'string', 'max:300'],
            'twitter_handle'         => ['nullable', 'string', 'max:30', 'regex:/^@?[A-Za-z0-9_]{1,29}$/'],
        ]);

        // Normalise Twitter handle to include the leading @ so it
        // round-trips back into the form correctly and Twitter's card
        // validator picks it up.
        $handle = trim((string) ($data['twitter_handle'] ?? ''));
        if ($handle !== '' && !str_starts_with($handle, '@')) {
            $handle = '@' . $handle;
        }

        setting(['seo.og_default_image',       trim((string) ($data['og_default_image'] ?? ''))]);
        setting(['seo.og_default_description', trim((string) ($data['og_default_description'] ?? ''))]);
        setting(['seo.twitter_handle',         $handle]);

        Setting::flushCache();

        return redirect()
            ->route('admin.seo.index')
            ->with('status_seo_social', 'Social-share defaults saved.');
    }
}
