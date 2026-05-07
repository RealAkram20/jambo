@extends('layouts.app', ['module_title' => 'Signup attempts'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="mb-1">Signup attempts</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Every public signup POST — success or failure — for the last while.
                        Use this when a user reports "I tried to sign up and got an error" —
                        filter by their email or IP and the <strong>outcome</strong> column tells
                        you instantly which failure path they hit.
                    </p>
                </div>
                <a href="{{ route('admin.diagnostics.logs') }}" class="btn btn-ghost btn-sm">
                    <i class="ph ph-arrow-left me-1"></i> Other diagnostics
                </a>
            </div>

            {{-- Last-7-day summary --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="mb-3">Last 7 days</h6>
                    @if ($totalAll === 0)
                        <p class="text-muted mb-0">No signup attempts in the last 7 days.</p>
                    @else
                        @php
                            $rate = $totalAll > 0 ? round(($totalSuccess / $totalAll) * 100, 1) : 0;
                        @endphp
                        <p class="mb-3">
                            <strong>{{ $totalAll }}</strong> attempts,
                            <strong>{{ $totalSuccess }}</strong> successful
                            (<strong>{{ $rate }}%</strong> success rate).
                        </p>
                        <div class="row g-2">
                            @foreach ($allOutcomes as $key => $label)
                                @php $count = $countsByOutcome[$key] ?? 0; @endphp
                                @if ($count > 0)
                                    <div class="col-md-3 col-sm-6">
                                        <div class="border rounded p-2 d-flex justify-content-between align-items-center"
                                             style="background:#0b0f17;border-color:#1f2738 !important;">
                                            <span class="text-muted small">{{ $label }}</span>
                                            <strong>{{ $count }}</strong>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.diagnostics.signups') }}" class="row g-2 align-items-end mb-4">
                <div class="col-md-4">
                    <label class="form-label small">Email / username / IP</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ $search }}"
                           placeholder="Search…">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Outcome</label>
                    <select name="outcome" class="form-select form-select-sm">
                        <option value="">All outcomes</option>
                        @foreach ($allOutcomes as $key => $label)
                            <option value="{{ $key }}" @selected($outcome === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ph ph-magnifying-glass me-1"></i> Filter
                    </button>
                </div>
                @if ($search !== '' || $outcome !== '')
                    <div class="col-md-2">
                        <a href="{{ route('admin.diagnostics.signups') }}" class="btn btn-ghost btn-sm">Clear</a>
                    </div>
                @endif
            </form>

            {{-- Attempts table --}}
            <div class="card">
                <div class="card-body p-0">
                    @if ($attempts->isEmpty())
                        <div class="p-4 text-center text-muted">
                            No attempts match your filter.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="font-size:13px;">
                                <thead style="background:#0b0f17;">
                                    <tr>
                                        <th>When</th>
                                        <th>Outcome</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>IP</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($attempts as $a)
                                        @php
                                            $badge = match ($a->outcome) {
                                                'success'        => 'success',
                                                'honeypot'       => 'warning',
                                                'recaptcha_fail' => 'warning',
                                                'validation_error' => 'info',
                                                'csrf_expired'   => 'danger',
                                                'throttle'       => 'warning',
                                                'exception'      => 'danger',
                                                default          => 'secondary',
                                            };
                                            $outcomeLabel = $allOutcomes[$a->outcome] ?? $a->outcome;
                                        @endphp
                                        <tr>
                                            <td class="text-muted" style="white-space:nowrap;">
                                                <span title="{{ $a->created_at?->toDateTimeString() }}">
                                                    {{ $a->created_at?->diffForHumans() }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $badge }}-subtle text-{{ $badge }}-emphasis">
                                                    {{ $outcomeLabel }}
                                                </span>
                                            </td>
                                            <td class="text-truncate" style="max-width:240px;">
                                                {{ $a->email_attempted ?: '—' }}
                                            </td>
                                            <td>{{ $a->username_attempted ?: '—' }}</td>
                                            <td class="text-muted" style="font-family:monospace;">
                                                {{ $a->ip ?: '—' }}
                                            </td>
                                            <td>
                                                @if ($a->details)
                                                    <details>
                                                        <summary class="text-muted small" style="cursor:pointer;">view</summary>
                                                        <pre class="mt-2 small mb-0" style="white-space:pre-wrap;font-size:11px;color:#9ca3af;">{{ json_encode($a->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                    </details>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($attempts->hasPages())
                            <div class="p-3 d-flex justify-content-center">
                                {{ $attempts->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
