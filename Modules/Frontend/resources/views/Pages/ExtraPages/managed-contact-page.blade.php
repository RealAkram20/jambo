@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => $page->title])

{{-- Social-preview metadata for the Contact page. --}}
@if ($page->featured_image_url)
    @section('seo:image', $page->featured_image_url)
@endif
@if ($page->meta_description)
    @section('seo:description', $page->meta_description)
@endif

@php
    $cards = collect($page->metaValue('cards', []))->filter(fn ($c) => !empty($c['title']) || !empty($c['link_value']))->values();

    // Card link href depends on link_type: mailto: for email, tel: for
    // phone (digits only), as-is for url. Falls back to '#' so the
    // markup stays valid even with missing data.
    $cardHref = function (array $card): string {
        $type = $card['link_type'] ?? 'email';
        $value = trim((string) ($card['link_value'] ?? ''));
        if ($value === '') return '#';
        return match ($type) {
            'phone' => 'tel:' . preg_replace('/[^0-9+]/', '', $value),
            'url' => $value,
            default => 'mailto:' . $value,
        };
    };

    $addressLines = array_values(array_filter(
        preg_split('/\r?\n/', (string) $page->metaValue('address_lines', '')),
        fn ($l) => trim($l) !== '',
    ));

    $facebook = $page->metaValue('facebook_url');
    $twitter = $page->metaValue('twitter_url');
    $youtube = $page->metaValue('youtube_url');
    $mapUrl = $page->metaValue('map_embed_url');
    $mapHeight = (int) $page->metaValue('map_height', 600);
@endphp

@section('content')
    <div class="section-padding">
        <div class="container">
            @if ($intro = trim((string) $page->content))
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-10 managed-page-body">
                        {!! $intro !!}
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="title-box">
                        <h2>{{ $page->metaValue('page_heading', $page->title) }}</h2>
                    </div>
                </div>
            </div>

            @if ($cards->isNotEmpty())
                <div class="feature-card-spacing">
                    <div class="row gy-4 gy-lg-0">
                        @foreach ($cards as $card)
                            <div class="col-lg-3 col-md-6">
                                <div class="card border-color-dark feature-card card-block card-stretch card-height">
                                    <div class="card-body">
                                        @if (!empty($card['icon']))
                                            <div class="feature-card-icon">
                                                <i class="ph {{ $card['icon'] }}" style="font-size: 36px; color:#fff;"></i>
                                            </div>
                                        @endif
                                        <div class="mt-4">
                                            <h5>{{ $card['title'] ?? '' }}</h5>
                                            <div>
                                                <p class="mt-4 mb-0">{{ $card['desc'] ?? '' }}</p>
                                            </div>
                                            @if (!empty($card['link_value']))
                                                <div class="contact-decs-container">
                                                    <p class="contact-mail-title">
                                                        <a href="{{ $cardHref($card) }}">{{ $card['link_value'] }}</a>
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="section-padding-bottom contact-us-form">
        <div class="container">
            <div class="row gap-5 gap-lg-0">
                <div class="col-lg-8">
                    <div class="card contact-card rounded-2">
                        <div class="card-body">
                            <div class="mb-4 pb-2">
                                <h3 class="mb-3">{{ $page->metaValue('form_heading', 'Start the conversation') }}</h3>
                                <p class="mb-0">{{ $page->metaValue('form_subheading') }}</p>
                            </div>

                            @if (session('contact_success'))
                                <div class="alert alert-success">{{ session('contact_success') }}</div>
                            @endif
                            @if (session('contact_error'))
                                <div class="alert alert-danger">{{ session('contact_error') }}</div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('frontend.contact_us.submit') }}" class="mb-5 mb-lg-0">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <input type="text" name="first_name" class="form-control font-size-14 rounded-3"
                                               placeholder="First Name*" value="{{ old('first_name') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <input type="text" name="last_name" class="form-control font-size-14 rounded-3"
                                               placeholder="Last Name*" value="{{ old('last_name') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <input type="email" name="email" class="form-control font-size-14 rounded-3"
                                               placeholder="Email*" value="{{ old('email') }}" required>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <input type="tel" name="phone" class="form-control font-size-14 rounded-3"
                                               maxlength="140" minlength="7" placeholder="Phone Number*"
                                               value="{{ old('phone') }}" required>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <textarea name="message" class="form-control font-size-14 rounded-3" cols="40" rows="10"
                                                  placeholder="Your Message" required>{{ old('message') }}</textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <x-auth.bot-defence action="contact" />
                                    </div>
                                    <div class="col-md-12 mt-4 pt-2">
                                        <div class="iq-button">
                                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                                {{ $page->metaValue('form_button_label', 'Send Message') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-1 d-none d-lg-block"></div>

                <div class="col-lg-3">
                    @if (!empty($addressLines))
                        <div class="border-bottom pb-2 mb-3 pb-md-4 mb-md-5">
                            <h5 class="mb-3">{{ $page->metaValue('visit_us_heading', 'Visit Us') }}</h5>
                            @if ($intro = $page->metaValue('visit_us_intro'))
                                <p>{{ $intro }}</p>
                            @endif
                            <h5 class="mb-3">
                                <i class="ph-fill ph-map-pin text-primary"></i>
                                {{ $page->metaValue('address_label', 'Address') }}:
                            </h5>
                            <p>
                                @foreach ($addressLines as $i => $line)
                                    {{ $line }}@if ($i < count($addressLines) - 1)<br>@endif
                                @endforeach
                            </p>
                        </div>
                    @endif

                    @if ($page->metaValue('business_body') || $page->metaValue('business_email'))
                        <div class="border-bottom pb-2 mb-3 pb-md-4 mb-md-4 pt-2">
                            <h5>{{ $page->metaValue('business_heading', 'Business Inquiries') }}</h5>
                            @if ($body = $page->metaValue('business_body'))
                                <p>{{ $body }}</p>
                            @endif
                            @if ($email = $page->metaValue('business_email'))
                                <p><a href="mailto:{{ $email }}">{{ $email }}</a></p>
                            @endif
                        </div>
                    @endif

                    @if ($facebook || $twitter || $youtube)
                        <div class="pt-2">
                            <h5>{{ $page->metaValue('follow_label', 'Follow Us') }}</h5>
                            <ul class="p-0 m-0 mt-3 list-unstyled widget_social_media">
                                @if ($facebook)
                                    <li class="me-2">
                                        <a href="{{ $facebook }}" class="position-relative" target="_blank" rel="noopener">
                                            <i class="icon icon-facebook-share"></i>
                                        </a>
                                    </li>
                                @endif
                                @if ($twitter)
                                    <li class="me-2">
                                        <a href="{{ $twitter }}" class="position-relative" target="_blank" rel="noopener">
                                            <i class="icon icon-x-twitter-share"></i>
                                        </a>
                                    </li>
                                @endif
                                @if ($youtube)
                                    <li>
                                        <a href="{{ $youtube }}" class="position-relative" target="_blank" rel="noopener">
                                            <i class="icon icon-youtube-share"></i>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($mapUrl)
        <div class="map">
            <div class="container-fluid p-0">
                <iframe loading="lazy" class="w-100"
                        src="{{ $mapUrl }}"
                        height="{{ $mapHeight }}" allowfullscreen=""></iframe>
            </div>
        </div>
    @endif

    @include('frontend::components.widgets.mobile-footer')
@endsection
