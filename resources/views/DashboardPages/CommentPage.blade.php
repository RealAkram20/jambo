@extends('layouts.app', ['module_title' => 'Comment Lists', 'isSweetalert' => true, 'isFlatpickr' => true])

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
                        {{ __('dashboard.Comment_List') }}
                        <span class="badge bg-info-subtle text-info-emphasis ms-2" style="font-size:13px;">{{ $comments->total() }}</span>
                    </span>
                </h2>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('dashboard.comment') }}" class="d-flex align-items-center mt-3 gap-2 mb-4">
                <div class="form-group mb-0">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="movie" @selected(request('type') === 'movie')>Movie</option>
                        <option value="show" @selected(request('type') === 'show')>TV Show</option>
                        <option value="episode" @selected(request('type') === 'episode')>Episode</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="approved" @selected($filter === 'approved')>Approved</option>
                        <option value="unapproved" @selected($filter === 'unapproved')>Unapproved</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">{{__('form.filter')}}</button>
                @if(request('type') || $filter)
                    <a href="{{ route('dashboard.comment') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>

            <div class="table-view table-space">
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                            <th>No</th>
                            <th>Content</th>
                            <th>Author</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comments as $comment)
                            <tr>
                                <td>{{ $loop->iteration + ($comments->currentPage() - 1) * $comments->perPage() }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ class_basename($comment->commentable_type) }}</span>
                                    <br>
                                    <small>{{ $comment->commentable?->title ?? $comment->commentable?->name ?? '—' }}</small>
                                </td>
                                <td>{{ $comment->user?->name ?? '—' }}</td>
                                <td>
                                    <p class="mb-0" style="max-width:300px;">{{ Str::limit($comment->body, 100) }}</p>
                                </td>
                                <td>
                                    @if ($comment->is_approved)
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $comment->created_at->format('d M, Y') }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="{{ route('admin.comments.toggle-approved', $comment) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm btn-{{ $comment->is_approved ? 'warning' : 'success' }}-subtle"
                                                title="{{ $comment->is_approved ? 'Unapprove' : 'Approve' }}">
                                                <i class="ph ph-{{ $comment->is_approved ? 'eye-slash' : 'check-circle' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.comments.destroy', $comment) }}"
                                            class="d-inline"
                                            onsubmit="return confirm('Delete this comment?');">
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
                                    No comments yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($comments->hasPages())
                <div class="mt-3">{{ $comments->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
