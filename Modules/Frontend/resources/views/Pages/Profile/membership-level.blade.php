@extends('frontend::layouts.master', ['isBreadCrumb' => true, 'title' => __('profile.membership_levels') ?? 'Plans'])

@section('content')
    <div class="section-padding">
        <div class="pmpro container">
            <section id="pmpro_levels" class="pmpro_section">
                <div class="pmpro_section_content">
                    <div class="pmpro_card pmpro_level_group">
                        <div class="pmpro_card_content">
                            @if ($tiers->count())
                                <table class="pmpro_table pmpro_levels_table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('streamTag.level') ?? 'Plan' }}</th>
                                            <th>{{ __('streamMovies.Price') ?? 'Price' }}</th>
                                            <th><span class="screen-reader-text">{{ __('streamTag.action') ?? 'Action' }}</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tiers as $tier)
                                            @php $isCurrent = $currentTierId === $tier->id; @endphp
                                            <tr class="pmpro_level {{ $isCurrent ? 'pmpro_level-current' : '' }}">
                                                <th data-title="Level">
                                                    {{ $tier->name }}
                                                    @if ($isCurrent)
                                                        <span class="pmpro_tag pmpro_tag-success ms-2">Current</span>
                                                    @endif
                                                    @if ($tier->description)
                                                        <div class="text-muted small fw-normal mt-1">{{ $tier->description }}</div>
                                                    @endif
                                                </th>
                                                <td data-title="Price">
                                                    <p class="pmpro_level-price mb-0">
                                                        <strong>{{ $tier->currency ?: 'USD' }} {{ number_format($tier->price, 2) }}</strong>
                                                        @if ($tier->billing_period)
                                                            / {{ $tier->billing_period }}
                                                        @endif
                                                    </p>
                                                </td>
                                                <td>
                                                    @if ($isCurrent)
                                                        <button class="pmpro_btn pmpro_btn-select w-100" disabled>
                                                            {{ __('form.select') ?? 'Select' }}
                                                        </button>
                                                    @else
                                                        <a class="pmpro_btn pmpro_btn-select w-100"
                                                           href="{{ route('frontend.pricing-page') }}?tier={{ $tier->slug }}"
                                                           aria-label="Select the {{ $tier->name }} plan">
                                                            {{ __('form.select') ?? 'Select' }}
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted mb-0">No plans are available right now.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            @if ($currentSub)
                <div class="pmpro_actions_nav mt-4">
                    <span class="pmpro_actions_nav-left">
                        <a href="{{ route('frontend.membership-account') }}">← Back to My Account</a>
                    </span>
                </div>
            @endif
        </div>
    </div>

    @include('frontend::components.widgets.mobile-footer')
@endsection
