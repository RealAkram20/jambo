@extends('layouts.app', ['module_title' => 'Vjs', 'isSweetalert' => true, 'isSelect2' => true])

@section('content')
<div class="row streamit-wraper-table2">
    <div class="col-lg-4">
        <div class="card pb-3">
            <div class="card-header">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative">Add New Vj</span>
                </h2>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success mb-3">{{ session('success') }}</div>
                @endif
                @if (isset($errors) && $errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0 mt-1">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.vjs.store') }}">
                    @csrf
                    @include('DashboardPages.vjs._fields', ['vj' => null, 'uid' => 'new'])

                    <div class="d-flex align-items-center justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary">Add Vj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="pb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative">Vjs</span>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-view table-space">
                        <table class="table custom-table movie_table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th><input type="checkbox" class="form-check-input" /></th>
                                    <th>Thumbnail</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Movies</th>
                                    <th>Series</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($vjs ?? [] as $vj)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input" /></td>
                                        <td>
                                            {{-- Show the VJ's actual photo when one is set. The
                                                 coloured square with a microphone glyph stays as
                                                 the fallback: most of the 36 existing VJs have no
                                                 photo yet, and an empty box reads as "broken"
                                                 where the swatch reads as "not set". --}}
                                            @if ($vj->photo_url)
                                                <img src="{{ media_img($vj->photo_url, 80) }}"
                                                    alt="{{ $vj->display_name }}"
                                                    class="rounded"
                                                    style="width:40px;height:40px;object-fit:cover;"
                                                    loading="lazy" decoding="async">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center rounded"
                                                    style="width:40px;height:40px;background:{{ $vj->colour ?? '#1f2738' }};">
                                                    <i class="ph ph-microphone-stage text-white"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $vj->name }}</td>
                                        <td>{{ $vj->slug }}</td>
                                        <td>{{ $vj->movies_count ?? 0 }}</td>
                                        <td>{{ $vj->shows_count ?? 0 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center list-user-action gap-2">
                                                {{-- Edit. VjController::update() and the
                                                     admin.vjs.update route already existed; the
                                                     list simply never surfaced them, so a VJ could
                                                     be created and deleted but never corrected. --}}
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-primary-subtle rounded"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#vj-edit-{{ $vj->id }}"
                                                    title="Edit">
                                                    <i class="ph ph-pencil-simple fs-6"></i>
                                                </button>

                                                <form method="POST" action="{{ route('admin.vjs.destroy', $vj) }}" class="d-inline"
                                                    onsubmit="return confirm('Delete Vj {{ addslashes($vj->name) }}?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-icon btn-danger-subtle rounded"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                        <i class="ph ph-trash-simple fs-6"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted" style="font-size:14px;">
                                            No Vjs yet. Add one using the form.
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

{{-- Edit modals, one per row. Rendered outside the table so the markup stays
     valid (a <form> inside a <tr> is not). Each passes a per-VJ uid so the
     media picker's document-wide preview lookup resolves to the right instance
     — without that, every modal's Browse button would drive the first modal's
     preview. --}}
@foreach ($vjs ?? [] as $vj)
    <div class="modal fade" id="vj-edit-{{ $vj->id }}" tabindex="-1"
        aria-labelledby="vj-edit-label-{{ $vj->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            {{-- The <form> lives INSIDE .modal-body, not wrapped around the
                 header/body/footer.

                 modal-dialog-scrollable works by making .modal-content a flex
                 column with `overflow: hidden`, and letting .modal-body
                 (`flex: 1 1 auto`) scroll within it. A <form> wrapped around all
                 three becomes the single flex item, grows to its natural height,
                 and gets clipped by that `overflow: hidden` — which silently ate
                 the footer, so on a form this tall there was no reachable Save
                 button and the body would not scroll.

                 Keeping header/body/footer as direct children preserves that
                 layout; the footer's submit button reaches the form via the
                 HTML5 `form` attribute. --}}
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vj-edit-label-{{ $vj->id }}">
                        Edit {{ $vj->display_name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('admin.vjs.update', $vj) }}"
                        id="vj-edit-form-{{ $vj->id }}">
                        @csrf @method('PUT')
                        @include('DashboardPages.vjs._fields', ['vj' => $vj, 'uid' => $vj->id])
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" form="vj-edit-form-{{ $vj->id }}">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{-- Wires the Browse buttons to the FileManager picker. Included inline rather
     than pushed to a stack: layouts.app defines no @stack, so a @push here
     would be silently dropped and every Browse button would be inert. This
     matches how content::admin.movies.form loads it. --}}
@include('content::admin.partials.media-picker-script')
@endsection
