@extends('layouts.app', ['module_title' => 'Home Sections', 'Activelink' => 'Home Sections'])

@section('content')
{{--
    Home Sections: the order the home page renders its shelves in, on the
    website and in the app, from one list.

    Built on the same shell as the Featured screen — the same card, table
    classes, drag handle, status line and reorder request. This is the fourth
    drag list in the product; it must behave like the other three.

    Honest by design: a switched-off section is listed and dimmed, not hidden.
    Hiding it would leave nobody able to work out why a shelf stopped
    appearing. The technical key is on the row for the same reason — it is
    what support and the API both talk in.

    No prose. Every word on this screen carries a fact the controls do not.
--}}
<style>
    /* Craft inside the house shell. Every colour is a Bootstrap theme
       variable, so this follows the admin theme rather than pinning
       its own values. */

    .jhs-row { transition: background-color 0.15s ease; }
    .jhs-row:hover { background: rgba(var(--bs-primary-rgb), 0.05); }

    .jhs-rank {
        /* Tabular figures so 1 and 10 hold the same width mid-drag. */
        font-variant-numeric: tabular-nums;
        font-size: 1.05rem;
        font-weight: 600;
        color: rgba(var(--bs-body-color-rgb), 0.72);
    }

    .jhs-row--lead .jhs-rank { color: var(--bs-primary); }

    .jhs-name { font-weight: 500; }

    .jhs-key {
        display: inline-block;
        margin-top: 0.15rem;
        font-family: var(--bs-font-monospace, monospace);
        font-size: 0.74rem;
        color: rgba(var(--bs-body-color-rgb), 0.6);
        /* The admin table capitalises its cells. This one is a literal
           identifier the API and the app key on — `top_movies`, not
           `Top_movies` — so a support engineer reading it off the screen must
           read what is actually stored. */
        text-transform: none !important;
    }

    /* Same reason: a sentence should not be Title Cased Like A Heading. */
    .jhs-note { font-size: 0.76rem; color: rgba(var(--bs-body-color-rgb), 0.68); text-transform: none !important; }

    /* A hidden row reads as hidden at a glance, and says so in a word as
       well — never by colour alone. */
    .jhs-row--off .jhs-name,
    .jhs-row--off .jhs-rank { opacity: 0.55; }

    .jhs-off-flag { font-size: 0.74rem; letter-spacing: 0.04em; text-transform: uppercase; color: rgba(var(--bs-body-color-rgb), 0.6); }
    .jhs-row:not(.jhs-row--off) .jhs-off-flag { display: none; }

    /* The saved row lights briefly, then settles — the reorder confirms
       itself where it happened rather than only in the header. */
    .jhs-row.is-saved { animation: jhs-settle 0.55s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes jhs-settle { from { background: rgba(var(--bs-primary-rgb), 0.18); } }

    .jhs-drag-handle { cursor: grab; }
    .jhs-drag-handle:active { cursor: grabbing; }
    .jhs-row.sortable-ghost { opacity: 0.35; }

    /* Reorder and toggle feedback, in place of a blocking alert(): it names
       what happened and, on failure, what to do next. */
    .jhs-status { font-size: 0.82rem; opacity: 0; transition: opacity 0.2s ease; }
    .jhs-status.is-on { opacity: 1; }
    .jhs-status[data-tone="ok"] { color: var(--bs-success); }
    .jhs-status[data-tone="bad"] { color: var(--bs-danger); }

    .jhs-empty-state { padding: 2.5rem 1rem; }
    .jhs-empty-state i { font-size: 1.9rem; color: rgba(var(--bs-body-color-rgb), 0.32); }

    @media (prefers-reduced-motion: reduce) {
        .jhs-row.is-saved { animation: none; }
    }
</style>

<div class="row streamit-wraper-table2">
    <div class="col-12">
        <div class="card">
            <div class="pb-3">
                <div class="card-header d-flex justify-content-between gap-3 flex-wrap align-items-center mb-4">
                    <h2 class="episode-playlist-title wp-heading-inline">
                        <span class="position-relative">Home Sections</span>
                    </h2>
                    <span class="jhs-status" id="hsStatus" role="status" aria-live="polite"></span>
                </div>

                <div class="card-body">
                    <div class="table-view table-space">
                        {{-- data-ordering/paging off: rows follow `position` and are
                             rearranged by drag — the table must not re-sort them or
                             split them across pages. Same contract as Featured. --}}
                        <table class="data-tables table custom-table movie_table data-table-one custom-table-height"
                               data-toggle="data-table1" data-ordering="false" data-paging="false">
                            <thead>
                                <tr class="text-uppercase">
                                    <th></th>
                                    <th>#</th>
                                    <th>Section</th>
                                    <th>Website &amp; app</th>
                                </tr>
                            </thead>
                            <tbody id="hsSortBody">
                                @forelse ($sections as $section)
                                    @php
                                        // NOT $title: a Blade view shares its variables with
                                        // the layout, and the admin header renders $title.
                                        $sectionName = $section->displayTitle();
                                        $isCategoryGroup = $section->key === \Modules\Frontend\app\Models\HomeSection::CATEGORY_GROUP;
                                    @endphp
                                    <tr class="jhs-row {{ $loop->first ? 'jhs-row--lead' : '' }} {{ $section->enabled ? '' : 'jhs-row--off' }}"
                                        data-id="{{ $section->id }}">
                                        <td class="jhs-drag-handle" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Drag to reorder">
                                            <i class="ph ph-dots-six-vertical fs-5" aria-hidden="true"></i>
                                        </td>
                                        <td class="jhs-rank" data-rank>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="jhs-name d-block">{{ $sectionName }}</span>
                                            <code class="jhs-key">{{ $section->key }}</code>
                                            @if ($isCategoryGroup)
                                                <span class="jhs-note d-block mt-1">
                                                    Order within the block is set on the Categories screen.
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox"
                                                           role="switch"
                                                           id="hsToggle{{ $section->id }}"
                                                           aria-label="Show {{ $sectionName }}"
                                                           data-toggle-url="{{ route('admin.home-sections.toggle', $section) }}"
                                                           {{ $section->enabled ? 'checked' : '' }}>
                                                </div>
                                                {{-- Only the off state needs a word. "On" is what the
                                                     switch already says. --}}
                                                <span class="jhs-off-flag" data-state>Hidden</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">
                                            <div class="jhs-empty-state text-center">
                                                <i class="ph ph-rows" aria-hidden="true"></i>
                                                <p class="mt-2 mb-1" style="font-weight:500;">No sections listed</p>
                                                <p class="text-muted mb-0" style="font-size:13px;">
                                                    The home page is using the order built into the software.
                                                    Reload; if it stays empty, the home_sections table has not
                                                    been set up on this server.
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
     same CDN pattern as the Featured, Categories and footer editor screens. --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tbody = document.getElementById('hsSortBody');
        var statusEl = document.getElementById('hsStatus');
        var statusTimer = null;
        var csrf = '{{ csrf_token() }}';

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
                row.classList.toggle('jhs-row--lead', i === 0);
            });
        }

        /* ---- Reorder ------------------------------------------------ */
        if (tbody && window.Sortable && tbody.querySelector('tr[data-id]')) {
            new Sortable(tbody, {
                handle: '.jhs-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    renumber();
                    status('Saving order…', 'ok');

                    var order = Array.prototype.map.call(
                        tbody.querySelectorAll('tr[data-id]'),
                        function (tr) { return tr.getAttribute('data-id'); }
                    );

                    fetch('{{ route('admin.home-sections.reorder') }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
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

        /* ---- Show / hide a section ---------------------------------- */
        if (!tbody) return;

        Array.prototype.forEach.call(tbody.querySelectorAll('input[data-toggle-url]'), function (input) {
            input.addEventListener('change', function () {
                var row = input.closest('tr');
                var wanted = input.checked;

                input.disabled = true;

                fetch(input.getAttribute('data-toggle-url'), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ enabled: wanted }),
                }).then(function (res) {
                    if (!res.ok) throw new Error('toggle failed');
                    return res.json();
                }).then(function (data) {
                    // The server's answer wins over the checkbox, so the row
                    // shows what is stored rather than what was clicked.
                    var on = !!data.enabled;
                    input.checked = on;
                    if (row) row.classList.toggle('jhs-row--off', !on);
                    status(on ? 'Section is showing' : 'Section is hidden', 'ok');
                }).catch(function () {
                    // Nothing was saved, so the switch must go back to what
                    // is actually stored rather than lying about it.
                    input.checked = !wanted;
                    status('Could not save that change — reload and try again', 'bad');
                }).then(function () {
                    input.disabled = false;
                });
            });
        });
    });
</script>
@endsection
