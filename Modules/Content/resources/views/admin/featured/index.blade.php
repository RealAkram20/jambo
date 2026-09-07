@extends('layouts.app', ['module_title' => 'Featured', 'Activelink' => 'Featured'])

@section('content')
{{--
    Featured: the hand-picked hero of the OTT homepage.

    Deliberately built on the same shell as the Categories screen — a
    col-lg-4 "add" card beside a col-lg-8 list card, the same underlined
    headings, the same table classes and drag handle. An admin screen that
    invents its own layout costs more to learn than it gains in polish.

    The one thing without a house equivalent is the catalogue type-ahead,
    so only its dropdown carries scoped styles. Everything else inherits.

    Honest by design: a featured title that is unpublished, or whose record
    has been deleted, is listed with a warning rather than hidden. Hiding it
    would leave nobody able to explain why the homepage looks wrong.
--}}
<style>
    /* Catalogue type-ahead. No house component for this, so it is scoped
       here and drawn from Bootstrap theme variables so it follows the
       admin theme rather than pinning its own colours. */
    .jf-search { position: relative; }

    .jf-results {
        position: absolute;
        z-index: 20;
        top: calc(100% + 4px);
        left: 0; right: 0;
        max-height: 340px;
        overflow-y: auto;
        padding: 0.3rem;
        border: 1px solid rgba(var(--bs-body-color-rgb), 0.14);
        border-radius: 8px;
        background: var(--bs-body-bg);
        box-shadow: 0 12px 32px -8px rgba(0, 0, 0, 0.45);
    }

    .jf-result {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        width: 100%;
        padding: 0.45rem;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: var(--bs-body-color);
        text-align: left;
        cursor: pointer;
    }

    .jf-result:hover:not(:disabled),
    .jf-result.is-active:not(:disabled) { background: rgba(var(--bs-primary-rgb), 0.16); }
    .jf-result:disabled { opacity: 0.55; cursor: not-allowed; }

    .jf-result__poster {
        width: 32px; height: 45px;
        flex: 0 0 auto;
        object-fit: cover;
        border-radius: 4px;
        background: rgba(var(--bs-body-color-rgb), 0.08);
    }

    .jf-result__title { font-size: 0.88rem; font-weight: 500; line-height: 1.25; }
    .jf-result__meta { font-size: 0.74rem; color: rgba(var(--bs-body-color-rgb), 0.68); }
    .jf-results__note { padding: 0.85rem; font-size: 0.85rem; color: rgba(var(--bs-body-color-rgb), 0.68); margin: 0; }

    .jf-spinner {
        position: absolute;
        top: 50%; right: 0.75rem;
        transform: translateY(-50%);
        width: 14px; height: 14px;
        border: 2px solid rgba(var(--bs-body-color-rgb), 0.25);
        border-top-color: var(--bs-primary);
        border-radius: 50%;
        animation: jf-spin 0.6s linear infinite;
    }

    @keyframes jf-spin { to { transform: rotate(360deg); } }

    /* ---- Craft inside the house shell ------------------------------ */
    /* The structure is the standard admin table. These are the details:
       a readable rank, posters that look like posters, a row that responds,
       and a settle after a drag so the save is visible without a banner. */

    .jf-row { transition: background-color 0.15s ease; }
    .jf-row:hover { background: rgba(var(--bs-primary-rgb), 0.05); }

    .jf-rank {
        /* Tabular figures so 1 and 10 hold the same width mid-drag. */
        font-variant-numeric: tabular-nums;
        font-size: 1.05rem;
        font-weight: 600;
        color: rgba(var(--bs-body-color-rgb), 0.72);
    }

    .jf-row--lead .jf-rank { color: var(--bs-primary); }

    .jf-poster {
        width: 46px;
        aspect-ratio: 2 / 3;
        object-fit: cover;
        border-radius: 6px;
        background: rgba(var(--bs-body-color-rgb), 0.08);
        box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.5);
    }

    /* The saved row lights briefly, then settles — the reorder confirms
       itself where it happened rather than only in the header. */
    .jf-row.is-saved { animation: jf-settle 0.55s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes jf-settle { from { background: rgba(var(--bs-primary-rgb), 0.18); } }

    .jf-empty-state { padding: 2.5rem 1rem; }
    .jf-empty-state i { font-size: 1.9rem; color: rgba(var(--bs-body-color-rgb), 0.32); }

    .featured-drag-handle { cursor: grab; }
    .featured-drag-handle:active { cursor: grabbing; }
    .jf-row.sortable-ghost { opacity: 0.35; }

    /* Reorder feedback, in place of a blocking alert(): it names what
       happened and, on failure, what to do next. */
    .jf-status { font-size: 0.82rem; opacity: 0; transition: opacity 0.2s ease; }
    .jf-status.is-on { opacity: 1; }
    .jf-status[data-tone="ok"] { color: var(--bs-success); }
    .jf-status[data-tone="bad"] { color: var(--bs-danger); }

    @media (prefers-reduced-motion: reduce) {
        .jf-spinner { animation-duration: 1.6s; }
    }
</style>

<div class="row streamit-wraper-table2">
    {{-- Add column, in the position the Categories screen puts its form. --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h2 class="episode-playlist-title wp-heading-inline">
                    <span class="position-relative">Add to Featured</span>
                </h2>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success mb-3">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
                @endif

                <div class="form-group jf-search">
                    <label class="form-label" for="featuredSearch">Search the catalogue</label>
                    <input type="text"
                           class="form-control"
                           id="featuredSearch"
                           placeholder="Type a movie or series title…"
                           autocomplete="off"
                           role="combobox"
                           aria-expanded="false"
                           aria-controls="featuredResults"
                           aria-describedby="featuredSearchHelp">
                    <span class="jf-spinner" id="featuredSpinner" hidden></span>
                    <div class="jf-results" id="featuredResults" role="listbox" hidden></div>
                    <small id="featuredSearchHelp" class="text-muted d-block mt-1">
                        Type at least two letters. Arrow keys and Enter pick without the mouse.
                        The first title in the list is the big homepage banner; the rest fill the rail beside it.
                    </small>
                </div>

                {{-- A plain form post, so adding behaves identically whether it
                     was triggered by mouse, keyboard or a blocked script. --}}
                <form method="POST" action="{{ route('admin.featured.store') }}" id="featuredAddForm" class="d-none">
                    @csrf
                    <input type="hidden" name="type" id="featuredType">
                    <input type="hidden" name="id" id="featuredId">
                </form>
            </div>
        </div>
    </div>

    {{-- List column. --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="pb-3">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative">Featured</span>
                    </h2>
                    <span class="jf-status" id="featuredStatus" role="status" aria-live="polite"></span>
                </div>
                <div class="card-body">
                    <div class="table-view table-space">
                        {{-- data-ordering/paging off: rows follow sort_order and are
                             rearranged by drag — the table must not re-sort them or
                             split them across pages. Same contract as Categories. --}}
                        <table class="data-tables table custom-table movie_table data-table-one custom-table-height"
                               data-toggle="data-table1" data-ordering="false" data-paging="false">
                            <thead>
                                <tr class="text-uppercase">
                                    <th></th>
                                    <th>#</th>
                                    <th>Poster</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>On homepage</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="featuredSortBody">
                                @forelse ($items as $item)
                                    @php
                                        // NOT $title: a Blade view shares its variables
                                        // with the layout, and the admin header renders
                                        // $title. A model there printed the whole record
                                        // as JSON across the page.
                                        $featuredTitle = $item->featurable;
                                        $isShow = $featuredTitle instanceof \Modules\Content\app\Models\Show;
                                        $published = $featuredTitle && $featuredTitle->status === 'published';
                                    @endphp
                                    <tr class="jf-row {{ $loop->first ? 'jf-row--lead' : '' }}" data-id="{{ $item->id }}">
                                        <td class="featured-drag-handle" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Drag to reorder">
                                            <i class="ph ph-dots-six-vertical fs-5" aria-hidden="true"></i>
                                        </td>
                                        <td class="jf-rank" data-rank>{{ $loop->iteration }}</td>
                                        <td>
                                            @if ($featuredTitle?->poster_url)
                                                <img class="jf-poster" src="{{ media_img($featuredTitle->poster_url, 120) }}"
                                                     alt="" loading="lazy" decoding="async">
                                            @else
                                                <span class="jf-poster d-inline-block" aria-hidden="true"></span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $featuredTitle->title ?? 'Deleted title' }}
                                            @if ($loop->first)
                                                <span class="badge bg-info-subtle text-info-emphasis">Main banner</span>
                                            @endif
                                        </td>
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
                                        <td>
                                            <div class="d-flex align-items-center list-user-action gap-2">
                                                <form method="POST" action="{{ route('admin.featured.destroy', $item) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Remove {{ addslashes($featuredTitle->title ?? 'this title') }} from the homepage hero?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-icon btn-danger-subtle rounded"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            aria-label="Remove {{ $featuredTitle->title ?? 'this title' }}" title="Remove">
                                                        <i class="ph ph-trash-simple fs-6" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="jf-empty-state text-center">
                                                <i class="ph ph-star" aria-hidden="true"></i>
                                                <p class="mt-2 mb-1" style="font-weight:500;">No titles featured yet</p>
                                                <p class="text-muted mb-0" style="font-size:13px;">
                                                    The homepage is picking the most-watched titles on its own.
                                                    Search on the left to choose the banner yourself.
                                                </p>
                                            </div>
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
     same CDN pattern as the Categories page and the footer editor. --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tbody = document.getElementById('featuredSortBody');
        var statusEl = document.getElementById('featuredStatus');
        var statusTimer = null;

        function status(text, tone) {
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.setAttribute('data-tone', tone);
            statusEl.classList.add('is-on');
            window.clearTimeout(statusTimer);
            if (tone === 'ok') {
                statusTimer = window.setTimeout(function () { statusEl.classList.remove('is-on'); }, 2200);
            }
        }

        function renumber() {
            Array.prototype.forEach.call(tbody.querySelectorAll('tr[data-id]'), function (row, i) {
                var rank = row.querySelector('[data-rank]');
                if (rank) rank.textContent = i + 1;

                // Only the top row is the banner, so the label follows the order.
                row.classList.toggle('jf-row--lead', i === 0);
                var titleCell = row.children[3];
                var badge = titleCell ? titleCell.querySelector('.badge') : null;
                if (i === 0 && titleCell && !badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge bg-info-subtle text-info-emphasis';
                    badge.textContent = 'Main banner';
                    titleCell.appendChild(badge);
                } else if (i !== 0 && badge) {
                    badge.remove();
                }
            });
        }

        /* ---- Reorder ------------------------------------------------ */
        if (tbody && window.Sortable && tbody.querySelector('tr[data-id]')) {
            new Sortable(tbody, {
                handle: '.featured-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    renumber();
                    status('Saving order…', 'ok');

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
                        status('Order saved', 'ok');
                        if (evt.item) {
                            evt.item.classList.remove('is-saved');
                            void evt.item.offsetWidth; // restart the settle
                            evt.item.classList.add('is-saved');
                        }
                    }).catch(function () {
                        status('Could not save the order — reload and try again', 'bad');
                    });
                },
            });
        }

        /* ---- Catalogue type-ahead ----------------------------------- */
        var input = document.getElementById('featuredSearch');
        var results = document.getElementById('featuredResults');
        var spinner = document.getElementById('featuredSpinner');
        var form = document.getElementById('featuredAddForm');
        var typeInput = document.getElementById('featuredType');
        var idInput = document.getElementById('featuredId');

        if (!input || !results || !form) return;

        var debounce = null;
        var controller = null;
        var active = -1;

        function close() {
            results.hidden = true;
            results.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
            active = -1;
        }

        function note(text) {
            results.innerHTML = '<p class="jf-results__note"></p>';
            results.firstChild.textContent = text;
            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function add(type, id) {
            typeInput.value = type;
            idInput.value = id;
            form.submit();
        }

        function render(items, term) {
            if (!items.length) {
                note('No titles match “' + term + '”.');
                return;
            }

            results.innerHTML = '';
            items.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'jf-result';
                btn.setAttribute('role', 'option');
                btn.disabled = item.featured;

                if (item.poster) {
                    var img = document.createElement('img');
                    img.className = 'jf-result__poster';
                    img.src = item.poster;
                    img.alt = '';
                    img.loading = 'lazy';
                    btn.appendChild(img);
                } else {
                    var ph = document.createElement('span');
                    ph.className = 'jf-result__poster';
                    btn.appendChild(ph);
                }

                var body = document.createElement('span');
                body.style.minWidth = '0';

                var title = document.createElement('span');
                title.className = 'jf-result__title d-block text-truncate';
                title.textContent = item.title;
                body.appendChild(title);

                var meta = document.createElement('span');
                meta.className = 'jf-result__meta d-block';
                var bits = [item.type === 'show' ? 'Series' : 'Movie'];
                if (item.year) bits.push(item.year);
                if (item.status !== 'published') bits.push(item.status.charAt(0).toUpperCase() + item.status.slice(1));
                if (item.featured) bits.push('already featured');
                meta.textContent = bits.join(' · ');
                body.appendChild(meta);

                btn.appendChild(body);
                btn.addEventListener('click', function () { add(item.type, item.id); });
                results.appendChild(btn);
            });

            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            active = -1;
        }

        function highlight(delta) {
            var options = results.querySelectorAll('.jf-result:not(:disabled)');
            if (!options.length) return;
            if (active >= 0 && options[active]) options[active].classList.remove('is-active');
            active = (active + delta + options.length) % options.length;
            options[active].classList.add('is-active');
            options[active].scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('input', function () {
            var term = input.value.trim();
            window.clearTimeout(debounce);

            if (term.length < 2) {
                if (controller) controller.abort();
                spinner.hidden = true;
                close();
                return;
            }

            // Debounced so a fast typist sends one request, not one per key.
            debounce = window.setTimeout(function () {
                if (controller) controller.abort();
                controller = new AbortController();
                spinner.hidden = false;

                fetch('{{ route('admin.featured.search') }}?q=' + encodeURIComponent(term), {
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal,
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('search failed');
                        return res.json();
                    })
                    .then(function (data) {
                        spinner.hidden = true;
                        render(data.results || [], term);
                    })
                    .catch(function (err) {
                        if (err.name === 'AbortError') return; // superseded by a newer keystroke
                        spinner.hidden = true;
                        note('Could not search right now. Check your connection and try again.');
                    });
            }, 220);
        });

        input.addEventListener('keydown', function (e) {
            if (results.hidden) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); highlight(1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(-1); }
            else if (e.key === 'Escape') { close(); }
            else if (e.key === 'Enter') {
                var current = results.querySelector('.jf-result.is-active');
                if (current) { e.preventDefault(); current.click(); }
            }
        });

        document.addEventListener('click', function (e) {
            if (!results.hidden && !results.contains(e.target) && e.target !== input) close();
        });
    });
</script>
@endsection
