@extends('layouts.app', ['module_title' => 'Rating Lists','isSelect2'=>true, 'isSweetalert'=>true, 'isFlatpickr'=>true, 'isBanner'=>false])

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
                        {{__('dashboard.Rating_List')}}
                        <span class="badge bg-info-subtle text-info-emphasis ms-2" style="font-size:13px;">{{ $ratings->total() }}</span>
                    </span>
                </h2>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('dashboard.rating') }}" class="d-flex align-items-center mt-3 gap-2 mb-4">
                <div class="form-group mb-0">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="movie" @selected(request('type') === 'movie')>Movie</option>
                        <option value="show" @selected(request('type') === 'show')>Series</option>
                        <option value="episode" @selected(request('type') === 'episode')>Episode</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">{{__('form.filter')}}</button>
                @if(request('type'))
                    <a href="{{ route('dashboard.rating') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>

            <div class="table-view table-space">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                            <th>No</th>
                            <th>Category</th>
                            <th>Name</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ratings as $rating)
                            <tr>
                                <td>{{ $loop->iteration + ($ratings->currentPage() - 1) * $ratings->perPage() }}</td>
                                <td>{{ class_basename($rating->ratable_type) }}</td>
                                <td>
                                    <p class="mb-0">{{ $rating->ratable?->title ?? $rating->ratable?->name ?? '—' }}</p>
                                </td>
                                <td>{{ $rating->user?->name ?? '—' }}</td>
                                <td><i class="ph-fill ph-star text-primary"></i> {{ $rating->stars }}</td>
                                <td>{{ $rating->created_at->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="{{ route('admin.ratings.destroy', $rating) }}"
                                            class="d-inline"
                                            onsubmit="return confirm('Delete this rating?');">
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
                                    No ratings yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($ratings->hasPages())
                <div class="mt-3">{{ $ratings->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
