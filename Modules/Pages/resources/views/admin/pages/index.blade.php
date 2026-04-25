@extends('layouts.app', ['module_title' => 'Pages'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Pages</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ $statusCounts['all'] }} total
                            · {{ $statusCounts['published'] }} published
                            · {{ $statusCounts['draft'] }} drafts
                        </p>
                    </div>
                    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus me-1"></i> Add page
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mx-4 mt-3 mb-0">{{ session('error') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('admin.pages.index') }}" class="row g-2 align-items-end mb-4">
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
                                <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>Title</th>
                                    <th>Slug</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pages as $page)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $page->title }}</div>
                                            @if ($page->is_system)
                                                <span class="badge bg-info-subtle text-info-emphasis" style="font-size:10px;">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($page->slug === 'footer')
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:11px;">Site-wide footer</span>
                                            @else
                                                <code style="font-size:12px;">/{{ $page->slug }}</code>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($page->status === 'published')
                                                <span class="badge bg-success">Published</span>
                                            @else
                                                <span class="badge bg-warning">Draft</span>
                                            @endif
                                        </td>
                                        <td style="font-size:12px;color:var(--bs-secondary);">{{ $page->updated_at?->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-success-subtle" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                @if (!$page->is_system)
                                                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="d-inline" onsubmit="return confirm('Delete {{ $page->title }}? This cannot be undone.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger-subtle" title="Delete">
                                                            <i class="ph ph-trash-simple"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-secondary-subtle" disabled title="System pages cannot be deleted">
                                                        <i class="ph ph-lock"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No pages yet.
                                            <a href="{{ route('admin.pages.create') }}">Add your first page →</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($pages->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $pages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
