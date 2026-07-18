@extends('profile-hub._layout', ['pageTitle' => 'Refer & Earn', 'user' => $user, 'activeTab' => $activeTab])

@section('hub-content')
    @php
        // Strip decimal zeros only ("10.50" → "10.5") — a plain "10" has
        // no fraction to trim and must not lose its integer zero.
        $fmtPercent = fn ($v) => str_contains((string) $v, '.') ? rtrim(rtrim((string) $v, '0'), '.') : (string) $v;
        $activePane = $errors->any() ? 'refer' : request()->query('tab', 'refer');
    @endphp

    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activePane === 'refer' ? 'active' : '' }}" data-bs-toggle="tab"
                    data-bs-target="#refer-pane" type="button" role="tab">Refer friends</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activePane === 'earnings' ? 'active' : '' }}" data-bs-toggle="tab"
                    data-bs-target="#earnings-pane" type="button" role="tab">My earnings</button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- ============================ Refer friends ============================ --}}
        <div class="tab-pane fade {{ $activePane === 'refer' ? 'show active' : '' }}" id="refer-pane" role="tabpanel">
            <div class="jambo-hub-card text-center">
                <i class="ph-fill ph-gift" style="font-size: 2.5rem; color: var(--bs-primary);"></i>
                <h5 class="mt-2">Invite friends — they save {{ $fmtPercent($discountPercent) }}%, you earn {{ $fmtPercent($rewardPercent) }}%</h5>
                <p class="jambo-hub-card__subtitle">
                    Share your link. When a friend subscribes, they get {{ $fmtPercent($discountPercent) }}% off their first
                    payment and you earn {{ $fmtPercent($rewardPercent) }}% of what they pay.
                </p>

                <div class="d-flex justify-content-center">
                    <div class="input-group" style="max-width: 460px;">
                        <input type="text" class="form-control" id="jambo-ref-link" value="{{ $link }}" readonly>
                        <button type="button" class="btn btn-primary" id="jambo-ref-copy">
                            <i class="ph ph-copy me-1"></i>Copy link
                        </button>
                    </div>
                </div>
                <div class="small text-success mt-2 d-none" id="jambo-ref-copied">Link copied</div>
            </div>

            <div class="jambo-hub-card">
                <h5>Your code</h5>
                <p class="jambo-hub-card__subtitle">Friends can also enter this code at checkout.</p>

                <form method="POST" action="{{ route('profile.refer.code', ['username' => $user->username]) }}">
                    @csrf @method('PUT')
                    <div class="d-flex gap-2" style="max-width: 460px;">
                        <input type="text" name="referral_code" maxlength="50" id="jambo-ref-code-input"
                               class="form-control @error('referral_code') is-invalid @enderror"
                               value="{{ old('referral_code', $code) }}" autocomplete="off"
                               data-check-url="{{ route('referrals.check-code') }}"
                               data-current-code="{{ $code }}">
                        <button type="submit" class="btn btn-outline-primary flex-shrink-0" id="jambo-ref-code-save">Save code</button>
                    </div>
                    <div class="small mt-1 d-none" id="jambo-ref-code-status"></div>
                    @error('referral_code')
                        <div class="small text-danger mt-1">{{ $message }}</div>
                    @enderror
                </form>
            </div>
        </div>

        {{-- ============================ My earnings ============================ --}}
        <div class="tab-pane fade {{ $activePane === 'earnings' ? 'show active' : '' }}" id="earnings-pane" role="tabpanel">
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="jambo-hub-card mb-0 text-center py-3">
                        <div class="fs-5 fw-bold">{{ $currency }} {{ number_format((float) $balance, 0) }}</div>
                        <div class="small text-muted">Balance</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="jambo-hub-card mb-0 text-center py-3">
                        <div class="fs-5 fw-bold">{{ $currency }} {{ number_format((float) bcadd((string) $totalEarned, (string) $partnerEarned, 2), 0) }}</div>
                        <div class="small text-muted">Total earned</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="jambo-hub-card mb-0 text-center py-3">
                        <div class="fs-5 fw-bold">{{ $totalReferrals }}</div>
                        <div class="small text-muted">Referrals</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="jambo-hub-card mb-0 text-center py-3">
                        <div class="fs-5 fw-bold">{{ $qualifiedCount }}</div>
                        <div class="small text-muted">Subscribed</div>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($isPartner)
                <div class="jambo-hub-card d-flex align-items-center justify-content-between gap-2">
                    <span class="small">New referral rewards are credited to your Creator Studio wallet.</span>
                    @if (Route::has('partner.wallet'))
                        <a class="btn btn-sm btn-outline-primary flex-shrink-0" href="{{ route('partner.wallet') }}">Open wallet</a>
                    @endif
                </div>
            @endif

            {{-- Money actions live on the Wallet tab. --}}
            <div class="jambo-hub-card d-flex align-items-center justify-content-between gap-2">
                <span class="small">Withdraw or spend your balance from your wallet.</span>
                <a class="btn btn-sm btn-outline-primary flex-shrink-0"
                   href="{{ route('profile.wallet', ['username' => $user->username]) }}">
                    <i class="ph ph-wallet me-1"></i>Open Wallet
                </a>
            </div>

            <div class="jambo-hub-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Your referrals</h5>
                </div>

                @if ($referrals->count())
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover align-middle small mb-0">
                            <thead class="text-muted">
                                <tr>
                                    <th>Friend</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th class="text-end">Earned</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($referrals as $referral)
                                    <tr>
                                        <td>
                                            {{ $referral->masked_name }}
                                            <span class="text-muted d-block">{{ $referral->masked_email }}</span>
                                        </td>
                                        <td>
                                            @if ($referral->status === \Modules\Referrals\app\Models\Referral::STATUS_QUALIFIED)
                                                <span class="badge bg-success">Subscribed</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $referral->created_at?->format('M j, Y') }}</td>
                                        <td class="text-end">
                                            @if ($referral->reward_amount !== null)
                                                {{ $referral->currency ?? $currency }} {{ number_format((float) $referral->reward_amount, 0) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $referrals->appends(['tab' => 'earnings'])->links() }}
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="ph ph-gift fs-1 text-muted"></i>
                        <p class="text-muted mb-0 mt-2">No referrals yet — share your link to start earning.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Live availability while typing a custom code: debounced
            // check against /referrals/check-code, save disabled while
            // the current value is known-taken.
            var input = document.getElementById('jambo-ref-code-input');
            var save = document.getElementById('jambo-ref-code-save');
            var status = document.getElementById('jambo-ref-code-status');
            if (!input || !save || !status) return;

            var timer = null;
            var csrf = '{{ csrf_token() }}';

            function setStatus(text, ok) {
                status.textContent = text;
                status.classList.remove('d-none', 'text-danger', 'text-success', 'text-muted');
                status.classList.add(ok === null ? 'text-muted' : (ok ? 'text-success' : 'text-danger'));
            }

            input.addEventListener('input', function () {
                clearTimeout(timer);
                var code = input.value.trim();

                if (code === '' || code === input.dataset.currentCode) {
                    status.classList.add('d-none');
                    save.disabled = false;
                    return;
                }

                setStatus('{{ __('Checking…') }}', null);
                save.disabled = true;

                timer = setTimeout(function () {
                    fetch(input.dataset.checkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ code: code }),
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            if (code !== input.value.trim()) return; // stale response
                            setStatus(data.message || '', !!data.available);
                            save.disabled = !data.available;
                        })
                        .catch(function () {
                            // Network hiccup: don't block saving — the
                            // server re-validates on submit anyway.
                            status.classList.add('d-none');
                            save.disabled = false;
                        });
                }, 350);
            });
        })();

        (function () {
            var btn = document.getElementById('jambo-ref-copy');
            var input = document.getElementById('jambo-ref-link');
            var copied = document.getElementById('jambo-ref-copied');
            if (!btn || !input) return;

            btn.addEventListener('click', function () {
                var done = function () {
                    copied.classList.remove('d-none');
                    setTimeout(function () { copied.classList.add('d-none'); }, 2000);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value).then(done);
                } else {
                    input.select();
                    document.execCommand('copy');
                    done();
                }
            });
        })();
    </script>
@endsection
