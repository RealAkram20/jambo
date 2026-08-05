@extends('layouts.app', ['module_title' => 'Payments'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Payment orders</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            <span data-count="all">{{ $statusCounts['all'] }}</span> total
                            · <span data-count="completed">{{ $statusCounts['completed'] }}</span> completed
                            · <span data-count="pending">{{ $statusCounts['pending'] }}</span> pending
                            · <span data-count="failed">{{ $statusCounts['failed'] }}</span> failed
                            · <span data-count="cancelled">{{ $statusCounts['cancelled'] }}</span> cancelled
                        </p>
                    </div>
                    <a href="{{ route('admin.payments.orders.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus me-1"></i> Create order
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif

                <div class="card-body">
                    {{-- Filter bar. Matches the same visual pattern as the
                         movies / shows admin index so the chrome stays
                         consistent across the admin area. --}}
                    <form method="GET" action="{{ route('admin.payments.orders') }}" class="row g-2 align-items-end mb-4" id="orders-filter-form">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Search</label>
                            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control"
                                placeholder="Customer name, email, phone, reference…">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All ({{ $statusCounts['all'] }})</option>
                                <option value="pending" @selected($filters['status'] === 'pending')>Pending ({{ $statusCounts['pending'] }})</option>
                                <option value="completed" @selected($filters['status'] === 'completed')>Completed ({{ $statusCounts['completed'] }})</option>
                                <option value="failed" @selected($filters['status'] === 'failed')>Failed ({{ $statusCounts['failed'] }})</option>
                                <option value="cancelled" @selected($filters['status'] === 'cancelled')>Cancelled ({{ $statusCounts['cancelled'] }})</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Gateway</label>
                            <select name="gateway" class="form-select">
                                <option value="">All</option>
                                @foreach ($gateways as $g)
                                    <option value="{{ $g }}" @selected($filters['gateway'] === $g)>{{ ucfirst($g) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">From</label>
                            <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">To</label>
                            <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control">
                        </div>
                        <div class="col-md-1 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                        </div>
                        {{-- Always in the DOM: the AJAX live search must be
                             able to show/hide it as filters come and go. --}}
                        <div class="col-12" id="orders-clear-filters" @if (!array_filter($filters)) hidden @endif>
                            <a href="{{ route('admin.payments.orders') }}" class="btn btn-ghost btn-sm">
                                <i class="ph ph-x me-1"></i> Clear filters
                            </a>
                        </div>
                    </form>

                    {{-- Table + pagination live in a partial so the AJAX
                         endpoint can re-render just this fragment. --}}
                    <div id="orders-results">
                        @include('payments::admin.partials.orders-table', ['orders' => $orders])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form    = document.getElementById('orders-filter-form');
    var results = document.getElementById('orders-results');
    if (!form || !results) return;

    var qInput   = form.querySelector('[name="q"]');
    var debounce = null;
    var inflight = null;

    function formUrl() {
        var params = new URLSearchParams(new FormData(form));
        // Drop empties so the URL stays clean (?q=&status= → ?).
        Array.from(params.keys()).forEach(function (k) {
            if (!params.get(k)) params.delete(k);
        });
        var qs = params.toString();
        return form.action + (qs ? '?' + qs : '');
    }

    function load(url) {
        // Abort the previous request so a slow response can't arrive
        // late and overwrite the results of a newer keystroke.
        if (inflight) inflight.abort();
        inflight = new AbortController();
        results.style.opacity = '.45';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: inflight.signal
        })
        .then(function (r) {
            if (!r.ok) throw new Error(r.status);
            return r.json();
        })
        .then(function (data) {
            results.innerHTML = data.table;
            results.style.opacity = '';
            history.replaceState(null, '', url);
            // Refresh the header tallies + the Status dropdown labels.
            Object.keys(data.counts || {}).forEach(function (key) {
                var el = document.querySelector('[data-count="' + key + '"]');
                if (el) el.textContent = data.counts[key];
            });
            var clearBtn = document.getElementById('orders-clear-filters');
            if (clearBtn) {
                var hasFilters = Array.from(new FormData(form).values()).some(function (v) { return v !== ''; });
                clearBtn.hidden = !hasFilters;
            }
            var statusSel = form.querySelector('[name="status"]');
            if (statusSel) {
                Array.from(statusSel.options).forEach(function (opt) {
                    var key = opt.value || 'all';
                    if (data.counts && key in data.counts) {
                        var label = opt.value ? opt.value.charAt(0).toUpperCase() + opt.value.slice(1) : 'All';
                        opt.textContent = label + ' (' + data.counts[key] + ')';
                    }
                });
            }
        })
        .catch(function (err) {
            if (err.name === 'AbortError') return;
            // Network / server error: fall back to a full page load so
            // the admin still gets their results.
            window.location.assign(url);
        });
    }

    // Live search: debounce keystrokes.
    if (qInput) {
        qInput.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(function () { load(formUrl()); }, 350);
        });
    }

    // Selects + date fields fire immediately on change.
    ['status', 'gateway', 'from', 'to'].forEach(function (name) {
        var el = form.querySelector('[name="' + name + '"]');
        if (el) el.addEventListener('change', function () { load(formUrl()); });
    });

    // The Filter button (and Enter in a field) goes through AJAX too.
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(debounce);
        load(formUrl());
    });

    // Pagination links arrive inside the swapped fragment — delegate.
    results.addEventListener('click', function (e) {
        var link = e.target.closest('.pagination a');
        if (!link) return;
        e.preventDefault();
        load(link.href);
        results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
})();
</script>
@endsection
