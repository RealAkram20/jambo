{{--
    Load More button that replaces numbered pagination on archive grids.

    Progressive enhancement, deliberately: the button is a REAL link to
    ?page=2, so crawlers still discover every page of the catalogue —
    a JS-only load-more would hide everything past page 1 from Google,
    undoing the point of these pages. JS intercepts the click, fetches
    the next page, moves its grid cells into the current grid, and
    adopts that page's own next-link (or hides when exhausted).

    Expects:
      $paginator    : LengthAwarePaginator
      $gridSelector : CSS selector for the grid whose children get appended
--}}
@if ($paginator->hasMorePages())
    <div class="text-center mt-4 mb-2" data-load-more-wrap>
        <a href="{{ $paginator->nextPageUrl() }}"
           class="btn btn-outline-primary px-4 py-2"
           data-load-more
           data-load-more-grid="{{ $gridSelector }}">
            <i class="ph ph-plus-circle me-2"></i>
            <span class="label">Load More</span>
        </a>
    </div>

    @once
        <script>
            document.addEventListener('click', async function (e) {
                var btn = e.target.closest('[data-load-more]');
                if (!btn) return;
                e.preventDefault();

                var wrap = btn.closest('[data-load-more-wrap]');
                var grid = document.querySelector(btn.dataset.loadMoreGrid);
                var label = btn.querySelector('.label');
                if (!grid || btn.dataset.busy) return;

                btn.dataset.busy = '1';
                if (label) label.textContent = 'Loading…';

                try {
                    var res = await fetch(btn.href, { credentials: 'same-origin' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    var doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                    var nextGrid = doc.querySelector(btn.dataset.loadMoreGrid);
                    if (nextGrid) {
                        while (nextGrid.firstElementChild) {
                            grid.appendChild(nextGrid.firstElementChild);
                        }
                    }

                    // Adopt the fetched page's next link; no link there
                    // means the catalogue is exhausted.
                    var nextBtn = doc.querySelector('[data-load-more]');
                    if (nextBtn) {
                        btn.href = nextBtn.href;
                    } else if (wrap) {
                        wrap.remove();
                    }
                } catch (err) {
                    // On any failure fall back to plain navigation — the
                    // href is a real page either way.
                    window.location.href = btn.href;
                } finally {
                    delete btn.dataset.busy;
                    if (label) label.textContent = 'Load More';
                }
            });
        </script>
    @endonce
@endif
