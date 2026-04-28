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
        // Pre-process the GSC verification field BEFORE validation —
        // Google Search Console hands operators an HTML snippet like:
        //   <meta name="google-site-verification" content="abcd1234..." />
        // and people predictably paste the whole thing into the input.
        // Pull the content="..." value out so the validator only sees
        // the token. Also tolerate a leading/trailing meta tag with
        // single quotes or no quotes around the value.
        $rawGsc = trim((string) $request->input('gsc_verification', ''));
        if ($rawGsc !== '' && stripos($rawGsc, 'google-site-verification') !== false) {
            if (preg_match('/content\s*=\s*["\']([A-Za-z0-9_\-]+)["\']/i', $rawGsc, $m)) {
                $rawGsc = $m[1];
                $request->merge(['gsc_verification' => $rawGsc]);
            }
        }

        // Same forgiveness for GA4 ID — operators sometimes paste the
        // full <script src="...?id=G-XXX"> tag instead of the bare ID.
        $rawGa4 = trim((string) $request->input('ga4_id', ''));
        if ($rawGa4 !== '' && stripos($rawGa4, '<') !== false) {
            if (preg_match('/(G-[A-Z0-9]{4,})/i', $rawGa4, $m)) {
                $request->merge(['ga4_id' => strtoupper($m[1])]);
            }
        }
        // And GTM: people paste the full container snippet too.
        $rawGtm = trim((string) $request->input('gtm_id', ''));
        if ($rawGtm !== '' && stripos($rawGtm, '<') !== false) {
            if (preg_match('/(GTM-[A-Z0-9]{4,})/i', $rawGtm, $m)) {
                $request->merge(['gtm_id' => strtoupper($m[1])]);
            }
        }

        $data = $request->validate([
            'tracking_enabled'  => ['nullable', 'in:0,1'],
            'exclude_admins'    => ['nullable', 'in:0,1'],
            'sitemap_enabled'   => ['nullable', 'in:0,1'],
            // GA4 IDs look like "G-XXXXXXXXXX"; GTM is "GTM-XXXXXXX". Both
            // are short ASCII tokens — strict regex prevents accidental
            // pastes of full HTML snippets that would break the page.
            // The pre-processing above handles the common "pasted the
            // whole snippet" case so the regex can stay strict here.
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
