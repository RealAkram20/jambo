@extends('monetization::layouts.partner')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="card-title mb-1">My titles — {{ $month->format('F Y') }}</h4>
            <p class="text-muted mb-0" style="font-size:13px;">
                Live qualified views and minutes on your attributed titles. Only paid viewers who watch past the
                completion threshold count.
                @unless ($partner->can_edit_content || $partner->can_delete_content)
                    Editing and deleting your titles requires rights granted by the Jambo team.
                @endunless
            </p>
        </div>
        <form method="GET" action="{{ route('partner.titles') }}" class="d-flex gap-2" id="titles-filter-form">
            <input type="hidden" name="type" value="{{ $filters['type'] }}">
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control"
                   placeholder="Search your titles…" style="min-width:180px;">
            <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}">
            <button class="btn btn-primary">Go</button>
        </form>
    </div>
    <div class="card-body">
        {{-- Movies / Series tabs. Counts follow the current search. --}}
        <ul class="nav nav-pills gap-2 mb-3" id="titles-type-tabs">
            @foreach ([['', 'All'], ['movie', 'Movies'], ['show', 'Series']] as [$val, $label])
                <li class="nav-item">
                    <button type="button"
                            class="nav-link py-1 px-3 {{ $filters['type'] === $val ? 'active' : '' }}"
                            data-type-tab="{{ $val }}">
                        {{ $label }} (<span data-count="{{ $val ?: 'all' }}">{{ $counts[$val ?: 'all'] }}</span>)
                    </button>
                </li>
            @endforeach
        </ul>

        <div id="titles-results">
            @include('monetization::partner.partials.titles-table', ['rows' => $rows, 'partner' => $partner])
        </div>
    </div>
</div>

<script>
(function () {
    var form    = document.getElementById('titles-filter-form');
    var results = document.getElementById('titles-results');
    if (!form || !results) return;

    var qInput    = form.querySelector('[name="q"]');
    var typeInput = form.querySelector('[name="type"]');
    var debounce  = null;
    var inflight  = null;

    function formUrl() {
        var params = new URLSearchParams(new FormData(form));
        Array.from(params.keys()).forEach(function (k) {
            if (!params.get(k)) params.delete(k);
        });
        var qs = params.toString();
        return form.action + (qs ? '?' + qs : '');
    }

    function load(url) {
        if (inflight) inflight.abort();
        inflight = new AbortController();
        results.style.opacity = '.45';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: inflight.signal })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                results.innerHTML = data.table;
                results.style.opacity = '';
                history.replaceState(null, '', url);
                Object.keys(data.counts || {}).forEach(function (key) {
                    var el = document.querySelector('[data-count="' + key + '"]');
                    if (el) el.textContent = data.counts[key];
                });
            })
            .catch(function (err) {
                if (err.name === 'AbortError') return;
                window.location.assign(url);
            });
    }

    qInput.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () { load(formUrl()); }, 350);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(debounce);
        load(formUrl());
    });

    document.querySelectorAll('[data-type-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            typeInput.value = tab.dataset.typeTab;
            document.querySelectorAll('[data-type-tab]').forEach(function (t) {
                t.classList.toggle('active', t === tab);
            });
            load(formUrl());
        });
    });

    // Month changes reprice every row — full filter run, still AJAX.
    form.querySelector('[name="month"]').addEventListener('change', function () { load(formUrl()); });

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
