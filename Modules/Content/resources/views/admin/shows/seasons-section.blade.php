{{-- Seasons list for the show edit page --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Seasons</h6>
        <a href="{{ route('admin.series.seasons.create', $show) }}" class="btn btn-sm btn-primary">
            <i class="ph ph-plus me-1"></i> Add season
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                        <th style="width:60px;">#</th>
                        <th>Title</th>
                        <th>Episodes</th>
                        <th>Released</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($seasons as $season)
                        <tr>
                            <td><span class="fw-semibold">{{ $season->number }}</span></td>
                            <td>{{ $season->title ?: '—' }}</td>
                            <td>
                                <span class="badge bg-info-subtle text-info-emphasis">{{ $season->episodes_count ?? $season->episodes()->count() }}</span>
                            </td>
                            <td style="font-size:12px;color:var(--bs-secondary);">
                                {{ $season->released_at ? \Illuminate\Support\Carbon::parse($season->released_at)->format('M j, Y') : '—' }}
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.series.seasons.edit', [$show, $season]) }}" class="btn btn-sm btn-success-subtle" title="Edit">
                                        <i class="ph ph-pencil-simple"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.series.seasons.destroy', [$show, $season]) }}" class="d-inline" onsubmit="return confirm('Delete season {{ $season->number }}? This cannot be undone.');">
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
                            <td colspan="5" class="text-center py-4 text-muted" style="font-size:14px;">
                                No seasons yet.
                                <a href="{{ route('admin.series.seasons.create', $show) }}">Add the first season →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
