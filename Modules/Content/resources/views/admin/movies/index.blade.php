@extends('layouts.app', ['module_title' => 'Movies', 'isSweetalert' => true])

@section('content')
<div class="container-fluid" data-bulk-scope="movies">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Movies</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ $statusCounts['all'] }} total
                            · {{ $statusCounts['published'] }} published
                            · {{ $statusCounts['upcoming'] }} upcoming
                            · {{ $statusCounts['draft'] }} drafts
                        </p>
                    </div>
                    <a href="{{ route('admin.movies.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus me-1"></i> Add movie
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('admin.movies.index') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Search</label>
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search by title...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="published" @selected($statusFilter === 'published')>Published</option>
                                <option value="upcoming" @selected($statusFilter === 'upcoming')>Upcoming</option>
                                <option value="draft" @selected($statusFilter === 'draft')>Draft</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            @if ($search || $statusFilter)
                                <a href="{{ route('admin.movies.index') }}" class="btn btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>

                    {{-- Bulk action bar — hidden until at least one row is checked.
                         Submission is intercepted by JS so we can route through the
                         SweetAlert2 confirm before posting to bulk-destroy. --}}
                    <div id="movies-bulk-bar" class="d-none align-items-center justify-content-between gap-3 mb-3 px-3 py-2 rounded"
                         style="background:#0f1422;border:1px solid rgba(255,255,255,.08);">
                        <span class="text-light" style="font-size:13px;">
                            <span id="movies-bulk-count">0</span> selected
                        </span>
                        <form id="movies-bulk-form" method="POST" action="{{ route('admin.movies.bulk-destroy') }}"
                              data-jambo-confirm="bulk-delete-movies" class="m-0">
                            @csrf
                            @method('DELETE')
                            <div id="movies-bulk-ids"></div>
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="ph ph-trash-simple me-1"></i> Delete selected
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th style="width:36px;">
                                        <input type="checkbox" id="movies-select-all" class="form-check-input" aria-label="Select all">
                                    </th>
                                    <th style="width:80px;">Poster</th>
                                    <th>Title</th>
                                    <th>Year</th>
                                    <th>Genres</th>
                                    <th>Cast</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movies as $movie)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input movies-row-cb"
                                                   value="{{ $movie->id }}" data-title="{{ $movie->title }}"
                                                   aria-label="Select {{ $movie->title }}">
                                        </td>
                                        <td>
                                            @if ($movie->poster_url)
                                                <img src="{{ $movie->poster_url }}" alt="" class="rounded" style="width:48px;height:72px;object-fit:cover;">
                                            @else
                                                <div class="rounded d-flex align-items-center justify-content-center" style="width:48px;height:72px;background:#1f2738;color:#8791a3;font-size:20px;">?</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $movie->title }}</div>
                                            @if ($movie->rating)
                                                <span class="badge bg-secondary" style="font-size:10px;">{{ $movie->rating }}</span>
                                            @endif
                                            @if ($movie->tier_required)
                                                <span class="badge bg-primary" style="font-size:10px;">{{ $movie->tier_required }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $movie->year ?: '—' }}</td>
                                        <td style="max-width:200px;">
                                            @foreach ($movie->genres->take(3) as $genre)
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:11px;">{{ $genre->name }}</span>
                                            @endforeach
                                            @if ($movie->genres->count() > 3)
                                                <span class="text-muted" style="font-size:11px;">+{{ $movie->genres->count() - 3 }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info-emphasis">{{ $movie->cast_count }}</span>
                                        </td>
                                        <td>
                                            @if ($movie->status === 'published')
                                                <span class="badge bg-success">Published</span>
                                            @elseif ($movie->status === 'upcoming')
                                                <span class="badge bg-info">Upcoming</span>
                                            @else
                                                <span class="badge bg-warning">Draft</span>
                                            @endif
                                            @if ($movie->transcode_status)
                                                @switch($movie->transcode_status)
                                                    @case('queued')
                                                        <span class="badge bg-secondary" title="Transcode queued" style="font-size:10px;">Queued</span>
                                                        @break
                                                    @case('transcoding')
                                                        <span class="badge bg-info" title="Transcoding in progress" style="font-size:10px;">Transcoding</span>
                                                        @break
                                                    @case('ready')
                                                        <span class="badge bg-success" title="HLS stream ready" style="font-size:10px;">HLS</span>
                                                        @break
                                                    @case('failed')
                                                        <span class="badge bg-danger" title="{{ $movie->transcode_error }}" style="font-size:10px;">Transcode failed</span>
                                                        @break
                                                @endswitch
                                            @endif
                                        </td>
                                        <td style="font-size:12px;color:var(--bs-secondary);">{{ $movie->updated_at?->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('admin.movies.edit', $movie) }}" class="btn btn-sm btn-success-subtle" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <form method="POST" action="{{ route('admin.movies.destroy', $movie) }}"
                                                      class="d-inline"
                                                      data-jambo-confirm="delete-movie"
                                                      data-title="{{ $movie->title }}">
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
                                            No movies yet.
                                            <a href="{{ route('admin.movies.create') }}">Add your first movie →</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($movies->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $movies->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.partials.admin-bulk-confirm')
@endsection
