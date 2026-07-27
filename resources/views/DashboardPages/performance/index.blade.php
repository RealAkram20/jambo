@extends('layouts.app', ['module_title' => 'Performance', 'title' => 'Performance'])

@section('content')
@php
    $periods = ['day' => 'Today', 'week' => 'Last 7 days', 'month' => 'Last 30 days'];
    $fmtMoney = fn ($n) => $currency . ' ' . number_format((float) $n, 0);
    $typeMeta = [
        'movie'   => ['label' => 'Movies',   'icon' => 'ph-film-strip',   'colour' => 'primary'],
        'show'    => ['label' => 'Series',   'icon' => 'ph-television',    'colour' => 'info'],
        'season'  => ['label' => 'Seasons',  'icon' => 'ph-stack',         'colour' => 'secondary'],
        'episode' => ['label' => 'Episodes', 'icon' => 'ph-play-circle',   'colour' => 'success'],
    ];
    $actionMeta = [
        'created' => ['icon' => 'ph-plus-circle',   'colour' => 'success'],
        'updated' => ['icon' => 'ph-pencil-simple', 'colour' => 'warning'],
        'deleted' => ['icon' => 'ph-trash',         'colour' => 'danger'],
    ];
@endphp

<div class="container-fluid">

    {{-- Header + period filter --}}
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="ph ph-chart-line-up me-2"></i>Performance</h4>
        </div>
        <div class="btn-group" role="group" aria-label="Period">
            @foreach ($periods as $key => $label)
                <a href="{{ route('dashboard.performance', array_filter(['period' => $key, 'actor' => $actorId])) }}"
                   class="btn btn-sm {{ $period === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- My stat tiles --}}
    <div class="row g-3 mb-4">
        @foreach ($typeMeta as $type => $meta)
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-{{ $meta['colour'] }}-subtle text-{{ $meta['colour'] }}-emphasis"
                             style="width:48px;height:48px;">
                            <i class="ph {{ $meta['icon'] }} fs-4"></i>
                        </div>
                        <div>
                            <div class="fs-3 fw-semibold lh-1">{{ $myCounts[$type] }}</div>
                            <div class="text-muted small">{{ $meta['label'] }} added</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- My earnings --}}
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis"
                     style="width:48px;height:48px;">
                    <i class="ph ph-wallet fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Credited to your wallet this period</div>
                    <div class="fs-3 fw-semibold lh-1">{{ $fmtMoney($myEarnings) }}</div>
                    <a class="small" href="{{ route('admin.wallet.index') }}">
                        Wallet balance: {{ $fmtMoney((float) $walletBalance) }}
                    </a>
                </div>
            </div>
            <div class="text-muted small text-end">
                Rates: {{ $fmtMoney($prices['movie']) }}/movie ·
                {{ $fmtMoney($prices['show']) }}/series ·
                {{ $fmtMoney($prices['episode']) }}/episode
                @if ($isSuperAdmin)
                    <br><a href="{{ route('dashboard.performance.settings') }}">Adjust rates</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Admin wallet balances — what's LEFT on each account (all-time
         credits minus payouts), the number a payout request is checked
         against. Not period-scoped, unlike the leaderboard's Earned.
         A looping Swiper (the admin layout already bundles Swiper for
         the dashboard sliders) so any number of admins stays one row. --}}
    @if ($isSuperAdmin && count($adminBalances))
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="ph ph-wallet"></i>
                <h5 class="mb-0">Admin wallet balances</h5>
            </div>
            {{-- Arrows live in the header, not overlaid on the cards:
                 custom ids (not .swiper-button-*) so Swiper's absolute-
                 position arrow CSS never applies. --}}
            <div class="d-flex gap-2">
                <button type="button" id="admin-balances-prev"
                        class="btn btn-sm btn-icon btn-outline-secondary rounded" aria-label="Previous">
                    <i class="ph ph-caret-left"></i>
                </button>
                <button type="button" id="admin-balances-next"
                        class="btn btn-sm btn-icon btn-outline-secondary rounded" aria-label="Next">
                    <i class="ph ph-caret-right"></i>
                </button>
            </div>
        </div>
        {{-- .swiper-container, NOT .swiper: the dashboard bundles Swiper 6,
             whose CSS (overflow:hidden etc.) is keyed to the old class name —
             with .swiper the slides render unclipped across the page. --}}
        <div class="swiper-container mb-4" id="admin-balances-slider">
            <div class="swiper-wrapper">
                @foreach ($adminBalances as $row)
                    <div class="swiper-slide h-auto">
                        <div class="card h-100 mb-0">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning-emphasis"
                                     style="width:48px;height:48px;">
                                    <i class="ph ph-wallet fs-4"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-semibold lh-1">{{ $fmtMoney($row['balance']) }}</div>
                                    <div class="text-muted small">{{ $row['user']?->username ?? 'Unknown' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('admin-balances-slider');
                if (!el || !window.Swiper) return;

                new Swiper(el, {
                    loop: true,
                    spaceBetween: 16,
                    slidesPerView: 1,
                    breakpoints: {
                        576:  { slidesPerView: 2 },
                        992:  { slidesPerView: 3 },
                        1400: { slidesPerView: 4 },
                    },
                    navigation: {
                        nextEl: '#admin-balances-next',
                        prevEl: '#admin-balances-prev',
                    },
                });
            });
        </script>
    @endif

    <div class="row g-4">
        {{-- Super-admin leaderboard --}}
        @if ($isSuperAdmin)
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="ph ph-ranking me-2"></i> Admin leaderboard
                    </div>
                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>Admin</th>
                                    <th class="text-center">Movies</th>
                                    <th class="text-center">Series</th>
                                    <th class="text-center">Episodes</th>
                                    <th class="text-end">Earned</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($leaderboard as $row)
                                    <tr>
                                        <td>{{ $row['user']?->username ?? 'Unknown' }}</td>
                                        <td class="text-center">{{ $row['counts']['movie'] }}</td>
                                        <td class="text-center">{{ $row['counts']['show'] }}</td>
                                        <td class="text-center">{{ $row['counts']['episode'] }}</td>
                                        <td class="text-end fw-semibold">{{ $fmtMoney($row['earnings']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No uploads in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Activity feed --}}
        <div class="col-lg-{{ $isSuperAdmin ? '6' : '12' }}">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="ph ph-clock-counter-clockwise me-2"></i>
                    {{ $isSuperAdmin ? 'Activity — who did what' : 'Your recent activity' }}
                    @if ($isSuperAdmin && $actors->isNotEmpty())
                        <form method="GET" action="{{ route('dashboard.performance') }}" class="ms-auto">
                            <input type="hidden" name="period" value="{{ $period }}">
                            <select name="actor" class="form-select form-select-sm" style="width:auto;"
                                    aria-label="Filter by admin" onchange="this.form.submit()">
                                <option value="">All admins</option>
                                @foreach ($actors as $a)
                                    <option value="{{ $a->id }}" @selected($actorId === $a->id)>{{ $a->username }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
                {{-- Inner scroller: content teams add/update titles all
                     day, so the feed must never stretch the page. --}}
                <div class="overflow-auto" style="max-height:480px;">
                    <ul class="list-unstyled mb-0">
                        @forelse ($feed as $item)
                            @php $am = $actionMeta[$item->action] ?? ['icon' => 'ph-dot', 'colour' => 'secondary']; @endphp
                            <li class="d-flex gap-3 px-3 py-2 border-bottom align-items-center">
                                <span class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-{{ $am['colour'] }}-subtle text-{{ $am['colour'] }}-emphasis"
                                      style="width:34px;height:34px;">
                                    <i class="ph {{ $am['icon'] }}"></i>
                                </span>
                                <div class="flex-grow-1">
                                    <div class="small">
                                        <strong>{{ $item->actor_name ?? ($item->actor?->username ?? 'Someone') }}</strong>
                                        {{ $item->action }}
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $item->content_type }}</span>
                                        {{ $item->content_title }}
                                    </div>
                                    <div class="text-muted" style="font-size:11px;">{{ $item->created_at?->diffForHumans() }}</div>
                                </div>
                            </li>
                        @empty
                            <li class="text-center text-muted py-4">No activity in this period.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
