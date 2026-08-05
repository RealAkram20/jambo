{{-- My-titles table + pagination. Rendered inside #titles-results on
     the full page and re-rendered alone for AJAX search/filter/page
     requests (PartnerStatementController::titles). --}}
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
                    <td>
                        <span class="badge {{ $row['type'] === 'movie' ? 'bg-primary-subtle text-primary-emphasis' : 'bg-info-subtle text-info-emphasis' }}">
                            {{ $row['type'] === 'movie' ? 'Movie' : 'Series' }}
                        </span>
                    </td>
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
                <tr><td colspan="7" class="text-center py-5 text-muted">No titles match — try a different search or tab.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($rows->hasPages())
    <div class="d-flex justify-content-center pt-3">{{ $rows->links() }}</div>
@endif
