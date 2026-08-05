{{-- Title-splits table + pagination. Rendered inside #splits-results
     on the partner page and re-rendered alone for AJAX tab/search/page
     requests (PartnerAdminController::show). --}}
<div class="table-responsive">
    <table class="table custom-table align-middle mb-0">
        <thead>
            <tr class="text-uppercase" style="font-size:11px;letter-spacing:.5px;">
                <th>Title</th><th>Type</th><th class="text-end">Share</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($splits as $split)
                <tr>
                    <td>{{ $split->splittable->title ?? $split->splittable->name ?? '(deleted)' }}</td>
                    <td>
                        @if (str_contains($split->splittable_type, 'Movie'))
                            <span class="badge bg-primary-subtle text-primary-emphasis">Movie</span>
                        @else
                            <span class="badge bg-info-subtle text-info-emphasis">Series</span>
                        @endif
                    </td>
                    <td class="text-end"><code>{{ $split->percent }}%</code></td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-3">No titles match — nothing accrues until splits are set.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($splits->hasPages())
    <div class="d-flex justify-content-center pt-3">{{ $splits->links() }}</div>
@endif
