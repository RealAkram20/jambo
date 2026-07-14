{{--
    Head-tags partial — included from Modules/Frontend/resources/views/
    layouts/master.blade.php just inside the closing </head>.

    Pulls everything from the seo.* settings table values, with a
    short-circuit at the top so a fresh install (no settings seeded
    yet) silently outputs nothing instead of erroring. The whole
    file is safe to render even when the Seo module is disabled —
    Blade's @include falls back to a missing-view warning that we
    pre-empt with @includeIf in the layout.

    Per-page overrides:
      - Pages can @section('seo:title', '...') to customise <title>
      - Pages can @section('seo:description', '...') for og:description
      - Pages can @section('seo:image', '...') for og:image
      - Pages can @push('seo:head', '...') to add JSON-LD or extra
        meta tags inside the <head>.
    Each falls back to a sensible global default if not provided.
--}}
@php
    $seo = [
        'tracking_enabled'       => (bool) setting('seo.tracking_enabled', false),
        'exclude_admins'         => (bool) setting('seo.exclude_admins', true),
        'ga4_id'                 => trim((string) setting('seo.ga4_id', '')),
        'gtm_id'                 => trim((string) setting('seo.gtm_id', '')),
        'gsc_verification'       => trim((string) setting('seo.gsc_verification', '')),
        'og_default_image'       => trim((string) setting('seo.og_default_image', '')),
        'og_default_description' => trim((string) setting('seo.og_default_description', '')),
        'twitter_handle'         => trim((string) setting('seo.twitter_handle', '')),
    ];

    // Don't track logged-in admins by default — admin's own pageviews
    // would otherwise inflate metrics and pollute audience data.
    $isAdmin = auth()->check() && method_exists(auth()->user(), 'hasRole')
        && auth()->user()->hasRole('admin');
    $shouldTrack = $seo['tracking_enabled']
        && !($seo['exclude_admins'] && $isAdmin);

    // Per-page metadata with global fallbacks. Sections are evaluated
    // when @yield runs; absent sections render empty so the falsy
    // checks below pick up the defaults.
    // seo_section() rather than yieldContent(): Blade runs e() over the
    // value of an inline @section before storing it, so reading it raw and
    // echoing through {{ }} escapes it twice — a synopsis containing "&"
    // or an apostrophe shipped as "&amp;amp;" / "&amp;#039;" in the meta
    // tags. seo_section() decodes once so {{ }} escapes exactly once.
    $ogImage = seo_section($__env, 'seo:image') ?: $seo['og_default_image'];
    $ogDescription = seo_section($__env, 'seo:description')
        ?: ($seo['og_default_description'] ?: meta_description());
    $ogTitle = seo_section($__env, 'seo:title')
        ?: (($title ?? null) ? $title . ' - ' . app_name() : app_name());

    // og:type — "website" is right for the home page and listings, but
    // wrong for a film (video.movie) or an episode (video.episode).
    // Facebook/LinkedIn use it to pick the card treatment.
    $ogType = seo_section($__env, 'seo:type') ?: 'website';

    // Canonical. Defaults to the current URL, but a page can point
    // elsewhere via @section('seo:canonical') — /watch/{slug} does
    // exactly that, aiming at /movie-detail/{slug}, because the two
    // URLs describe the same film and were otherwise splitting their
    // ranking signal as duplicates. Deliberately drops the query string:
    // ?ref=, ?page= and friends would otherwise each self-canonicalise
    // into a separate near-duplicate URL in Google's index.
    $canonical = seo_section($__env, 'seo:canonical') ?: url()->current();
    if ($canonical !== '' && !preg_match('#^https?://#i', $canonical)) {
        $canonical = url(ltrim($canonical, '/'));
    }

    // Open Graph and Twitter Card both require absolute URLs for the
    // image; relative paths like "/storage/foo.jpg" silently get
    // ignored by Facebook's scraper. Normalise here so admins can paste
    // any of:
    //   /storage/branding/logo.png         (relative)
    //   storage/branding/logo.png          (no leading slash)
    //   https://jambofilms.com/...         (absolute)
    // and the meta tag always renders an absolute URL.
    if ($ogImage !== '' && !preg_match('#^https?://#i', $ogImage)) {
        $ogImage = url(ltrim($ogImage, '/'));
    }
