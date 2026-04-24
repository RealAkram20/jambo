@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('frontendheader.pricing_plan')])

@php
    /**
     * Highlight rule for the "most popular" pill + active badge:
     * flag whichever monthly tier has the highest access level. Falls
     * back to the single highest-access tier so even all-yearly
     * catalogs still get a visual winner.
     */
    $tiers = $tiers ?? collect();
    $highlightedId = $tiers
        ->where('billing_period', \Modules\Subscriptions\app\Models\SubscriptionTier::PERIOD_MONTHLY)
        ->sortByDesc('access_level')
        ->first()
        ?->id
        ?? $tiers->sortByDesc('access_level')->first()?->id;

    /**
     * Query-param highlight. The device-limit picker appends
     * ?highlight=<slug> when suggesting an upgrade; we match the slug
     * here so the matching card gets the attention-pulse class on
     * first paint and is the scroll anchor for the JS at the bottom
     * of the page.
     */
    $pulseSlug = request()->query('highlight');
@endphp

@section('content')
    <div class="section-padding">
        <div class="container">
            {{-- Flash messages (error / info) are now rendered by the
                 frontend master layout, so the page body doesn't need
                 its own alert block. --}}

            <div class="row">
                @forelse ($tiers as $tier)
                    @php
                        $isHighlighted = $tier->id === $highlightedId;
                        $isPulsed = $pulseSlug && $pulseSlug === $tier->slug;
                        $features = is_array($tier->features) ? $tier->features : [];
                    @endphp
                    <div class="col-xl-4 col-md-6 mb-3 mb-lg-0">
                        <div class="pricing-plan-wrapper @if ($isPulsed) jambo-tier-pulse @endif"
                             id="tier-{{ $tier->slug }}"
                             data-tier-slug="{{ $tier->slug }}">
                            @if ($isHighlighted)
                                <div class="pricing-plan-discount bg-primary p-2 text-center">
                                    <span class="text-white">{{ __('streamTag.most_popular') ?? 'Most popular' }}</span>
                                </div>
                            @endif
                            <div class="pricing-plan-header">
                                <div class="plan-wrapper @if ($isHighlighted) d-flex align-items-center justify-content-between @endif">
                                    <h4 class="plan-name">{{ $tier->name }}</h4>
                                    @if ($isHighlighted)
                                        <span class="badge bg-primary rounded-2">
                                            <small>{{ __('streamTag.active') ?? 'Featured' }}</small>
                                        </span>
                                    @endif
                                </div>
                                <div class="pricing-plan-details">
                                    <span class="plan-main-price">{{ $tier->currency }} {{ number_format((float) $tier->price, 0) }}</span>
                                    <span class="plan-period-time">/ {{ $tier->periodLabel() }}</span>
                                </div>
                            </div>
                            <div class="pricing-details">
                                @if ($tier->description)
                                    <div class="description">
                                        <p class="m-0">{{ $tier->description }}</p>
                                    </div>
                                @endif
                                <div class="pricing-plan-description">
                                    <ul class="list-inline p-0">
                                        @forelse ($features as $feature)
                                            <li>
                                                <i class="ph ph-check fw-bold"></i>
                                                <span class="font-size-18 fw-500">{{ $feature }}</span>
                                            </li>
                                        @empty
                                            <li>
                                                <i class="ph ph-check fw-bold"></i>
                                                <span class="font-size-18 fw-500">{{ $tier->name }} access</span>
                                            </li>
                                        @endforelse
                                    </ul>
                                </div>
                                <div class="pricing-plan-footer">
                                    <div class="iq-button">
                                        @php
                                            // Free tiers have no checkout flow — CTA becomes
                                            // "Get Started" and drops users onto the homepage
                                            // so they can browse. Paid tiers POST to the
                                            // payment gateway directly.
                                            $isFree = (float) $tier->price <= 0;
                                        @endphp

                                        @if ($isFree)
                                            <a href="{{ route('frontend.ott') }}"
                                                class="btn btn-primary fw-semibold rounded-3 w-100">
                                                {{ __('streamShop.get_started') }}
                                            </a>
                                        @elseif (!auth()->check())
                                            {{-- Guests: send them to login with an `intended`
                                                 that bounces back to /pricing so they land on
                                                 the same tier after signing in. --}}
                                            <a href="{{ route('login') }}?redirect={{ urlencode(route('frontend.pricing-page')) }}"
                                                class="btn btn-primary fw-semibold rounded-3 w-100">
                                                {{ __('streamShop.checkout') }}
                                            </a>
                                        @else
                                            {{-- Tier metadata on data-* attrs so the modal header
                                                 can show "Complete your payment — Premium Monthly
                                                 (UGX 30,000)" without a second server request. --}}
                                            <form method="POST" action="{{ route('payment.create-order') }}"
                                                  class="jambo-subscribe-form m-0"
                                                  data-tier-name="{{ $tier->name }}"
                                                  data-tier-price="{{ $tier->currency }} {{ number_format((float) $tier->price, 0) }} / {{ $tier->periodLabel() }}">
                                                @csrf
                                                <input type="hidden" name="subscription_tier_id" value="{{ $tier->id }}">
                                                <button type="submit" class="btn btn-primary fw-semibold rounded-3 w-100 jambo-subscribe-btn">
                                                    <span class="label">{{ __('streamShop.checkout') }}</span>
                                                    <span class="spinner spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">
                        No subscription plans are active right now. Please check back soon.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Mobile Footer --}}
    @include('frontend::components.widgets.mobile-footer')
    {{-- Mobile Footer End --}}

    {{-- Iframe checkout modal. Intercepts every Subscribe form on this
         page, POSTs to /payment/create-order via fetch, and hosts
         PesaPal's redirect_url inside a dark-themed dialog with X-only
         dismissal. See the partial for the full UX rules. --}}
    @auth
        @include('frontend::components.partials.payment-modal')
    @endauth

    @if ($pulseSlug)
        {{-- Attention pulse + auto-scroll for ?highlight=<slug>. Only
             emits when the query param is present so regular pricing
             visits are unchanged. Three short pulses then settles —
             enough to catch the eye without being a loop animation.
             Honors prefers-reduced-motion by disabling the keyframe. --}}
        <style>
            @keyframes jambo-tier-pulse-kf {
                0%   { box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0.55); }
                70%  { box-shadow: 0 0 0 16px rgba(var(--bs-primary-rgb), 0); }
                100% { box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0); }
            }

            .jambo-tier-pulse {
                border-radius: 12px;
                animation: jambo-tier-pulse-kf 1.4s ease-out 3;
            }

            @media (prefers-reduced-motion: reduce) {
                .jambo-tier-pulse { animation: none; }
            }
        </style>

        <script>
            (function () {
                var target = document.getElementById('tier-{{ $pulseSlug }}');
                if (!target) return;
                // Defer one frame so layout settles before we measure.
                requestAnimationFrame(function () {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            })();
        </script>
    @endif
@endsection
