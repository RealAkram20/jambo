@extends('layouts.app', ['module_title' => 'Featured'])

@section('content')
{{--
    Featured: the hand-picked hero of the OTT homepage.

    Rows are dragged by the handle and the id order is PATCHed immediately,
    exactly like the categories page. That order is the order the homepage
    banner and its poster rail render in.

    The screen is deliberately honest about what the site is actually
    showing: a featured title that is not published, or whose record has
    gone, is listed with a warning rather than hidden, because otherwise an
    admin cannot tell why the homepage looks wrong.
--}}
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h4 class="card-title mb-1">Featured on the homepage</h4>
                        <p class="mb-0 text-muted" style="font-size:13px;">
                            These titles are the big banner and poster rail on the homepage, in this order.
                            Drag a row to move it. Leave the list empty and the homepage picks the most-watched titles by itself.
                        </p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary-emphasis align-self-start">
                        {{ $items->count() }} featured
                    </span>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success py-2">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger py-2">{{ session('error') }}</div>
                    @endif

                    {{-- Add form: one select per kind, because a movie id and a
                         show id can collide and the endpoint needs to know which
                         table to look in. --}}
                    <form method="POST" action="{{ route('admin.featured.store') }}" class="row g-2 align-items-end mb-4">
                        @csrf
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="featured-movie">Add a movie</label>
                            <select class="form-select" id="featured-movie" name="movie_id">
                                <option value="">Choose a movie…</option>
                                @foreach ($movieOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="featured-show">Add a series</label>
                            <select class="form-select" id="featured-show" name="show_id">
                                <option value="">Choose a series…</option>
                                @foreach ($showOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            {{-- type/id are filled from whichever select the admin
                                 used, so the endpoint stays a simple two-field POST. --}}
                            <input type="hidden" name="type" id="featured-type">
                            <input type="hidden" name="id" id="featured-id">
                            <button type="submit" class="btn btn-primary w-100" id="featured-add-btn" disabled>
                                <i class="ph ph-plus me-1"></i> Add to homepage hero
                            </button>
                        </div>
                    </form>

                    <div class="table-view table-space">
                        <table class="table custom-table movie_table">
                            <thead>
                                <tr class="text-uppercase">
                                    <th style="width:40px;"></th>
                                    <th style="width:60px;">#</th>
                                    <th style="width:80px;">Poster</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>On the homepage</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="featuredSortBody">
                                @forelse ($items as $item)
                                    @php
                                        // NOT $title: the admin header partial renders a
                                        // $title variable, and a value assigned in a child
                                        // view leaks into the layout's scope. Putting a
                                        // model there printed the whole record as JSON
                                        // above the page, because Eloquent stringifies to
                                        // JSON. Any variable set in a Blade view is shared
                                        // with the layout, so scope the name.
                                        $featuredTitle = $item->featurable;
                                        $isShow = $featuredTitle instanceof \Modules\Content\app\Models\Show;
                                        $published = $featuredTitle && $featuredTitle->status === 'published';
                                    @endphp
                                    <tr data-id="{{ $item->id }}">
                                        <td class="featured-drag-handle" style="cursor:grab;"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Drag to reorder">
                                            <i class="ph ph-dots-six-vertical fs-5"></i>
                                        </td>
                                        <td class="text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            @if ($featuredTitle && $featuredTitle->poster_url)
                                                <img src="{{ media_img($featuredTitle->poster_url, 120) }}" alt=""
                                                     style="width:48px;height:68px;object-fit:cover;border-radius:4px;">
                                            @else
                                                <div style="width:48px;height:68px;border-radius:4px;background:rgba(255,255,255,.06);"></div>
                                            @endif
                                        </td>
                                        <td>{{ $featuredTitle->title ?? 'Deleted title' }}</td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                                {{ $isShow ? 'Series' : 'Movie' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if (!$featuredTitle)
                                                <span class="badge bg-danger">Deleted — remove this row</span>
                                            @elseif ($published)
                                                <span class="badge bg-success">Showing</span>
                                            @else
                                                <span class="badge bg-warning"
                                                      title="Only published titles appear on the site">
                                                    Hidden — {{ ucfirst((string) $featuredTitle->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.featured.destroy', $item) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Remove this title from the homepage hero?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-icon btn-danger-subtle rounded"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Remove">
                                                    <i class="ph ph-trash-simple fs-6"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted" style="font-size:14px;">
                                            Nothing featured yet. The homepage is showing the most-watched titles automatically.
                                            Add one above to take control of the hero.
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

{{-- SortableJS only on this page (the admin bundle ships no drag library) —
     same CDN pattern as the categories page and the footer editor. --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tbody = document.getElementById('featuredSortBody');

        if (tbody && window.Sortable && tbody.querySelector('tr[data-id]')) {
            new Sortable(tbody, {
                handle: '.featured-drag-handle',
                animation: 150,
                onEnd: function () {
                    var order = Array.prototype.map.call(
                        tbody.querySelectorAll('tr[data-id]'),
                        function (tr) { return tr.getAttribute('data-id'); }
                    );

                    fetch('{{ route('admin.featured.reorder') }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ order: order }),
                    }).then(function (res) {
                        if (!res.ok) throw new Error('save failed');
                        // Renumber the position column so it matches the new order
                        // without a reload.
                        Array.prototype.forEach.call(
                            tbody.querySelectorAll('tr[data-id]'),
                            function (tr, i) { tr.children[1].textContent = i + 1; }
                        );
                    }).catch(function () {
                        alert('Could not save the new order. Reloading.');
                        window.location.reload();
                    });
                },
            });
        }

        // The two selects are one control: picking in either fills the
        // hidden type/id pair and clears the other, so an admin cannot
        // submit a movie and a series at the same time.
        var movieSelect = document.getElementById('featured-movie');
        var showSelect = document.getElementById('featured-show');
        var typeInput = document.getElementById('featured-type');
        var idInput = document.getElementById('featured-id');
        var addBtn = document.getElementById('featured-add-btn');

        function choose(kind, select, other) {
            return function () {
                if (!select.value) {
                    typeInput.value = idInput.value = '';
                    addBtn.disabled = true;
                    return;
                }
                other.value = '';
                typeInput.value = kind;
                idInput.value = select.value;
                addBtn.disabled = false;
            };
        }

        if (movieSelect && showSelect) {
            movieSelect.addEventListener('change', choose('movie', movieSelect, showSelect));
            showSelect.addEventListener('change', choose('show', showSelect, movieSelect));
        }
    });
</script>
@endsection