@endphp

{{-- Canonical URL: tells Google which copy of duplicate content to
     index. Important when query strings or pagination would otherwise
     produce many near-duplicate URLs. --}}
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph (Facebook, LinkedIn, WhatsApp share previews). --}}
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="{{ app_name() }}">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    {{-- WhatsApp + Telegram are noticeably pickier than LinkedIn /
         Facebook: they often drop the image when the supplementary
         tags are missing. secure_url pins the HTTPS variant; image
         type lets the scraper short-circuit a HEAD request. We can
         derive the type from the URL's extension; if it doesn't
         match a known image extension we omit the tag rather than
         lie about the MIME. Width/height are intentionally NOT
         emitted — they'd be wrong for arbitrary admin-pasted URLs
         (Dropbox / Contabo / external CDN). Operators who want them
         can override per-page via @push('seo:head', ...) below. --}}
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    @php
        $ogImageExt = strtolower((string) pathinfo(parse_url($ogImage, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $ogImageType = match ($ogImageExt) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            default       => null,
        };
    @endphp
    @if ($ogImageType)
        <meta property="og:image:type" content="{{ $ogImageType }}">
    @endif
    {{-- Alt text is not strictly required but Facebook's Sharing
         Debugger flags its absence as a warning. Use the page title
         as a sensible default. --}}
    <meta property="og:image:alt" content="{{ $ogTitle }}">
@endif

{{-- Twitter Card. summary_large_image is the rich preview that shows
     a full-width image instead of a thumbnail. --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
@if ($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif
@if ($seo['twitter_handle'])
    <meta name="twitter:site" content="{{ $seo['twitter_handle'] }}">
@endif

{{-- Google Search Console domain ownership. The token is base64-url
     so HTML-escape is sufficient; don't try to be clever and put it
     in a script body. --}}
@if ($seo['gsc_verification'])
    <meta name="google-site-verification" content="{{ $seo['gsc_verification'] }}">
@endif

{{-- Site-wide structured data: Organization (name, logo, social
     profiles — feeds the knowledge panel and the site name shown in
     results) and WebSite (carries the sitelinks search action). These
     are page-independent, so they render on every page; the content
     pages push their own Movie / TVSeries / TVEpisode graphs onto the
     seo:head stack below.

     Wrapped because a broken setting must not be able to 500 a page
     that ranks. If the Content module is unavailable or a setting is
     malformed, we log and emit nothing rather than take the page down. --}}
@php
    $siteSchemas = [];
    try {
        $siteSchemas = [
            \Modules\Seo\app\Support\StructuredData::organization(),
            \Modules\Seo\app\Support\StructuredData::webSite(),
        ];
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('[seo] site structured data failed', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);
    }
@endphp
@includeIf('seo::partials.json-ld', ['schemas' => $siteSchemas])

{{-- Per-page additional head content (JSON-LD, extra meta) — pushed
     by detail pages via @push('seo:head', ...). This is where the
     Movie / TVSeries / TVEpisode / BreadcrumbList graphs land. --}}
@stack('seo:head')

{{-- Google Analytics 4 (gtag.js). Loaded async so it doesn't block
     first paint. Wrapped so a misconfigured ID can't break the page —
     if tracking is disabled or the admin viewer is excluded, this
     block emits nothing. --}}
@if ($shouldTrack && $seo['ga4_id'])
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seo['ga4_id'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($seo['ga4_id']), {
            // anonymize_ip is the modern default in GA4 but specifying
            // it keeps the privacy expectation explicit.
            'anonymize_ip': true,
            // Match the operator's exclude-admins toggle on the
            // server-side render, but also send the role hint as a
            // user property so any admin sessions that slip through
            // (e.g. sub-second cache) are filterable in GA4.
            'user_properties': {
                'is_admin': @json($isAdmin),
            }
        });
    </script>
@endif

{{-- Google Tag Manager (alternative / additional to gtag). Same
     gating as GA4. Two-snippet install: head-script here, body-noscript
     fallback handled by an @push from layout if needed; for now most
     installs only need the head snippet for client-side firing. --}}
@if ($shouldTrack && $seo['gtm_id'])
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer',@json($seo['gtm_id']));
    </script>
@endif
