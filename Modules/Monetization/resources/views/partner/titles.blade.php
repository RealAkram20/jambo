@extends('monetization::layouts.partner')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="card-title mb-1">My titles — {{ $month->format('F Y') }}</h4>
            <p class="text-muted mb-0" style="font-size:13px;">
                Live qualified views and minutes on your attributed titles. Only paid viewers who watch past the
                completion threshold count.
                @unless ($partner->can_edit_content || $partner->can_delete_content)
                    Editing and deleting your titles requires rights granted by the Jambo team.
                @endunless
            </p>
        </div>
        <form method="GET" action="{{ route('partner.titles') }}" class="d-flex gap-2">
            <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}">
            <button class="btn btn-primary">Go</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table custom-table align-middle mb-0">
                <thead>
                    <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                        <th>Title</th>
                        <th>Type</th>
                        <th>Your split</th>
                        <th>Qualified views</th>
                        <th>Total minutes</th>
                        <th class="text-end">Your minutes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><strong>{{ $row['title'] }}</strong></td>
                            <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($row['type']) }}</span></td>
                            <td><code>{{ $row['percent'] }}%</code></td>
                            <td>{{ number_format($row['qualified_views']) }}</td>
                            <td>{{ number_format($row['minutes'], 0) }}</td>
                            <td class="text-end fw-bold">{{ number_format($row['your_minutes'], 1) }}</td>
                            <td class="text-end">
                                @if ($row['exists'])
                                    <div class="d-inline-flex gap-1">
                                        @if ($row['slug'])
                                            <a href="{{ $row['type'] === 'movie'
                                                    ? route('frontend.movie_detail', ['slug' => $row['slug']])
                                                    : route('frontend.series_detail', ['slug' => $row['slug']]) }}"
                                               class="btn btn-sm btn-info-subtle" title="Watch">
                                                <i class="ph ph-play"></i>
                                            </a>
                                        @endif
                                        @if ($partner->can_edit_content)
                                            <a href="{{ route('partner.content.edit', ['type' => $row['type'], 'id' => $row['id']]) }}"
                                               class="btn btn-sm btn-success-subtle" title="Edit details">
                                                <i class="ph ph-pencil-simple"></i>
                                            </a>
                                        @endif
                                        @if ($partner->can_delete_content)
                                            <form method="POST"
                                                  action="{{ route('partner.content.destroy', ['type' => $row['type'], 'id' => $row['id']]) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Permanently delete “{{ $row['title'] }}” from Jambo? Viewers lose access immediately. Past earnings stay in your statements.');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger-subtle" title="Delete">
                                                    <i class="ph ph-trash-simple"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">No titles are attributed to you yet — contact the Jambo team.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
