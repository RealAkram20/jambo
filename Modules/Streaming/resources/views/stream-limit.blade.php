@extends('frontend::layouts.master', ['isBreadCrumb' => false, 'title' => 'Too many streams'])

@section('content')
<section class="section-padding">
    <div class="container" style="max-width: 640px;">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center mb-3"
                 style="width:72px; height:72px; border-radius:50%; background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning);">
                <i class="ph ph-television-simple" style="font-size:2.25rem;"></i>
            </div>
            <h2 class="mb-2">You're streaming on too many devices</h2>
            <p class="text-muted mb-0">
                Your {{ $tier?->name ?? 'current' }} plan lets you watch premium content on up to
                <strong>{{ $cap }}</strong> {{ \Illuminate\Support\Str::plural('device', $cap) }} at the same time.
                {{ $others }} other {{ \Illuminate\Support\Str::plural('device', $others) }}
                {{ $others === 1 ? 'is' : 'are' }} already playing.
            </p>
        </div>

        <div class="card bg-dark border-secondary-subtle">
            <div class="card-body">
                <h6 class="mb-3">What you can do</h6>
                <ol class="mb-4 ps-3" style="font-size: 0.95rem; line-height: 1.7;">
                    <li>
                        <strong>Stop the other streams.</strong>
                        Close the player on one of your other devices — once its heartbeat stops
                        you'll be able to start playing here within a minute or two.
                    </li>
                    <li>
                        <strong>Sign out those devices.</strong>
                        Go to
                        <a href="{{ route('profile.devices', ['username' => auth()->user()->username]) }}">
                            your Devices tab
                        </a>
                        and remove the sessions you don't recognise.
                    </li>
                    <li>
                        <strong>Upgrade your plan.</strong>
                        Higher tiers support more simultaneous streams.
                        <a href="{{ route('profile.membership', ['username' => auth()->user()->username]) }}">
                            See plans →
                        </a>
                    </li>
                </ol>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('profile.devices', ['username' => auth()->user()->username]) }}"
                       class="btn btn-primary">
                        <i class="ph ph-devices me-1"></i> Manage devices
                    </a>
                    <a href="{{ route('profile.membership', ['username' => auth()->user()->username]) }}"
                       class="btn btn-outline-primary">
                        <i class="ph ph-crown me-1"></i> Upgrade plan
                    </a>
                    <a href="{{ url()->previous() }}" class="btn btn-ghost">
                        <i class="ph ph-arrow-left me-1"></i> Go back
                    </a>
                </div>
            </div>
        </div>

        <p class="text-center text-muted small mt-4">
            Note: free and ad-supported content never counts against your stream limit.
        </p>
    </div>
</section>
@endsection
