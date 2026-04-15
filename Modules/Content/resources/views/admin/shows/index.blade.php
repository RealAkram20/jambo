@extends('layouts.app', ['module_title' => 'Series'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Series</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ $statusCounts['all'] }} total
                            · {{ $statusCounts['published'] }} published
                            · {{ $statusCounts['draft'] }} drafts
                        </p>
                    </div>
                    <a href="{{ route('admin.series.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus me-1"></i> Add series
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('admin.series.index') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Search</label>
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search by title...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="published" @selected($statusFilter === 'published')>Published</option>
                                <option value="draft" @selected($statusFilter === 'draft')>Draft</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            @if ($search || $statusFilter)
                                <a href="{{ route('admin.series.index') }}" class="btn btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th style="width:80px;">Poster</th>
                                    <th>Title</th>
                                    <th>Year</th>
                                    <th>Genres</th>
                                    <th>Seasons</th>
                                    <th>Cast</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($shows as $show)
                                    <tr>
                                        <td>
                                            @if ($show->poster_url)
                                                <img src="{{ $show->poster_url }}" alt="" class="rounded" style="width:48px;height:72px;object-fit:cover;">
                                            @else
                                                <div class="rounded d-flex align-items-center justify-content-center" style="width:48px;height:72px;background:#1f2738;color:#8791a3;font-size:20px;">?</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $show->title }}</div>
                                            @if ($show->rating)
                                                <span class="badge bg-secondary" style="font-size:10px;">{{ $show->rating }}</span>
                                            @endif
                                            @if ($show->tier_required)
                                                <span class="badge bg-primary" style="font-size:10px;">{{ $show->tier_required }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $show->year ?: '—' }}</td>
                                        <td style="max-width:200px;">
                                            @foreach ($show->genres->take(3) as $genre)
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:11px;">{{ $genre->name }}</span>
                                            @endforeach
                                            @if ($show->genres->count() > 3)
                                                <span class="text-muted" style="font-size:11px;">+{{ $show->genres->count() - 3 }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-warning-emphasis">{{ $show->seasons_count }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info-emphasis">{{ $show->cast_count }}</span>
                                        </td>
                                        <td>
                                            @if ($show->status === 'published')
                                                <span class="badge bg-success">Published</span>
                                            @else
                                                <span class="badge bg-warning">Draft</span>
                                            @endif
                                        </td>
                                        <td style="font-size:12px;color:var(--bs-secondary);">{{ $show->updated_at?->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('admin.series.edit', $show) }}" class="btn btn-sm btn-success-subtle" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <form method="POST" action="{{ route('admin.series.destroy', $show) }}" class="d-inline" onsubmit="return confirm('Delete {{ $show->title }}? This cannot be undone.');">
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
                                        <td colspan="9" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No series yet.
                                            <a href="{{ route('admin.series.create') }}">Add your first series →</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($shows->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $shows->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
