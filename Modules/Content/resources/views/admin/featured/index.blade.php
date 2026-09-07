@extends('layouts.app', ['module_title' => 'Featured'])

@section('content')
{{--
    Featured: the hand-picked hero of the OTT homepage.

    The screen is shaped like the thing it controls. Slot 1 is drawn as the
    banner a visitor lands on; the rest read as the poster rail beside it.
    Posters are the identifier an admin actually recognises, so they carry
    the row rather than sitting in a 48px thumbnail column.

    Honest by design: a featured title that is unpublished, or whose record
    has been deleted, is listed with a warning rather than hidden. Hiding it
    would leave nobody able to explain why the homepage looks wrong.

    Scoped styles live in this file (the pattern episode-layout-assets.blade
    already uses) so a deploy needs no Vite rebuild. Every colour is a
    Bootstrap theme variable, so the screen follows the admin light/dark
    theme instead of pinning its own.
--}}
<style>
    .jf-hero-admin {
        /* One local scale so spacing stays on a rhythm rather than ad-hoc. */
        --jf-gap: 1rem;
        --jf-radius: 12px;
        --jf-line: 1px solid rgba(var(--bs-body-color-rgb), 0.12);
        --jf-poster-w: 74px;
    }

    /* ---- Search ---------------------------------------------------- */
    .jf-search { position: relative; max-width: 560px; }

    .jf-search__field {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.65rem 0.9rem;
        border: var(--jf-line);
        border-radius: var(--jf-radius);
        background: rgba(var(--bs-body-color-rgb), 0.03);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .jf-search__field:focus-within {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.22);
    }

    .jf-search__field i { color: rgba(var(--bs-body-color-rgb), 0.55); flex: 0 0 auto; }

    .jf-search__input {
        flex: 1 1 auto;
        border: 0;
        background: transparent;
        color: var(--bs-body-color);
        font-size: 0.95rem;
        outline: none;
        min-width: 0;
    }

    .jf-search__input::placeholder { color: rgba(var(--bs-body-color-rgb), 0.62); }

    /* Spinner shares the field's right edge; hidden until a request is out. */
    .jf-search__spinner {
        width: 15px; height: 15px;
        border: 2px solid rgba(var(--bs-body-color-rgb), 0.25);
        border-top-color: var(--bs-primary);
        border-radius: 50%;
        animation: jf-spin 0.6s linear infinite;
        flex: 0 0 auto;
    }

    @keyframes jf-spin { to { transform: rotate(360deg); } }

    .jf-results {
        position: absolute;
        z-index: 20;
        top: calc(100% + 6px);
        left: 0; right: 0;
        max-height: 380px;
        overflow-y: auto;
        padding: 0.3rem;
        border: var(--jf-line);
        border-radius: var(--jf-radius);
        background: var(--bs-body-bg);
        /* Real depth: offset plus blur, not a flat halo. */
        box-shadow: 0 12px 32px -8px rgba(0, 0, 0, 0.45);
        animation: jf-drop 0.16s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes jf-drop { from { opacity: 0; transform: translateY(-4px); } }

    .jf-result {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.5rem;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--bs-body-color);
        text-align: left;
        cursor: pointer;
    }

    .jf-result:hover:not(:disabled),
    .jf-result.is-active:not(:disabled) { background: rgba(var(--bs-primary-rgb), 0.14); }

    .jf-result:disabled { opacity: 0.55; cursor: not-allowed; }

    .jf-result__poster {
        width: 34px; height: 48px;
        flex: 0 0 auto;
        object-fit: cover;
        border-radius: 4px;
        background: rgba(var(--bs-body-color-rgb), 0.08);
    }

    .jf-result__title { font-weight: 500; font-size: 0.9rem; line-height: 1.25; }
    .jf-result__meta { font-size: 0.75rem; color: rgba(var(--bs-body-color-rgb), 0.68); }
    .jf-results__note { padding: 0.9rem; font-size: 0.85rem; color: rgba(var(--bs-body-color-rgb), 0.68); }

    /* ---- The hero list --------------------------------------------- */
    /* The list is a reading column, not a full-width table: capping it keeps
       the title and its remove button in one glance instead of a metre apart
       on a wide monitor. */
    .jf-slots { max-width: 940px; }

    .jf-slot {
        display: flex;
        align-items: center;
        gap: var(--jf-gap);
        padding: 0.85rem 0;
        border-top: var(--jf-line);
    }

    .jf-slot:first-child { border-top: 0; }
    .jf-slot.sortable-ghost { opacity: 0.35; }
    .jf-slot.is-saved { animation: jf-settle 0.5s cubic-bezier(0.16, 1, 0.3, 1); }

    @keyframes jf-settle { from { background: rgba(var(--bs-primary-rgb), 0.16); } }

    .jf-slot__handle {
        flex: 0 0 auto;
        color: rgba(var(--bs-body-color-rgb), 0.52);
        cursor: grab;
        padding: 0.25rem;
        border-radius: 6px;
        background: transparent;
        border: 0;
    }

    .jf-slot__handle:hover { color: var(--bs-body-color); }
    .jf-slot__handle:active { cursor: grabbing; }

    .jf-slot__rank {
        flex: 0 0 auto;
        width: 1.75rem;
        /* Tabular figures so 1 and 10 occupy the same width while dragging. */
        font-variant-numeric: tabular-nums;
        font-size: 1.05rem;
        font-weight: 600;
        color: rgba(var(--bs-body-color-rgb), 0.72);
    }

    .jf-slot--lead .jf-slot__rank { color: var(--bs-primary); }

    .jf-slot__poster {
        width: var(--jf-poster-w);
        aspect-ratio: 2 / 3;
        flex: 0 0 auto;
        object-fit: cover;
        border-radius: 8px;
        background: rgba(var(--bs-body-color-rgb), 0.08);
    }

    /* The first slot is the full-bleed banner, not a poster in a rail —
       showing it in the banner's own 16:9 makes the difference legible. */
    .jf-slot--lead .jf-slot__poster { width: 132px; aspect-ratio: 16 / 9; }

    .jf-slot__body { flex: 1 1 auto; min-width: 0; }

    .jf-slot__title {
        font-size: 0.98rem;
        font-weight: 500;
        margin: 0 0 0.15rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .jf-slot--lead .jf-slot__title {
        font-size: 1.15rem;
        white-space: normal;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .jf-slot__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.45rem;
        font-size: 0.78rem;
        color: rgba(var(--bs-body-color-rgb), 0.62);
    }

    .jf-slot__role {
        padding: 0.1rem 0.45rem;
        border-radius: 999px;
        background: rgba(var(--bs-primary-rgb), 0.16);
        color: var(--bs-primary);
        font-weight: 600;
    }

    .jf-empty { padding: 3rem 1rem; text-align: center; }
    .jf-empty i { font-size: 2rem; color: rgba(var(--bs-body-color-rgb), 0.3); }

    /* Reorder feedback replaces a blocking alert(): it names what happened
       and, on failure, what to do about it. */
    .jf-status {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.82rem;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .jf-status.is-on { opacity: 1; }
    .jf-status[data-tone="ok"] { color: var(--bs-success); }
    .jf-status[data-tone="bad"] { color: var(--bs-danger); }

    /* Keyboard users get the same affordance as the mouse. */
    .jf-hero-admin :focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
        border-radius: 6px;
    }

    @media (prefers-reduced-motion: reduce) {
        .jf-results, .jf-slot.is-saved { animation: none; }
        .jf-search__spinner { animation-duration: 1.6s; }
    }

    @media (max-width: 575.98px) {
        .jf-hero-admin { --jf-poster-w: 54px; }
        .jf-slot--lead .jf-slot__poster { width: 96px; }
        .jf-slot { gap: 0.7rem; }
        .jf-slot__rank { width: 1.25rem; font-size: 0.9rem; }
    }
</style>

<div class="container-fluid jf-hero-admin">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h4 class="card-title mb-1">Featured on the homepage</h4>
                        <p class="mb-0 text-muted" style="font-size:13px;max-width:62ch;">
                            The first title is the big banner visitors land on. The rest fill the poster rail beside it.
                            Drag to change the order. With nothing here, the homepage picks the most-watched titles on its own.
                        </p>
                    </div>
                    <span class="jf-status" id="featuredStatus" role="status" aria-live="polite"></span>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success py-2">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger py-2">{{ session('error') }}</div>
                    @endif

                    {{-- Type-ahead over the whole catalogue. Replaces two
                         dropdowns that could only reach the newest 300 titles. --}}
                    <div class="jf-search mb-4">
                        <label class="form-label" for="featuredSearch">Add a movie or series</label>
                        <div class="jf-search__field">
                            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                            <input type="text"
                                   class="jf-search__input"
                                   id="featuredSearch"
                                   placeholder="Search the catalogue by title…"
                                   autocomplete="off"
                                   role="combobox"
                                   aria-expanded="false"
                                   aria-controls="featuredResults"
                                   aria-describedby="featuredSearchHelp">
                            <span class="jf-search__spinner" id="featuredSpinner" hidden></span>
                        </div>
                        <div id="featuredSearchHelp" class="form-text" style="font-size:12px;">
                            Type at least two letters. Use the arrow keys and Enter to pick without the mouse.
                        </div>
                        <div class="jf-results" id="featuredResults" role="listbox" hidden></div>
                    </div>

                    {{-- The add itself is a plain form post, so it works
                         identically whether it was triggered by mouse,
                         keyboard, or a browser with the script blocked. --}}
                    <form method="POST" action="{{ route('admin.featured.store') }}" id="featuredAddForm" class="d-none">
                        @csrf
                        <input type="hidden" name="type" id="featuredType">
                        <input type="hidden" name="id" id="featuredId">
                    </form>

                    <div class="jf-slots" id="featuredSortBody">
                        @forelse ($items as $item)
                            @php
                                // NOT $title: a Blade view shares its variables with
                                // the layout, and the admin header renders $title. A
                                // model there printed the whole record as JSON.
                                $featuredTitle = $item->featurable;
                                $isShow = $featuredTitle instanceof \Modules\Content\app\Models\Show;
                                $published = $featuredTitle && $featuredTitle->status === 'published';
                                $poster = $featuredTitle?->poster_url;
                                $art = $isShow || $loop->first
                                    ? ($featuredTitle?->backdrop_url ?: $poster)
                                    : $poster;
                            @endphp
                            <div class="jf-slot {{ $loop->first ? 'jf-slot--lead' : '' }}" data-id="{{ $item->id }}">
                                <button type="button" class="jf-slot__handle" aria-label="Reorder {{ $featuredTitle->title ?? 'this title' }}"
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Drag to reorder">
                                    <i class="ph ph-dots-six-vertical fs-5" aria-hidden="true"></i>
                                </button>

                                <span class="jf-slot__rank" data-rank>{{ $loop->iteration }}</span>

                                @if ($art)
                                    <img class="jf-slot__poster" src="{{ media_img($art, 320) }}" alt=""
                                         loading="lazy" decoding="async">
                                @else
                                    <span class="jf-slot__poster" aria-hidden="true"></span>
                                @endif

                                <div class="jf-slot__body">
                                    <p class="jf-slot__title">{{ $featuredTitle->title ?? 'Deleted title' }}</p>
                                    <div class="jf-slot__meta">
                                        @if ($loop->first)
                                            <span class="jf-slot__role">Main banner</span>
                                        @endif
                                        <span>{{ $isShow ? 'Series' : 'Movie' }}</span>
                                        @if ($featuredTitle?->year)
                                            <span aria-hidden="true">·</span><span>{{ $featuredTitle->year }}</span>
                                        @endif
                                        @if (!$featuredTitle)
                                            <span class="badge bg-danger">Deleted — remove this row</span>
                                        @elseif ($published)
                                            <span class="badge bg-success-subtle text-success-emphasis">Showing</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning-emphasis"
                                                  title="Only published titles appear on the site">
                                                Hidden — {{ ucfirst((string) $featuredTitle->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.featured.destroy', $item) }}"
                                      onsubmit="return confirm('Remove {{ addslashes($featuredTitle->title ?? 'this title') }} from the homepage hero?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-danger-subtle rounded"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            aria-label="Remove {{ $featuredTitle->title ?? 'this title' }}" title="Remove">
                                        <i class="ph ph-trash-simple fs-6" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="jf-empty">
                                <i class="ph ph-star" aria-hidden="true"></i>
                                <p class="mt-2 mb-1" style="font-weight:500;">No titles featured yet</p>
                                <p class="text-muted mb-0" style="font-size:13px;">
                                    The homepage is showing the most-watched titles automatically.
                                    Search above to choose the banner yourself.
                                </p>
                            </div>
                        @endforelse
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
        var list = document.getElementById('featuredSortBody');
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
            Array.prototype.forEach.call(list.querySelectorAll('.jf-slot[data-id]'), function (row, i) {
                var rank = row.querySelector('[data-rank]');
                if (rank) rank.textContent = i + 1;
                // Only the top row is the banner; the label follows the order.
                row.classList.toggle('jf-slot--lead', i === 0);
                var role = row.querySelector('.jf-slot__role');
                if (i === 0 && !role) {
                    role = document.createElement('span');
                    role.className = 'jf-slot__role';
                    role.textContent = 'Main banner';
                    row.querySelector('.jf-slot__meta').prepend(role);
                } else if (i !== 0 && role) {
                    role.remove();
                }
            });
        }

        /* ---- Reorder ------------------------------------------------ */
        if (list && window.Sortable && list.querySelector('.jf-slot[data-id]')) {
            new Sortable(list, {
                handle: '.jf-slot__handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    renumber();
                    status('Saving order…', 'ok');

                    var order = Array.prototype.map.call(
                        list.querySelectorAll('.jf-slot[data-id]'),
                        function (row) { return row.getAttribute('data-id'); }
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

        /* ---- Search ------------------------------------------------- */
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
            results.innerHTML = '<p class="jf-results__note mb-0"></p>';
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
