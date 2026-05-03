@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">

            {{-- ─── Sitemap preview ────────────────────────────────────────
                 Lets the operator confirm what /sitemap.xml is publishing
                 right now without having to read XML. Counts per group +
                 a few recent samples per type. Backed by the same
                 SitemapController::entries() the live XML uses, so what
                 you see here is exactly what Google sees. --}}
            @php
                $totalUrls = collect($sitemapEntries)->sum(fn ($g) => $g->count());
                $groupMeta = [
                    'static'   => ['label' => 'Landing pages',   'icon' => 'ph-house-simple', 'color' => 'primary'],
                    'pages'    => ['label' => 'System pages',    'icon' => 'ph-file-text',    'color' => 'info'],
                    'movies'   => ['label' => 'Movies',          'icon' => 'ph-film-strip',   'color' => 'success'],
                    'shows'    => ['label' => 'Series',          'icon' => 'ph-television',   'color' => 'warning'],
                    'episodes' => ['label' => 'Episodes',        'icon' => 'ph-list-numbers', 'color' => 'secondary'],
                    'vjs'      => ['label' => 'VJs',             'icon' => 'ph-microphone',   'color' => 'danger'],
                ];
            @endphp
            <div class="card mb-4">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h4 class="card-title mb-0">
                            <i class="ph ph-magnifying-glass-plus me-1"></i>
                            Sitemap preview
                        </h4>
                        <p class="text-muted mb-0 mt-1" style="font-size:13px;">
                            What <code>/sitemap.xml</code> is currently publishing — compare against your
                            published movies, episodes, and pages. The same data Google sees, served fresh
                            from the database (no caching on this view).
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                            <i class="ph ph-arrow-square-out me-1"></i> Open sitemap.xml
                        </a>
                        <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                            <i class="ph ph-arrow-square-out me-1"></i> robots.txt
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Top-line stat row --}}
                    <div class="row g-2 mb-3">
                        @foreach ($groupMeta as $key => $meta)
                            @php $count = $sitemapEntries[$key]->count(); @endphp
                            <div class="col-6 col-md-4 col-xl-2">
                                <div class="border rounded p-3 text-center h-100" style="background: rgba(255,255,255,0.02);">
                                    <i class="ph {{ $meta['icon'] }} text-{{ $meta['color'] }} d-block mb-1" style="font-size: 22px;"></i>
                                    <div class="fw-semibold" style="font-size: 22px;">{{ $count }}</div>
                                    <div class="text-muted" style="font-size: 12px;">{{ $meta['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-2 pt-2 border-top">
                        <span class="text-muted" style="font-size: 12px;">
                            <strong class="text-white">{{ $totalUrls }}</strong> URLs total
                        </span>
                        @if (!setting('seo.sitemap_enabled', true))
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ph ph-warning"></i> Sitemap publishing is OFF — the live <code>/sitemap.xml</code> is empty
                            </span>
                        @endif
                    </div>

                    {{-- Per-group sample lists. Collapsible so the page stays compact when libraries grow. --}}
                    <div class="accordion" id="sitemapPreviewAccordion">
                        @foreach ($groupMeta as $key => $meta)
                            @continue($sitemapEntries[$key]->isEmpty())
                            @php
                                $items = $sitemapEntries[$key];
                                $sampleSize = 12;
                                $sample = $items->take($sampleSize);
                                $more = max(0, $items->count() - $sampleSize);
                                $accId = 'sitemap-group-' . $key;
                            @endphp
                            <div class="accordion-item" style="background: transparent; border-color: rgba(255,255,255,0.06);">
                                <h2 class="accordion-header" id="heading-{{ $accId }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse-{{ $accId }}" aria-expanded="false"
                                            aria-controls="collapse-{{ $accId }}"
                                            style="background: rgba(255,255,255,0.02); color: var(--bs-body-color); font-size: 14px;">
                                        <i class="ph {{ $meta['icon'] }} text-{{ $meta['color'] }} me-2"></i>
                                        <strong>{{ $meta['label'] }}</strong>
                                        <span class="badge bg-{{ $meta['color'] }}-subtle text-{{ $meta['color'] }} ms-2">{{ $items->count() }}</span>
                                    </button>
                                </h2>
                                <div id="collapse-{{ $accId }}" class="accordion-collapse collapse"
                                     aria-labelledby="heading-{{ $accId }}" data-bs-parent="#sitemapPreviewAccordion">
                                    <div class="accordion-body py-2">
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($sample as $entry)
                                                <li class="d-flex align-items-center justify-content-between py-1 border-bottom" style="border-color: rgba(255,255,255,0.04) !important;">
                                                    <div class="me-2" style="min-width: 0; flex: 1 1 auto;">
                                                        <div class="text-truncate" style="font-size: 13px;">{{ $entry['label'] ?? $entry['loc'] }}</div>
                                                        <div class="text-truncate text-muted" style="font-size: 11px;">{{ $entry['loc'] }}</div>
                                                    </div>
                                                    <a href="{{ $entry['loc'] }}" target="_blank" rel="noopener"
                                                       class="text-decoration-none flex-shrink-0" title="Open">
                                                        <i class="ph ph-arrow-square-out"></i>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($more > 0)
                                            <p class="text-muted mb-0 mt-2" style="font-size: 12px;">
                                                + {{ $more }} more {{ \Illuminate\Support\Str::plural('entry', $more) }} in the live sitemap
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($totalUrls === 0)
                        <div class="text-center py-3 text-muted" style="font-size: 13px;">
                            <i class="ph ph-info text-secondary d-block mb-2" style="font-size: 28px;"></i>
                            No published content yet — the sitemap will be empty until movies, series, or
                            episodes are published.
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title mb-0">SEO &amp; Analytics</h4>
                    <p class="text-muted mb-0 mt-1" style="font-size:13px;">
                        Connect Google Analytics, prove ownership in Search Console, and set the
                        defaults that show up when someone shares a Jambo link on social media.
                    </p>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ─── Live diagnostic ──────────────────────────────────
                         The single biggest source of "Google Tag Assistant
                         says my tag isn't there" tickets has been not
                         spotting that one of the silent gates is closed:
                         (a) the master switch is OFF, or (b) the viewer
                         testing it is an admin and exclude_admins is ON
                         (default). This block makes both visible without
                         the admin needing to read source. --}}
                    @php $diag = $gtagDiagnostic; @endphp
                    <div class="border rounded p-3 mb-4"
                         style="background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.08) !important;">
                        <h6 class="mb-3" style="font-size: 14px;">
                            <i class="ph ph-pulse me-1"></i>
                            Tag delivery — what's loading right now
                        </h6>

                        <div class="d-flex flex-column gap-2" style="font-size: 13px;">
                            {{-- Configured tag ID --}}
                            <div class="d-flex align-items-center gap-2">
                                @if ($diag['id'])
                                    <i class="ph ph-check-circle text-success"></i>
                                    <span>Tag ID saved: <code>{{ $diag['id'] }}</code></span>
                                @else
                                    <i class="ph ph-x-circle text-danger"></i>
                                    <span>No tag ID saved — paste it in the field below.</span>
                                @endif
                            </div>

                            {{-- Master switch --}}
                            <div class="d-flex align-items-center gap-2">
                                @if ($diag['tracking_enabled'])
                                    <i class="ph ph-check-circle text-success"></i>
                                    <span>Master switch: <strong>ON</strong></span>
                                @else
                                    <i class="ph ph-x-circle text-danger"></i>
                                    <span>Master switch: <strong>OFF</strong> — even with the ID saved, no tag is being emitted on any page.</span>
                                @endif
                            </div>

                            {{-- Anonymous visitor view --}}
                            <div class="d-flex align-items-center gap-2">
                                @if ($diag['renders_for_anon'])
                                    <i class="ph ph-check-circle text-success"></i>
                                    <span>An anonymous visitor (incognito / Google Tag Assistant): <strong>tag is rendered</strong>.</span>
                                @else
                                    <i class="ph ph-x-circle text-danger"></i>
                                    <span>An anonymous visitor: <strong>no tag</strong> ({{ implode(', ', $diag['reasons_blocking']) }}).</span>
                                @endif
                            </div>

                            {{-- Admin view (only meaningful if at least it would render for anon) --}}
                            <div class="d-flex align-items-center gap-2">
                                @if ($diag['renders_for_you'])
                                    <i class="ph ph-check-circle text-success"></i>
                                    <span>You, viewing the site logged in as admin: <strong>tag is rendered</strong>.</span>
                                @elseif ($diag['renders_for_anon'] && $diag['viewer_is_admin'] && $diag['exclude_admins'])
                                    <i class="ph ph-warning-circle text-warning"></i>
                                    <span>
                                        You, viewing the site logged in as admin: <strong>tag is suppressed</strong> by the
                                        "Don't track logged-in admins" toggle below — open <code>{{ url('/') }}</code>
                                        in an <strong>incognito window</strong> (or log out) to see it.
                                    </span>
                                @else
                                    <i class="ph ph-x-circle text-danger"></i>
                                    <span>You, viewing the site logged in as admin: <strong>no tag</strong> for the same reason as anonymous above.</span>
                                @endif
                            </div>
                        </div>

                        @if ($diag['id'] && $diag['tracking_enabled'])
                            <details class="mt-3">
                                <summary class="text-muted" style="font-size: 12px; cursor: pointer;">
                                    Show the exact snippet being injected
                                </summary>
                                <pre class="bg-dark text-light p-2 mt-2 mb-0 rounded" style="font-size: 11px; line-height: 1.4; overflow-x: auto;">&lt;script async src=&quot;https://www.googletagmanager.com/gtag/js?id={{ $diag['id'] }}&quot;&gt;&lt;/script&gt;
&lt;script&gt;
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ $diag['id'] }}');
&lt;/script&gt;</pre>
                            </details>
                        @endif
                    </div>

                    {{-- ─── Analytics + Search Console ──────────────────────── --}}
                    <h5 class="mb-3 mt-1">Analytics &amp; Search Console</h5>

                    @if (session('status_seo_general'))
                        <div class="alert alert-success mb-3">{{ session('status_seo_general') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.seo.general') }}">
                        @csrf

                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="tracking_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="tracking_enabled"
                                   name="tracking_enabled" value="1" @checked($tracking['enabled'])>
                            <label class="form-check-label" for="tracking_enabled">
                                <strong>Enable analytics tracking</strong>
                                <span class="d-block text-muted" style="font-size:12px;">
                                    Master switch. When off, no analytics scripts load on any page —
                                    even if the IDs below are filled in.
                                </span>
                            </label>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input type="hidden" name="exclude_admins" value="0">
                            <input type="checkbox" class="form-check-input" id="exclude_admins"
                                   name="exclude_admins" value="1" @checked($tracking['exclude_admins'])>
                            <label class="form-check-label" for="exclude_admins">
                                Don't track logged-in admins
                                <span class="d-block text-muted" style="font-size:12px;">
                                    Recommended on. Otherwise your own pageviews while testing will
                                    inflate metrics and pollute audience demographics in GA4.
                                </span>
                            </label>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="ga4_id">Google Tag (gtag.js) — Measurement ID</label>
                                <input type="text" class="form-control @error('ga4_id') is-invalid @enderror"
                                       id="ga4_id" name="ga4_id" value="{{ $tracking['ga4_id'] }}"
                                       placeholder="G-XXXXXXXXXX" autocomplete="off">
                                <div class="form-text">
                                    From GA4 → Admin → Data Streams → your stream → Measurement ID.
                                    Looks like <code>G-AB12CD34EF</code>.
                                    You can also paste the <strong>entire</strong>
                                    <code>&lt;!-- Google tag (gtag.js) --&gt;</code> snippet —
                                    we'll pull the ID out for you and inject the standard
                                    loader on every public page.
                                </div>
                                @error('ga4_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="gtm_id">Google Tag Manager (optional)</label>
                                <input type="text" class="form-control @error('gtm_id') is-invalid @enderror"
                                       id="gtm_id" name="gtm_id" value="{{ $tracking['gtm_id'] }}"
                                       placeholder="GTM-XXXXXXX" autocomplete="off">
                                <div class="form-text">
                                    Only needed if you're using Tag Manager to fan out to multiple
                                    analytics tools. Leave blank if you only use GA4.
                                </div>
                                @error('gtm_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-row mt-3">
                            <label class="form-label" for="gsc_verification">Search Console verification token</label>
                            <input type="text" class="form-control @error('gsc_verification') is-invalid @enderror"
                                   id="gsc_verification" name="gsc_verification" value="{{ $tracking['gsc_verification'] }}"
                                   placeholder="abcd1234..._-EFGH"
                                   autocomplete="off">
                            <div class="form-text">
                                In Search Console, choose <em>HTML tag</em> verification — it shows you
                                a snippet like <code>&lt;meta name="google-site-verification" content="<strong>this-part-only</strong>"&gt;</code>.
                                You can paste either the bare token or the full <code>&lt;meta&gt;</code>
                                tag — we'll extract the token automatically.
                            </div>
                            @error('gsc_verification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-check form-switch mt-4">
                            <input type="hidden" name="sitemap_enabled" value="0">
                            <input type="checkbox" class="form-check-input" id="sitemap_enabled"
                                   name="sitemap_enabled" value="1" @checked($tracking['sitemap_enabled'])>
                            <label class="form-check-label" for="sitemap_enabled">
                                Publish <code>/sitemap.xml</code>
                                <span class="d-block text-muted" style="font-size:12px;">
                                    Lists every published movie, series, and VJ page so Googlebot
                                    discovers new uploads quickly. Cached for 6 hours.
                                </span>
                            </label>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">Save analytics settings</button>
                            @if ($tracking['sitemap_enabled'])
                                <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                                    <i class="ph ph-arrow-square-out me-1"></i> View sitemap
                                </a>
                            @endif
                            <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                                <i class="ph ph-arrow-square-out me-1"></i> View robots.txt
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ─── Verification file upload ──────────────────────────────── --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Verification file upload</h5>
                    <p class="text-muted mb-0 mt-1" style="font-size:13px;">
                        Some Search Console / Webmaster Tools accounts prefer the
                        <em>HTML file</em> verification method instead of the meta tag above.
                        Drop the file Google (or Bing / Yandex / Pinterest / Baidu) gives you here
                        and we'll publish it at the site root where the verifier looks.
                    </p>
                </div>
                <div class="card-body">
                    @if (session('status_seo_verification'))
                        <div class="alert alert-success mb-3">{{ session('status_seo_verification') }}</div>
                    @endif

                    @if (!empty($verificationFiles))
                        <div class="mb-4">
                            <h6 class="mb-2" style="font-size: 14px;">Currently published</h6>
                            <ul class="list-group list-group-flush">
                                @foreach ($verificationFiles as $vf)
                                    <li class="list-group-item d-flex align-items-center justify-content-between bg-transparent px-0">
                                        <div>
                                            <a href="{{ $vf['url'] }}" target="_blank" rel="noopener" class="text-decoration-none">
                                                <i class="ph ph-file-text me-1"></i>
                                                <code>{{ $vf['name'] }}</code>
                                            </a>
                                            <span class="text-muted ms-2" style="font-size:12px;">{{ $vf['size'] }} bytes</span>
                                        </div>
                                        <form method="POST" action="{{ route('admin.seo.verification.delete', ['filename' => $vf['name']]) }}"
                                              onsubmit="return confirm('Remove {{ $vf['name'] }}? Search Console will lose verification through this file.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger-subtle" title="Remove">
                                                <i class="ph ph-trash-simple"></i>
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.seo.verification.upload') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-row">
                            <label class="form-label" for="verification_file">Upload verification file</label>
                            <input type="file" class="form-control @error('verification_file') is-invalid @enderror"
                                   id="verification_file" name="verification_file"
                                   accept=".html,.xml,.txt"
                                   required>
                            <div class="form-text">
                                Accepted filename shapes:
                                <code>google&hellip;.html</code>,
                                <code>BingSiteAuth.xml</code>,
                                <code>yandex_&hellip;.html</code>,
                                <code>pinterest-&hellip;.html</code>,
                                <code>baidu_verify_&hellip;.html</code>.
                                Anything else is rejected.
                            </div>
                            @error('verification_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ─── Social-share defaults ──────────────────────────────────── --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Social-share defaults</h5>
                    <p class="text-muted mb-0 mt-1" style="font-size:13px;">
                        Used when someone pastes a Jambo link into Facebook, WhatsApp, Twitter, etc.
                        Per-page values (movie poster, episode title) override these where set.
                    </p>
                </div>
                <div class="card-body">
                    @if (session('status_seo_social'))
                        <div class="alert alert-success mb-3">{{ session('status_seo_social') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.seo.social') }}">
                        @csrf

                        <div class="form-row">
                            <label class="form-label" for="og_default_image">Default share image (URL)</label>
                            <input type="text" class="form-control @error('og_default_image') is-invalid @enderror"
                                   id="og_default_image" name="og_default_image"
                                   value="{{ $social['og_default_image'] }}"
                                   placeholder="https://jambofilms.com/frontend/images/share-card.jpg">
                            <div class="form-text">
                                Recommended size: <strong>1200×630</strong>. Used when sharing the homepage
                                or a page with no movie-specific image. JPG or PNG.
                            </div>
                            @error('og_default_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-row mt-3">
                            <label class="form-label" for="og_default_description">Default share description</label>
                            <textarea class="form-control @error('og_default_description') is-invalid @enderror"
                                      id="og_default_description" name="og_default_description" rows="2"
                                      maxlength="300" placeholder="Stream the latest VJ-translated movies and series on Jambo Films.">{{ $social['og_default_description'] }}</textarea>
                            <div class="form-text">
                                Up to 300 characters. Falls back to the global meta description on the
                                Settings → General page if blank.
                            </div>
                            @error('og_default_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-row mt-3">
                            <label class="form-label" for="twitter_handle">Twitter / X handle (optional)</label>
                            <input type="text" class="form-control @error('twitter_handle') is-invalid @enderror"
                                   id="twitter_handle" name="twitter_handle"
                                   value="{{ $social['twitter_handle'] }}"
                                   placeholder="@jambofilms" maxlength="30">
                            <div class="form-text">
                                Shows up under "Site" on Twitter Card previews. The leading <code>@</code>
                                is added automatically if you leave it off.
                            </div>
                            @error('twitter_handle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">Save social defaults</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
