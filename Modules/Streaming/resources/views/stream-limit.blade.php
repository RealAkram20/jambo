@extends('frontend::layouts.master', ['isBreadCrumb' => false, 'title' => 'Too many devices'])

@php
    use App\Support\UserAgent;
    use Carbon\Carbon;
@endphp

@section('content')
<section class="section-padding">
    <div class="container" style="max-width: 760px;">

        {{-- Header: icon + headline + current usage. --}}
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center mb-3"
                 style="width:72px; height:72px; border-radius:50%; background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning);">
                <i class="ph ph-devices" style="font-size:2.25rem;"></i>
            </div>
            <h2 class="mb-2">Too many devices on your account</h2>
            <p class="text-muted mb-0">
                Your <strong>{{ $tier?->name ?? 'current' }}</strong> plan allows up to
                <strong id="jambo-cap">{{ $cap }}</strong>
                {{ \Illuminate\Support\Str::plural('device', $cap) }} signed in at once.
                Disconnect one below to keep using this device, or upgrade for more.
            </p>
        </div>

        {{-- Device list. One row per distinct browser session for this
             user (pulled from Laravel's sessions table, not active_streams
             — the cap is now account-level, so dormant tabs count too).
             Each row can be disconnected; clicking on the current device
             signs THIS browser out and routes to /login. --}}
        <div class="card bg-dark border-secondary-subtle">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0">Signed-in devices</h6>
                <span class="badge bg-warning text-dark" id="jambo-active-count">
                    {{ $sessions->count() }} / {{ $cap ?? '∞' }}
                </span>
            </div>

            <ul class="list-group list-group-flush" id="jambo-stream-list">
                @forelse ($sessions as $session)
                    @php
                        $ua = UserAgent::parse($session->user_agent ?? '');
                        $lastActive = Carbon::createFromTimestamp((int) $session->last_activity);
                        $watchable = $session->watching?->watchable;
                        $watchingLabel = $watchable
                            ? (class_basename($session->watching->watchable_type) . ': ' . ($watchable->title ?? '—'))
                            : null;
                    @endphp
                    <li class="list-group-item jambo-stream-row d-flex align-items-center gap-3"
                        data-session-id="{{ $session->id }}"
                        style="background: transparent;">
                        <i class="ph {{ $ua['icon'] }}" style="font-size: 1.75rem; color: var(--bs-primary);"></i>

                        <div class="flex-grow-1" style="min-width: 0;">
                            <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
                                <span>{{ $ua['browser'] }} on {{ $ua['os'] }}</span>
                                @if ($session->is_current)
                                    <span class="badge bg-primary" style="font-size: 0.7rem;">This device</span>
                                @endif
                            </div>
                            @if ($watchingLabel)
                                <div class="text-muted small text-truncate">
                                    <i class="ph ph-play me-1"></i>Watching: {{ $watchingLabel }}
                                </div>
                            @endif
                            <div class="text-muted small">
                                @if ($session->ip_address)
                                    {{ $session->ip_address }} ·
                                @endif
                                last active {{ $lastActive->diffForHumans() }}
                            </div>
                        </div>

                        <button type="button"
                                class="btn btn-outline-danger btn-sm jambo-boot-btn"
                                data-boot
                                data-session-id="{{ $session->id }}"
                                data-is-current="{{ $session->is_current ? '1' : '0' }}">
                            <i class="ph ph-sign-out me-1"></i>
                            {{ $session->is_current ? 'Sign out here' : 'Disconnect' }}
                        </button>
                    </li>
                @empty
                    {{-- Pathological case: user was redirected here but their
                         sessions have already expired. Give them a way out. --}}
                    <li class="list-group-item text-center text-muted py-4" style="background: transparent;">
                        No other devices are currently signed in — click Continue below.
                    </li>
                @endforelse
            </ul>
        </div>

        @php
            // Bake the "return to" URL straight into the button's href
            // (see previous fix — avoids the redirect()->intended()
            // session-cookie timing trap).
            $continueHref = $intendedUrl ?: route('streams.continue');
        @endphp
        <div class="d-flex gap-2 mt-4 flex-wrap justify-content-center">
            <a href="{{ $continueHref }}"
               class="btn btn-primary {{ $sessions->count() > $cap ? 'd-none' : '' }}"
               id="jambo-continue-btn">
                <i class="ph ph-play me-1"></i>
                Continue
            </a>

            @if ($nextTier)
                <a href="{{ route('frontend.pricing-page', ['highlight' => $nextTier->slug]) }}"
                   class="btn btn-warning">
                    <i class="ph ph-crown me-1"></i>
                    Upgrade to {{ $nextTier->name }}
                    ({{ $nextTier->max_concurrent_streams ?? 'Unlimited' }} {{ \Illuminate\Support\Str::plural('device', $nextTier->max_concurrent_streams ?? 2) }})
                    — {{ $nextTier->currency }} {{ number_format($nextTier->price, 0) }} {{ $nextTier->periodLabel() }}
                </a>
            @else
                <a href="{{ route('frontend.pricing-page') }}" class="btn btn-outline-warning">
                    <i class="ph ph-crown me-1"></i>
                    View plans
                </a>
            @endif
        </div>

        <p class="text-center text-muted small mt-4">
            Your Jambo plan caps how many devices can be signed in at the same time. Disconnected devices are signed out and will need to log in again to come back.
        </p>

    </div>
