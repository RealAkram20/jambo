@extends('layouts.app', ['module_title' => 'review', 'isSelect2' => true, 'isSweetalert' => true, 'isFlatpickr' => true, 'isBanner' => false])

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="streamit-wraper-table">
            @if (session('success'))
                <div class="alert alert-success mx-0 mt-0 mb-3">{{ session('success') }}</div>
            @endif

            <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative">
                        {{__('form.review-list')}}
                        <span class="badge bg-info-subtle text-info-emphasis ms-2" style="font-size:13px;">{{ $reviews->total() }}</span>
                    </span>
                </h2>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('dashboard.review') }}" class="d-flex align-items-center mt-3 gap-2 mb-4">
                <div class="form-group mb-0">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="movie" @selected(request('type') === 'movie')>Movie</option>
                        <option value="show" @selected(request('type') === 'show')>Series</option>
                        <option value="episode" @selected(request('type') === 'episode')>Episode</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="published" @selected($filter === 'published')>Published</option>
                        <option value="unpublished" @selected($filter === 'unpublished')>Unpublished</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">{{__('form.filter')}}</button>
                @if(request('type') || $filter)
                    <a href="{{ route('dashboard.review') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>

            <div class="table-view table-space streamit-wraper-table3">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                            <th>Author</th>
                            <th>Content</th>
                            <th>Review</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reviews as $review)
                            <tr>
                                <td>
                                    {{ $review->user?->name ?? '—' }}
                                    <br><small class="text-muted">{{ $review->user?->email ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ class_basename($review->reviewable_type) }}</span>
                                    <br>
                                    <small>{{ $review->reviewable?->title ?? $review->reviewable?->name ?? '—' }}</small>
                                </td>
                                <td style="max-width:280px;">
                                    @if ($review->title)
                                        <strong>{{ $review->title }}</strong><br>
                                    @endif
                                    <p class="mb-0">{{ Str::limit($review->body, 80) }}</p>
                                </td>
                                <td>
                                    @if ($review->stars)
                                        <i class="ph-fill ph-star text-primary"></i> {{ $review->stars }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($review->is_published)
                                        <span class="badge bg-success">Published</span>
                                    @else
                                        <span class="badge bg-warning">Unpublished</span>
                                    @endif
                                </td>
                                <td>{{ $review->created_at->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="{{ route('admin.reviews.toggle-published', $review) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-{{ $review->is_published ? 'warning' : 'success' }}-subtle"
                                                title="{{ $review->is_published ? 'Unpublish' : 'Publish' }}">
                                                <i class="ph ph-{{ $review->is_published ? 'eye-slash' : 'check-circle' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}"
                                            class="d-inline"
                                            onsubmit="return confirm('Delete this review?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger-subtle" title="Delete">
                                                <i class="ph ph-trash-simple"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted" style="font-size:14px;">
                                    No reviews yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($reviews->hasPages())
                <div class="mt-3">{{ $reviews->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
