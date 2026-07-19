@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Monetization Settings</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Pool math inputs, qualification rules and withdrawal policy. Every change is audit-logged.
                    </p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ $errors->first() }}</div>
                @endif

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.monetization.settings.update') }}">
                        @csrf @method('PUT')

                        <h6 class="text-uppercase text-muted mb-3" style="font-size:11px;letter-spacing:.5px;">Program</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Program status</label>
                                <select name="active" class="form-select">
                                    <option value="0" @selected(old('active', $values['active']) === '0')>Inactive — no accrual</option>
                                    <option value="1" @selected(old('active', $values['active']) === '1')>Active — accruing watch-time</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Activation date</label>
                                <input type="date" name="activated_at" class="form-control"
                                       value="{{ old('activated_at', $values['activated_at']) }}">
                                <small class="text-muted">Accrual epoch — watch-time before this date never counts. Set once.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Finance admins can view</label>
                                <select name="finance_can_view" class="form-select">
                                    <option value="1" @selected(old('finance_can_view', $values['finance_can_view']) === '1')>Yes — finance + super-admin</option>
                                    <option value="0" @selected(old('finance_can_view', $values['finance_can_view']) === '0')>No — super-admin only</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="text-uppercase text-muted mb-3" style="font-size:11px;letter-spacing:.5px;">Monthly pool</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Pool % of net revenue</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" max="100" name="pool_percent" class="form-control"
                                           value="{{ old('pool_percent', $values['pool_percent']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gateway fee estimate</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" max="100" name="gateway_fee_percent" class="form-control"
                                           value="{{ old('gateway_fee_percent', $values['gateway_fee_percent']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">PesaPal doesn't report fees per transaction — this deducts an estimate.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Infra cost / month</label>
                                <div class="input-group">
                                    <span class="input-group-text">UGX</span>
                                    <input type="number" step="1" min="0" name="infra_cost_monthly" class="form-control"
                                           value="{{ old('infra_cost_monthly', $values['infra_cost_monthly']) }}">
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 mb-4" style="font-size:13px;">
                            Pool = (subscription revenue − fee% − infra) × pool%. Partner payouts can never exceed the pool.
                        </div>

                        <h6 class="text-uppercase text-muted mb-3" style="font-size:11px;letter-spacing:.5px;">Qualification</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Completion threshold</label>
                                <div class="input-group">
                                    <input type="number" step="1" min="1" max="100" name="qualify_threshold_percent" class="form-control"
                                           value="{{ old('qualify_threshold_percent', $values['qualify_threshold_percent']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">A paid user must watch this share of a title to credit it.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Free titles earn</label>
                                <select name="free_content_earns" class="form-select">
                                    <option value="1" @selected(old('free_content_earns', $values['free_content_earns']) === '1')>Yes — whole catalogue</option>
                                    <option value="0" @selected(old('free_content_earns', $values['free_content_earns']) === '0')>No — premium titles only</option>
                                </select>
                                <small class="text-muted">Whether watch-time on titles with no tier requirement pays partners.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Daily minutes cap / user</label>
                                <input type="number" step="1" min="60" max="1440" name="daily_minutes_cap" class="form-control"
                                       value="{{ old('daily_minutes_cap', $values['daily_minutes_cap']) }}">
                                <small class="text-muted">Anti-farming: max payable minutes one account can credit per day.</small>
                            </div>
                        </div>
                        <div class="alert alert-warning py-2 mb-4" style="font-size:13px;">
                            <i class="ph ph-shield-check me-1"></i>
                            While free titles earn, they also count against each viewer's concurrent-device limit —
                            otherwise one paid account could farm minutes from unlimited free tabs. Free viewers with
                            no paid subscription never earn partners anything either way.
                        </div>

                        <h6 class="text-uppercase text-muted mb-3" style="font-size:11px;letter-spacing:.5px;">Withdrawals</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Minimum withdrawal</label>
                                <div class="input-group">
                                    <span class="input-group-text">UGX</span>
                                    <input type="number" step="1" min="0" name="min_withdrawal" class="form-control"
                                           value="{{ old('min_withdrawal', $values['min_withdrawal']) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payout-change cooldown</label>
                                <div class="input-group">
                                    <input type="number" step="1" min="0" max="90" name="payout_change_cooldown_days" class="form-control"
                                           value="{{ old('payout_change_cooldown_days', $values['payout_change_cooldown_days']) }}">
                                    <span class="input-group-text">days</span>
                                </div>
                                <small class="text-muted">Withdrawals freeze this long after a partner changes their payout number.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Default title split</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="1" max="100" name="default_split_percent" class="form-control"
                                           value="{{ old('default_split_percent', $values['default_split_percent']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Auto-attached splits for VJ-linked partners; capped per title at 100% total.</small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ph ph-floppy-disk me-1"></i> Save settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
