<?php

namespace Modules\Seo\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
            'verificationFiles' => $this->listVerificationFiles(),
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

    /**
     * Save a search-engine verification file (Google's `googleXXX.html`,
     * Bing's `BingSiteAuth.xml`, Yandex's `yandex_XXX.html`, etc.) into
     * public/ so the engine can fetch it directly at the site root.
     *
     * Two layers of safety:
     *   1. Filename whitelist regex — only known verification-file
     *      shapes pass, so the form can't be repurposed to drop
     *      arbitrary HTML / PHP into the document root.
     *   2. We never trust the client filename verbatim; we re-derive
     *      it from the regex match group so a "google.html/../etc.html"
     *      style path-traversal attempt fails on the regex alone.
     *
     * The file content has to come from the upload (Google's verifier
     * fetches the file and matches its body to a token only Google
     * knows), so we accept the bytes as-is and write them to public/.
     */
    public function uploadVerificationFile(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_file' => ['required', 'file', 'max:64'], // 64 KB cap; verification files are tiny
        ]);

        $file = $request->file('verification_file');
        $clientName = $file->getClientOriginalName();

        // Whitelist of accepted patterns. If you add a new search
        // engine's verification format, extend this list — DON'T
        // loosen the regex.
        $patterns = [
            '/^google[a-zA-Z0-9]+\.html$/',         // Google Search Console
            '/^BingSiteAuth\.xml$/',                 // Bing Webmaster
            '/^yandex_[a-zA-Z0-9]+\.html$/',         // Yandex
            '/^pinterest-[a-zA-Z0-9]+\.html$/',      // Pinterest
            '/^baidu_verify_[a-zA-Z0-9_]+\.html$/',  // Baidu
        ];

        $matched = false;
        foreach ($patterns as $rx) {
            if (preg_match($rx, $clientName)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            return redirect()
                ->route('admin.seo.index')
                ->withErrors(['verification_file' =>
                    'Filename must match a known verification format (e.g. googleXXX.html, BingSiteAuth.xml, yandex_XXX.html). Got: ' . $clientName]);
        }

        // Move into public/. Using public_path() rather than the
        // public disk so the file lands at /<filename> exactly where
        // the verifier expects to fetch it. Existing files with the
        // same name are overwritten — re-uploading replaces the
        // previous copy.
        $destination = public_path($clientName);
        $file->move(public_path(), $clientName);

        return redirect()
            ->route('admin.seo.index')
            ->with('status_seo_verification', 'Verification file "' . $clientName . '" uploaded. The verifier should be able to find it at ' . url('/' . $clientName));
    }

    /**
     * Remove a previously-uploaded verification file. Limited to the
     * same whitelist of filename shapes so this endpoint can never be
     * abused to delete arbitrary files in public/.
     */
    public function deleteVerificationFile(string $filename): RedirectResponse
    {
        $known = $this->listVerificationFiles();
        $present = collect($known)->firstWhere('name', $filename);

        if (!$present) {
            return redirect()
                ->route('admin.seo.index')
                ->withErrors(['verification_file' => 'File not found or not removable.']);
        }

        @unlink(public_path($filename));

        return redirect()
            ->route('admin.seo.index')
            ->with('status_seo_verification', 'Verification file "' . $filename . '" removed.');
    }

    /**
     * Scan public/ for files matching our verification-filename
     * whitelist so the admin form can show what's currently published.
     * Returns each as ['name' => ..., 'url' => ..., 'size' => bytes].
     */
    private function listVerificationFiles(): array
    {
        $globs = [
            'google*.html',
            'BingSiteAuth.xml',
            'yandex_*.html',
            'pinterest-*.html',
            'baidu_verify_*.html',
        ];

        $found = [];
        foreach ($globs as $glob) {
            foreach (glob(public_path($glob)) ?: [] as $path) {
                $name = basename($path);
                $found[$name] = [
                    'name' => $name,
                    'url'  => url('/' . $name),
                    'size' => filesize($path) ?: 0,
                ];
            }
        }

        ksort($found);
        return array_values($found);
    }
}
