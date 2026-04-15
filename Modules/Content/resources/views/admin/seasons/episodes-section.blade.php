{{-- Episodes list for the season edit page --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Episodes</h6>
        <a href="{{ route('admin.series.seasons.episodes.create', [$show, $season]) }}" class="btn btn-sm btn-primary">
            <i class="ph ph-plus me-1"></i> Add episode
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                        <th style="width:60px;">#</th>
                        <th>Title</th>
                        <th>Runtime</th>
                        <th>Tier</th>
                        <th>Published</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($episodes as $episode)
                        <tr>
                            <td><span class="fw-semibold">{{ $episode->number }}</span></td>
                            <td>{{ $episode->title }}</td>
                            <td>{{ $episode->runtime_minutes ? $episode->runtime_minutes . ' min' : '—' }}</td>
                            <td>
                                @if ($episode->tier_required)
                                    <span class="badge bg-primary" style="font-size:10px;">{{ $episode->tier_required }}</span>
                                @else
                                    <span class="text-muted" style="font-size:12px;">Free</span>
                                @endif
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
                            <td colspan="6" class="text-center py-4 text-muted" style="font-size:14px;">
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
