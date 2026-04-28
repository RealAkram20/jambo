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
    $ogImage = trim((string) ($__env->yieldContent('seo:image') ?: $seo['og_default_image']));
    $ogDescription = trim((string) ($__env->yieldContent('seo:description')
        ?: ($seo['og_default_description'] ?: meta_description())));
    $ogTitle = trim((string) ($__env->yieldContent('seo:title')
        ?: (($title ?? null) ? $title . ' - ' . app_name() : app_name())));
    $canonical = url()->current();
@endphp

{{-- Canonical URL: tells Google which copy of duplicate content to
     index. Important when query strings or pagination would otherwise
     produce many near-duplicate URLs. --}}
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph (Facebook, LinkedIn, WhatsApp share previews). --}}
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ app_name() }}">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
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

{{-- Per-page additional head content (JSON-LD, extra meta) — pushed
     by detail pages via @push('seo:head', ...). --}}
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
