@include('profile-hub._layout', ['pageTitle' => 'Membership', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    {{-- Current plan summary --}}
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Current plan</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    What you're on right now.
                </p>
            </div>
            <i class="ph ph-crown fs-2 text-muted"></i>
        </div>

        @if ($activeSub)
            <div class="d-flex flex-wrap align-items-center gap-3">
                <h4 class="mb-0">{{ $activeSub->tier->name ?? '—' }}</h4>
                <span class="badge bg-success">
                    {{ ucfirst($activeSub->status ?? 'active') }}
                </span>
                @if ($activeSub->auto_renew)
                    <span class="badge bg-primary">Auto-renew on</span>
                @endif
            </div>
            <div class="mt-3 d-flex flex-wrap gap-4 small text-muted">
                <div><strong class="text-white">Started:</strong> {{ $activeSub->starts_at?->format('F j, Y') ?? '—' }}</div>
                <div><strong class="text-white">Renews:</strong> {{ $activeSub->ends_at?->format('F j, Y') ?? '—' }}</div>
            </div>
        @else
            <p class="text-muted mb-2">You don't have an active membership.</p>
            <a href="{{ route('frontend.pricing-page') }}" class="btn btn-primary btn-sm">
                Browse plans
            </a>
        @endif
    </div>

    {{-- All plans --}}
    <div class="jambo-hub-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">All plans</h5>
                <p class="jambo-hub-card__subtitle mb-0">
                    Upgrade or change anytime from the pricing page.
                </p>
            </div>
        </div>

        @if ($tiers->count())
            <div class="row g-3">
                @foreach ($tiers as $tier)
                    @php $isCurrent = $currentTierId === $tier->id; @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 rounded-3 h-100 d-flex flex-column {{ $isCurrent ? 'border border-primary border-opacity-50' : 'border border-dark' }}"
                             style="background: rgba(255,255,255,0.02);">
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <h6 class="mb-0">{{ $tier->name }}</h6>
                                @if ($isCurrent)
                                    <span class="badge bg-success small">Current</span>
                                @endif
                            </div>
                            @if ($tier->description)
                                <p class="small text-muted mb-3">{{ $tier->description }}</p>
                            @endif
                            <div class="mt-auto">
                                <div class="fw-bold mb-2">
                                    {{ $tier->currency ?: 'USD' }} {{ number_format($tier->price, 2) }}
                                    @if ($tier->billing_period)
                                        <small class="text-muted">/ {{ $tier->billing_period }}</small>
                                    @endif
                                </div>
                                @if (!$isCurrent)
                                    <a href="{{ route('frontend.pricing-page') }}?tier={{ $tier->slug }}"
                                       class="btn btn-outline-primary btn-sm w-100">Select</a>
                                @else
                                    <button class="btn btn-secondary btn-sm w-100" disabled>Current</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted mb-0">No plans available right now.</p>
        @endif
    </div>
@endsection
