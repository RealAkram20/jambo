@extends('layouts.app', ['module_title' => 'Persons'])

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Persons</h4>
                        <p class="text-muted mb-0" style="font-size:13px;">
                            {{ $totalCount }} total · cast + crew
                        </p>
                    </div>
                    <a href="{{ route('admin.persons.create') }}" class="btn btn-primary">
                        <i class="ph ph-plus me-1"></i> Add person
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success mx-4 mt-3 mb-0">{{ session('success') }}</div>
                @endif

                <div class="card-body">
                    <form method="GET" action="{{ route('admin.persons.index') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-md-9">
                            <label class="form-label" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--bs-secondary);">Search</label>
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search by first name, last name, or known-for...">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                            @if ($search)
                                <a href="{{ route('admin.persons.index') }}" class="btn btn-ghost">Clear</a>
                            @endif
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-0">
                            <thead>
                                <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                                    <th style="width:64px;">Photo</th>
                                    <th>Name</th>
                                    <th>Known for</th>
                                    <th>Born</th>
                                    <th>Movies</th>
                                    <th>Shows</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($persons as $person)
                                    <tr>
                                        <td>
                                            @if ($person->photo_url)
                                                <img src="{{ $person->photo_url }}" alt="" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                                            @else
                                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#1f2738;color:#8791a3;font-size:14px;">
                                                    {{ strtoupper(substr($person->first_name ?? '?', 0, 1)) }}{{ strtoupper(substr($person->last_name ?? '', 0, 1)) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ trim($person->first_name . ' ' . $person->last_name) }}</div>
                                            @if ($person->death_date)
                                                <small class="text-muted">({{ \Carbon\Carbon::parse($person->birth_date)->year ?? '?' }}–{{ \Carbon\Carbon::parse($person->death_date)->year }})</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($person->known_for)
                                                @foreach (array_slice(explode(',', $person->known_for), 0, 3) as $role)
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:10px;">{{ trim($role) }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td style="font-size:12px;color:var(--bs-secondary);">
                                            {{ $person->birth_date ? \Carbon\Carbon::parse($person->birth_date)->format('Y-m-d') : '—' }}
                                        </td>
                                        <td><span class="badge bg-info-subtle text-info-emphasis">{{ $person->movies_count }}</span></td>
                                        <td><span class="badge bg-info-subtle text-info-emphasis">{{ $person->shows_count }}</span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('admin.persons.edit', $person) }}" class="btn btn-sm btn-success-subtle" title="Edit">
                                                    <i class="ph ph-pencil-simple"></i>
                                                </a>
                                                <form method="POST" action="{{ route('admin.persons.destroy', $person) }}" class="d-inline" onsubmit="return confirm('Delete {{ trim($person->first_name . ' ' . $person->last_name) }}? This removes them from every movie and show credit.');">
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
                                        <td colspan="7" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No persons yet.
                                            <a href="{{ route('admin.persons.create') }}">Add your first person →</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($persons->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $persons->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
