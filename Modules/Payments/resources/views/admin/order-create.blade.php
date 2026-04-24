@extends('layouts.app', ['module_title' => 'Create order'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.payments.orders') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ph ph-arrow-left me-1"></i> Back to orders
                </a>
                <h4 class="m-0 ms-2">Create order</h4>
            </div>
        </div>

        @if ($errors->any())
            <div class="col-12 mb-3">
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.payments.orders.store') }}" id="jambo-create-order-form">
                @csrf

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">Choose a user…</option>
                                    @foreach ($users as $u)
                                        @php
                                            $display = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                                            $display = $display ?: $u->username;
                                        @endphp
                                        <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>
                                            {{ $display }} — {{ $u->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subscription tier <span class="text-danger">*</span></label>
                                <select name="subscription_tier_id" id="jambo-tier-select" class="form-select" required>
                                    <option value="">Choose a plan…</option>
                                    @foreach ($tiers as $t)
                                        <option value="{{ $t->id }}"
                                            data-price="{{ $t->price }}"
                                            data-currency="{{ $t->currency }}"
                                            @selected(old('subscription_tier_id') == $t->id)>
                                            {{ $t->name }} — {{ $t->currency }} {{ number_format((float) $t->price, 0) }} / {{ $t->periodLabel() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="1" min="0" name="amount" id="jambo-amount-input"
                                    class="form-control" value="{{ old('amount') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <input type="text" name="currency" id="jambo-currency-input"
                                    class="form-control text-uppercase"
                                    value="{{ old('currency', $defaultCurrency) }}" maxlength="3" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Gateway <span class="text-danger">*</span></label>
                                <select name="payment_gateway" class="form-select" required>
                                    <option value="manual" @selected(old('payment_gateway', 'manual') === 'manual')>Manual</option>
                                    <option value="pesapal" @selected(old('payment_gateway') === 'pesapal')>PesaPal</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Method</label>
                                <input type="text" name="payment_method" class="form-control"
                                    value="{{ old('payment_method') }}"
                                    placeholder="cash / bank / mpesa" maxlength="50">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="completed" @selected(old('status', 'completed') === 'completed')>Completed</option>
                                    <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                                    <option value="failed" @selected(old('status') === 'failed')>Failed</option>
                                    <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmation code</label>
                                <input type="text" name="confirmation_code" class="form-control"
                                    value="{{ old('confirmation_code') }}"
                                    placeholder="Bank ref / M-Pesa code / receipt #" maxlength="100">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="admin_notes" class="form-control" rows="3" maxlength="2000"
                                    placeholder="Optional">{{ old('admin_notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-check-circle me-1"></i> Create order
                    </button>
                    <a href="{{ route('admin.payments.orders') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">What this does</h5>
                </div>
                <div class="card-body" style="font-size:13px;">
                    <p class="mb-3">
                        Creates a row in <code>payment_orders</code> exactly like a gateway payment, so billing history,
                        reporting, and status filters all see it uniformly.
                    </p>
                    <ul class="ps-3 mb-3" style="font-size:12px;">
                        <li>Merchant reference is auto-generated with a <code>MAN-</code> prefix so manual orders are
                            visually distinct from PesaPal's <code>JAM-</code>.</li>
                        <li>No gateway call is made — no money moves. This just books the record.</li>
                        <li>If status is <strong>Completed</strong>, the subscription is activated right away through
                            the same event the gateway callback uses.</li>
                        <li>You can edit, reconcile, or delete the order afterwards like any other.</li>
                    </ul>
                    <div class="alert alert-warning mb-0" style="font-size:12px;">
                        <i class="ph ph-warning-circle"></i>
                        This doesn't collect payment. Use it to record money that already changed hands elsewhere.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prefill amount + currency when a tier is picked. Stops overwriting
// once the admin manually edits either field.
(function () {
    var tierSelect = document.getElementById('jambo-tier-select');
    var amountInput = document.getElementById('jambo-amount-input');
    var currencyInput = document.getElementById('jambo-currency-input');
    if (!tierSelect || !amountInput || !currencyInput) return;

    tierSelect.addEventListener('change', function () {
        var opt = tierSelect.options[tierSelect.selectedIndex];
        if (!opt || !opt.value) return;
        if (!amountInput.value || amountInput.dataset.auto === '1') {
            amountInput.value = Math.round(parseFloat(opt.getAttribute('data-price') || '0'));
            amountInput.dataset.auto = '1';
        }
        if (!currencyInput.dataset.touched) {
            currencyInput.value = (opt.getAttribute('data-currency') || '').toUpperCase();
        }
    });

    currencyInput.addEventListener('input', function () { currencyInput.dataset.touched = '1'; });
    amountInput.addEventListener('input', function () { amountInput.dataset.auto = '0'; });
})();
</script>
@endsection
