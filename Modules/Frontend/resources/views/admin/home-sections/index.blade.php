@extends('layouts.app', ['module_title' => 'Home Sections', 'Activelink' => 'Home Sections'])

@section('content')
{{--
    Home Sections: the order the app's home screen renders its shelves in.

    Deliberately built on the same shell as the Featured screen — the same
    card, the same table classes, the same drag handle, the same status line
    and the same reorder request. An admin screen that invents its own layout
    costs more to learn than it gains in polish, and this is the fourth drag
    list in the product: it must behave like the other three.

    Honest by design: a switched-off section is listed and labelled, not
    hidden. Hiding it would leave nobody able to work out why a shelf stopped
    appearing on the app. The technical key is on the row for the same
    reason — it is what support and the API both talk in.

    Scope: the APP's home screen. The website's homepage sections are a
    different, non-matching vocabulary in Streamit template Blade and are
    deliberately untouched — see docs/plans/homepage-section-arrangement.md.
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
    }

    .jhs-note { font-size: 0.76rem; color: rgba(var(--bs-body-color-rgb), 0.68); }

    /* A hidden row reads as hidden at a glance, and still says so in words
       and with an icon — never by colour alone. */
    .jhs-row--off .jhs-name,
    .jhs-row--off .jhs-rank { opacity: 0.55; }

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
                    <p class="jhs-note mb-4" style="max-width: 62ch;">
                        Drag a row to move that shelf up or down the app's home screen. The switch takes a
                        shelf off the home screen without deleting anything, so you can put it back later.
                        Changes are live the next time the app loads its home screen &mdash; there is nothing
                        to publish.
                    </p>

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
                                    <th>On the app</th>
                                    <th>Status</th>
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
                                                    All category shelves move together. Their order among
                                                    themselves is set on the Categories screen.
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox"
                                                       role="switch"
                                                       id="hsToggle{{ $section->id }}"
                                                       data-toggle-url="{{ route('admin.home-sections.toggle', $section) }}"
                                                       {{ $section->enabled ? 'checked' : '' }}>
                                                <label class="form-check-label jhs-note" for="hsToggle{{ $section->id }}">
                                                    Show &ldquo;{{ $sectionName }}&rdquo; on the app
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            {{-- Status carries an icon and words, never colour alone. --}}
                                            <span class="badge {{ $section->enabled ? 'bg-success' : 'bg-secondary' }}" data-state>
                                                <i class="ph {{ $section->enabled ? 'ph-eye' : 'ph-eye-slash' }}"
                                                   aria-hidden="true"></i>
                                                {{ $section->enabled ? 'Showing' : 'Hidden' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="jhs-empty-state text-center">
                                                <i class="ph ph-rows" aria-hidden="true"></i>
                                                <p class="mt-2 mb-1" style="font-weight:500;">No sections listed</p>
                                                <p class="text-muted mb-0" style="font-size:13px;">
                                                    The app is using the order built into the software. Reload this
                                                    page; if it stays empty, the home_sections table has not been
                                                    set up on this server.
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
                var badge = row ? row.querySelector('[data-state]') : null;
                var wanted = input.checked;

                input.disabled = true;
                status(wanted ? 'Showing this section…' : 'Hiding this section…', 'ok');

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
                    if (badge) {
                        badge.className = 'badge ' + (on ? 'bg-success' : 'bg-secondary');
                        badge.innerHTML = '';
                        var icon = document.createElement('i');
                        icon.className = 'ph ' + (on ? 'ph-eye' : 'ph-eye-slash');
                        icon.setAttribute('aria-hidden', 'true');
                        badge.appendChild(icon);
                        badge.appendChild(document.createTextNode(' ' + (on ? 'Showing' : 'Hidden')));
                    }
                    status(on ? 'Section is showing on the app' : 'Section is hidden from the app', 'ok');
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
