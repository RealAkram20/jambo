@extends('monetization::layouts.partner')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-1">Payout details</h4>
                <p class="text-muted mb-0" style="font-size:13px;">
                    Withdrawals only pay this number after an admin verifies it matches your registered name.
                    Changing it pauses withdrawals for a security cooldown.
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger mx-4 mt-3 mb-0">{{ $errors->first() }}</div>
            @endif

            <div class="card-body">
                <div class="mb-4">
                    @switch($partner->payout_status)
                        @case('verified')
                            <span class="badge bg-success">Verified</span>
                            <span class="text-muted ms-2" style="font-size:13px;">
                                since {{ optional($partner->payout_verified_at)->format('d M Y') }}
                            </span>
                            @break
                        @case('pending_review')
                            <span class="badge bg-warning">Awaiting admin verification</span>
                            @break
                        @default
                            <span class="badge bg-secondary">Not submitted</span>
                    @endswitch
                    @if ($partner->payoutLocked())
                        <span class="badge bg-danger ms-2">Withdrawals paused until {{ $partner->payout_locked_until->format('d M Y H:i') }}</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('partner.payout-profile.update') }}"
                      @if ($partner->payoutVerified())
                      onsubmit="return confirm('Changing your payout number requires re-verification and pauses withdrawals for a few days. Continue?');"
                      @endif>
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Mobile money number</label>
                        <input type="text" name="payout_msisdn" class="form-control" required
                               placeholder="07XXXXXXXX or +2567XXXXXXXX"
                               value="{{ old('payout_msisdn', $partner->payout_msisdn) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Registered name on the number</label>
                        <input type="text" name="payout_name" class="form-control" required maxlength="190"
                               placeholder="Exactly as registered with MTN / Airtel"
                               value="{{ old('payout_name', $partner->payout_name) }}">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Network</label>
                        <select name="payout_network" class="form-select">
                            <option value="mtn" @selected(old('payout_network', $partner->payout_network) === 'mtn')>MTN MoMo</option>
                            <option value="airtel" @selected(old('payout_network', $partner->payout_network) === 'airtel')>Airtel Money</option>
                        </select>
                    </div>

                    <button class="btn btn-primary w-100">
                        <i class="ph ph-identification-card me-1"></i>
                        {{ $partner->payout_status === 'none' ? 'Submit for verification' : 'Update details' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
