@php
    // $footerPage is shared by Modules\Pages\app\Providers\PagesServiceProvider
    // — it's the single Pages row admins edit at /admin/pages → Footer.
    $contact   = $footerPage?->metaValue('contact', []) ?? [];
    $columns   = $footerPage?->metaValue('columns', []) ?? [];
    $newsletter = $footerPage?->metaValue('newsletter', ['enabled' => true]) ?? ['enabled' => true];
    $socials   = $footerPage?->metaValue('socials', []) ?? [];
    $copyright = $footerPage?->metaValue('copyright');
    $playStore = $footerPage?->metaValue('play_store_url');
    $appStore  = $footerPage?->metaValue('app_store_url');
@endphp

<footer class="footer footer-default">
    <div class="footer-top">
        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-lg-3 col-sm-6">
                    <div class="footer-logo">
                        @include('frontend::components.brand.logo')
                    </div>
                    @php
                        // Append a trailing ":" to the saved labels if
                        // the admin didn't include one. Keeps the
                        // address / phone visually attached to its
                        // label without forcing every operator to
                        // remember to type the colon themselves.
                        $emailLabel    = trim($contact['email_label']    ?? '');
                        $helplineLabel = trim($contact['helpline_label'] ?? '');
                        if ($emailLabel !== ''    && !str_ends_with($emailLabel, ':'))    $emailLabel    .= ':';
                        if ($helplineLabel !== '' && !str_ends_with($helplineLabel, ':')) $helplineLabel .= ':';
                    @endphp
                    @if ($emailLabel !== '' || !empty($contact['email_address']))
                        <div class="mb-3">
                            {{ $emailLabel }}
                            @if (!empty($contact['email_address']))
                                <span class="text-white">{{ $contact['email_address'] }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($helplineLabel !== '')
                        <p class="mt-0 mb-2">{{ $helplineLabel }}</p>
                    @endif
                    @if (!empty($contact['helpline_phone']))
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['helpline_phone']) }}" class="helpline">{{ $contact['helpline_phone'] }}</a>
                    @endif
                </div>

                @foreach ($columns as $col)
                    @php
                        $links = collect($col['links'] ?? [])->filter(fn ($l) => !empty($l['label']) || !empty($l['url']))->values();
                    @endphp
                    @if (!empty($col['title']) || $links->isNotEmpty())
                        <div class="col-lg-2 col-sm-6">
                            @if (!empty($col['title']))
                                <h4 class="footer-link-title text-capitalize">{{ $col['title'] }}</h4>
                            @endif
                            @if ($links->isNotEmpty())
                                <ul class="list-unstyled footer-menu mb-0">
                                    @foreach ($links as $link)
                                        <li>
                                            <a href="{{ $link['url'] ?? '#' }}" class="text-capitalize">{{ $link['label'] ?? '' }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                @endforeach

                <div class="col-lg-3 col-sm-6">
                    @if (!empty($newsletter['enabled']))
                        @if (!empty($newsletter['title']))
                            <h4 class="footer-link-title text-capitalize">{{ $newsletter['title'] }}</h4>
                        @endif
                        <div class="mailchimp mailchimp-dark">
                            <div class="input-group">
                                <input type="text" class="form-control mb-0"
                                       placeholder="{{ $newsletter['placeholder'] ?? 'Email' }}"
                                       aria-describedby="button-addon2" />
                                <div class="iq-button">
                                    <button type="submit" class="btn btn-primary st-subscribe-btn"
                                            id="button-addon2">{{ $newsletter['button_label'] ?? 'Subscribe' }}</button>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (!empty($socials))
                        <div class="d-flex align-items-center gap-3 widget-streamit-social-media {{ !empty($newsletter['enabled']) ? 'mt-4' : '' }}">
                            <h3 class="font-size-14 widget-streamit-social-media-title">
                                {{ $footerPage?->metaValue('follow_label', 'Follow Us') }}
                            </h3>
                            <div class="social-footer">
                                <ul class="m-0 d-inline list-unstyled widget_social_media d-flex gap-2 flex-wrap">
                                    @foreach ($socials as $social)
                                        @if (!empty($social['url']))
                                            <li>
                                                <a href="{{ $social['url'] }}" class="position-relative" target="_blank" rel="noopener">
                                                    <i class="{{ $social['icon'] ?? '' }}"></i>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="font-size-14 mb-0">
                        @if ($copyright)
                            {!! $copyright !!}
                        @else
                            &copy; <span class="currentYear">{{ date('Y') }}</span> <span class="text-primary">JAMBO.</span>
                        @endif
                    </p>
                </div>

                @if ($playStore || $appStore)
                    <div class="col-md-6 mt-md-0 mt-5">
                        <div class="d-flex flex-column align-items-start align-items-md-end widget-iq-download-app">
                            @if ($title = $footerPage?->metaValue('download_app_title', 'Download App'))
                                <h6 class="mb-3 fw-bold">{{ $title }}</h6>
                            @endif
                            <div>
                                <ul class="d-inline-flex flex-wrap align-items-center list-inline m-0 p-0 gap-3">
                                    @if ($playStore)
                                        <li class="m-0 p-0">
                                            <a class="app-image" href="{{ $playStore }}" target="_blank" rel="noopener">
                                                <img src="{{ asset('frontend/images/footer/play-store.webp') }}" loading="lazy" alt="Play Store" />
                                            </a>
                                        </li>
                                    @endif
                                    @if ($appStore)
                                        <li class="m-0 p-0">
                                            <a class="app-image" href="{{ $appStore }}" target="_blank" rel="noopener">
                                                <img src="{{ asset('frontend/images/footer/app-store.webp') }}" loading="lazy" alt="App Store" />
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</footer>
