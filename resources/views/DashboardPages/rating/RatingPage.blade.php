@extends('layouts.app', ['module_title' => 'Ratings', 'isSweetalert' => true])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Ratings &amp; Reviews</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">{{ $ratings->total() }} entries on file</p>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('dashboard.rating') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Type</label>
                            <select name="type" class="form-select">
                                <option value="">All types</option>
                                <option value="movie" @selected(request('type') === 'movie')>Movie</option>
                                <option value="show" @selected(request('type') === 'show')>Series</option>
                                <option value="episode" @selected(request('type') === 'episode')>Episode</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">{{ __('form.filter') }}</button>
                            @if (request('type'))
                                <a href="{{ route('dashboard.rating') }}" class="btn btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th style="width:60px;">No</th>
                                    <th>User</th>
                                    <th>Content</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Date</th>
                                    <th class="text-end" style="width:140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ratings as $entry)
                                    @php
                                        $u = $entry->user;
                                        $userName = $u
                                            ? ($u->full_name ?: $u->username)
                                            : '— deleted user —';
                                        $userAvatar = $u?->profile_image ?: branded_icon();
                                        $contentTitle = $entry->target?->title ?? '—';
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration + ($ratings->currentPage() - 1) * $ratings->perPage() }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $userAvatar }}" alt="{{ $userName }}"
                                                     class="rounded-circle"
                                                     style="width:36px;height:36px;object-fit:cover;">
                                                <div style="min-width:0;">
                                                    <div class="fw-semibold text-truncate" style="max-width:180px;">{{ $userName }}</div>
                                                    @if ($u?->email)
                                                        <small class="text-muted text-truncate d-block" style="max-width:180px;">{{ $u->email }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td style="max-width:240px;">
                                            <div class="fw-semibold text-truncate">{{ $contentTitle }}</div>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:10px;">
                                                {{ class_basename($entry->targetType) ?: '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($entry->stars)
                                                <span class="d-inline-flex align-items-center gap-1 text-warning">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i class="ph{{ $i <= $entry->stars ? '-fill' : '' }} ph-star"></i>
                                                    @endfor
                                                    <strong class="ms-1 text-light">{{ $entry->stars }}</strong>
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td style="max-width:280px;">
                                            @if ($entry->kind === 'review')
                                                @if ($entry->reviewTitle)
                                                    <div class="fw-semibold text-truncate">{{ $entry->reviewTitle }}</div>
                                                @endif
                                                <div class="text-muted small text-truncate" style="max-width:280px;">{{ $entry->body }}</div>
                                            @else
                                                <span class="text-muted small">— no comment —</span>
                                            @endif
                                        </td>
                                        <td style="font-size:12px;color:var(--bs-secondary);">
                                            {{ $entry->created_at?->format('d M Y') }}
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @if ($entry->editRoute)
                                                    {{-- Hidden form for the SweetAlert2-driven star edit --}}
                                                    <form id="rating-edit-{{ $entry->kind }}-{{ $entry->id }}"
                                                          method="POST"
                                                          action="{{ $entry->editRoute }}"
                                                          class="d-none">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="stars" value="{{ $entry->stars }}">
                                                    </form>
                                                    <button type="button"
                                                            class="btn btn-sm btn-success-subtle jambo-rating-edit-btn"
                                                            data-form-id="rating-edit-{{ $entry->kind }}-{{ $entry->id }}"
                                                            data-stars="{{ $entry->stars }}"
                                                            title="Edit stars">
                                                        <i class="ph ph-pencil-simple"></i>
                                                    </button>
                                                @endif

                                                <form method="POST" action="{{ $entry->destroyRoute }}"
                                                      class="d-inline"
                                                      data-jambo-confirm="delete-rating">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger-subtle" title="Delete">
                                                        <i class="ph ph-trash-simple"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No ratings or reviews yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($ratings->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $ratings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.partials.admin-bulk-confirm')

<script>
(function () {
    if (typeof Swal === 'undefined') return;

    document.querySelectorAll('.jambo-rating-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var formId = btn.dataset.formId;
            var current = parseInt(btn.dataset.stars, 10);

            Swal.fire({
                title: 'Edit star rating',
                text: 'Pick a new value between 1 and 5.',
                input: 'select',
                inputOptions: { '1': '★', '2': '★★', '3': '★★★', '4': '★★★★', '5': '★★★★★' },
                inputValue: String(current || 5),
                showCancelButton: true,
                confirmButtonText: 'Save',
                background: '#10131c',
                color: '#fff',
                confirmButtonColor: '#1A98FF',
                cancelButtonColor: '#6c757d',
            }).then(function (result) {
                if (!result.isConfirmed) return;
                var form = document.getElementById(formId);
                if (!form) return;
                var input = form.querySelector('input[name="stars"]');
                if (input) input.value = result.value;
                form.submit();
            });
        });
    });
})();
</script>
@endsection
