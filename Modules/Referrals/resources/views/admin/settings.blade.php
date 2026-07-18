@extends('layouts.app', ['module_title' => 'Referrals'])

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Referral Settings</h4>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ $errors->first() }}</div>
                @endif

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.referrals.settings.update') }}">
                        @csrf @method('PUT')

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Program status</label>
                                <select name="active" class="form-select">
                                    <option value="0" @selected(old('active', $values['active']) === '0')>Inactive</option>
                                    <option value="1" @selected(old('active', $values['active']) === '1')>Active</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Referral cookie window</label>
                                <div class="input-group">
                                    <input type="number" step="1" min="1" max="90" name="cookie_days" class="form-control"
                                           value="{{ old('cookie_days', $values['cookie_days']) }}">
                                    <span class="input-group-text">days</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Referrer earns (of amount paid)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" max="100" name="reward_percent" class="form-control"
                                           value="{{ old('reward_percent', $values['reward_percent']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Referred buyer discount (first payment)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" max="99" name="discount_percent" class="form-control"
                                           value="{{ old('discount_percent', $values['discount_percent']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Minimum withdrawal</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ config('payments.currency', 'UGX') }}</span>
                                    <input type="number" step="1" min="0" name="min_withdrawal" class="form-control"
                                           value="{{ old('min_withdrawal', $values['min_withdrawal']) }}">
                                </div>
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
