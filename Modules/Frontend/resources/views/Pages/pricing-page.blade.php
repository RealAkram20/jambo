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
@endphp

@section('content')
    <div class="section-padding">
        <div class="container">
            <div class="row">
                @forelse ($tiers as $tier)
                    @php
                        $isHighlighted = $tier->id === $highlightedId;
                        $features = is_array($tier->features) ? $tier->features : [];
                    @endphp
                    <div class="col-xl-4 col-md-6 mb-3 mb-lg-0">
                        <div class="pricing-plan-wrapper">
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
                                        <a href="{{ route('frontend.membership-level') }}?tier={{ $tier->slug }}"
                                            class="btn btn-primary fw-semibold rounded-3">
                                            {{ __('streamShop.checkout') ?? 'Get started' }}
                                        </a>
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
@endsection
