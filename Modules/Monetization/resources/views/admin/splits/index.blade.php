@extends('layouts.app', ['module_title' => 'Monetization'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-1">Title Splits</h4>
                    <p class="text-muted mb-0" style="font-size:13px;">
                        Which partners earn from which titles. VJ credits on content are display-only — money follows these splits.
                        Unassigned percentage stays with the platform.
                    </p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('admin.monetization.splits.index') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-md-8">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">
                                Attribute a new title
                            </label>
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search movies and shows by title…">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Search</button>
                            @if ($search)
                                <a href="{{ route('admin.monetization.splits.index') }}" class="btn btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>

                    @if ($search)
                        <div class="mb-4">
                            @forelse ($results as $result)
                                <a href="{{ route('admin.monetization.splits.edit', ['type' => $result['type'], 'id' => $result['id']]) }}"
                                   class="btn btn-sm btn-info-subtle me-2 mb-2">
                                    <i class="ph ph-{{ $result['type'] === 'movie' ? 'film-strip' : 'television' }} me-1"></i>
                                    {{ $result['title'] }}
                                </a>
                            @empty
                                <p class="text-muted mb-0">No titles matched “{{ $search }}”.</p>
                            @endforelse
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Partners</th>
                                    <th>Assigned</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($titles as $row)
                                    <tr>
                                        <td><strong>{{ $row['title'] }}</strong></td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($row['type']) }}</span>
                                        </td>
                                        <td>
                                            @foreach ($row['splits'] as $split)
                                                <span class="badge bg-info-subtle text-info-emphasis me-1">
                                                    {{ $split->partner->display_name ?? '?' }} · {{ $split->percent }}%
                                                </span>
                                            @endforeach
                                        </td>
                                        <td>
                                            <code>{{ number_format($row['total'], 2) }}%</code>
                                            @if ($row['total'] < 100)
                                                <small class="text-muted">({{ number_format(100 - $row['total'], 2) }}% platform)</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.monetization.splits.edit', ['type' => $row['type'], 'id' => $row['id']]) }}"
                                               class="btn btn-sm btn-success-subtle" title="Edit splits">
                                                <i class="ph ph-pencil-simple"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No titles attributed yet — search above to set the first split.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