</section>

{{-- Inline disconnect handler. On success, removes the row visually
     and (for a self-boot) redirects to /login since the current
     session is invalidated on the server side. --}}
<script>
(function () {
    var list = document.getElementById('jambo-stream-list');
    if (!list) return;

    var continueBtn = document.getElementById('jambo-continue-btn');
    var countBadge = document.getElementById('jambo-active-count');
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var cap = {{ $cap ?? 'null' }};

    function activeCount() {
        return list.querySelectorAll('.jambo-stream-row:not(.jambo-stream-row--booted)').length;
    }

    function refreshCount() {
        if (countBadge) {
            countBadge.textContent = activeCount() + ' / ' + (cap === null ? '∞' : cap);
        }
        // Reveal Continue once count is at/under the cap.
        if (continueBtn && (cap === null || activeCount() <= cap)) {
            continueBtn.classList.remove('d-none');
        }
    }

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-boot]');
        if (!btn) return;
        e.preventDefault();

        var row = btn.closest('.jambo-stream-row');
        var sessionId = btn.dataset.sessionId;
        var isCurrent = btn.dataset.isCurrent === '1';

        if (!sessionId || btn.disabled) return;

        if (isCurrent && !confirm('Sign out of this device? You will need to log in again to come back.')) {
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' +
            (isCurrent ? 'Signing out' : 'Disconnecting');

        fetch("{{ url('/streams/boot') }}/" + encodeURIComponent(sessionId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
        .then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); })
        .then(function (r) {
            if (!r.ok || !r.body.ok) {
                throw new Error(r.body.error || 'Request failed');
            }
            // Self-boot: current session is invalid — ride the
            // server-provided login_url rather than stay on a page
            // whose CSRF is now dead.
            if (r.body.self) {
                window.location.href = r.body.login_url || "{{ route('login') }}";
                return;
            }
            row.classList.add('jambo-stream-row--booted');
            row.style.opacity = '0.45';
            btn.innerHTML = '<i class="ph ph-check me-1"></i>Disconnected';
            btn.classList.replace('btn-outline-danger', 'btn-outline-secondary');
            refreshCount();
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-sign-out me-1"></i>' + (isCurrent ? 'Sign out here' : 'Disconnect');
            alert("Couldn't disconnect that device: " + (err.message || 'unknown error'));
        });
    });

    refreshCount();
})();
</script>
@endsection
