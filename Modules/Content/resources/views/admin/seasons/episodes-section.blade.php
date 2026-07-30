{{-- Episodes list for the season edit page.

     data-bulk-scope wires this table into the shared admin bulk helper
     (components.partials.admin-bulk-confirm), same convention the movies and
     series lists use: row checkboxes `.episodes-row-cb`, select-all
     `#episodes-select-all`, bar `#episodes-bulk-bar`, count
     `#episodes-bulk-count`. --}}
<div class="card mt-4" data-bulk-scope="episodes">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Episodes</h6>
        <a href="{{ route('admin.series.seasons.episodes.create', [$show, $season]) }}" class="btn btn-sm btn-primary">
            <i class="ph ph-plus me-1"></i> Add episode
        </a>
    </div>
    <div class="card-body">
        {{-- Per-episode plan assignment. The series-level action overwrites a
             whole run; this is for the exceptions — a free pilot, or moving
             the back half of a season to Premium. --}}
        <div id="episodes-bulk-bar" class="d-none align-items-center justify-content-between gap-3 mb-3 px-3 py-2 rounded"
             style="background:#0f1422;border:1px solid rgba(255,255,255,.08);">
            <span class="text-light text-nowrap" style="font-size:13px;">
                <span id="episodes-bulk-count">0</span> selected
            </span>

            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                @include('components.partials.bulk-plan-form', [
                    'scope' => 'episodes',
                    'action' => route('admin.series.seasons.episodes.bulk-tier', [$show, $season]),
                    'confirmKey' => 'bulk-tier-episodes',
                    'tierOptions' => $tierOptions,
                ])
            </div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                        <th style="width:36px;">
                            <input type="checkbox" id="episodes-select-all" class="form-check-input" aria-label="Select all episodes">
                        </th>
                        <th style="width:60px;">#</th>
                        <th>Title</th>
                        <th>Runtime</th>
                        <th>Plan</th>
                        <th>Published</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($episodes as $episode)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input episodes-row-cb"
                                       value="{{ $episode->id }}" data-title="Episode {{ $episode->number }}"
                                       aria-label="Select episode {{ $episode->number }}">
                            </td>
                            <td><span class="fw-semibold">{{ $episode->number }}</span></td>
                            <td>{{ $episode->title }}</td>
                            <td>{{ $episode->runtime_minutes ? $episode->runtime_minutes . ' min' : '—' }}</td>
                            <td>
                                @include('components.partials.plan-badge', ['slug' => $episode->tier_required])
                            </td>
                            <td>
                                @if ($episode->published_at)
                                    <span class="badge bg-success">Published</span>
                                @else
                                    <span class="badge bg-warning">Draft</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.series.seasons.episodes.edit', [$show, $season, $episode]) }}" class="btn btn-sm btn-success-subtle" title="Edit">
                                        <i class="ph ph-pencil-simple"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.series.seasons.episodes.destroy', [$show, $season, $episode]) }}" class="d-inline" onsubmit="return confirm('Delete episode {{ $episode->number }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger-subtle" title="Delete">
                                            <i class="ph ph-trash-simple"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted" style="font-size:14px;">
                                No episodes yet.
                                <a href="{{ route('admin.series.seasons.episodes.create', [$show, $season]) }}">Add the first episode →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
