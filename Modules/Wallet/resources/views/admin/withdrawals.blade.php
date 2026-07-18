@extends('layouts.app', ['module_title' => 'Payouts'])

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        @foreach (['requested' => 'Awaiting review', 'approved' => 'Approved — send money', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $key => $label)
            <div class="col-6 col-xl-3">
                <div class="card mb-0">
                    <div class="card-body text-center py-3">
                        <h4 class="mb-0">{{ number_format($counts[$key] ?? 0) }}</h4>
                        <span class="text-muted small">{{ $label }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h4 class="card-title mb-0">Payouts</h4>
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="requested" @selected($status === 'requested')>Requested</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="paid" @selected($status === 'paid')>Paid</option>
                    <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                </select>
            </form>
        </div>
        <div class="card-body p-0">
            @include('wallet::admin._queue', ['withdrawals' => $withdrawals])
        </div>
        @if ($withdrawals->hasPages())
            <div class="card-footer">{{ $withdrawals->links() }}</div>
        @endif
    </div>
</div>
@endsection
