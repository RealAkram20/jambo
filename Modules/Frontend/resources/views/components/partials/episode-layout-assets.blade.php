{{--
    Shared CSS + JS for the Episodes section layout toggle (card
    scroller vs compact number grid). Used by both the episode watch
    page and the series detail page — include ONCE per page, after the
    episodes markup.

    Page markup contract:
      - toggle buttons carry data-ep-layout="scroller|grid"
      - each season pane wraps its swiper in .jambo-ep-scroller and
        renders a sibling .jambo-ep-grid (server-hidden per the
        episode_layout_default setting; see $epDefaultLayout)
      - the playing episode's grid tile (episode page only) carries
        .is-playing

    Site-wide default comes from Admin → Settings → General
    (episode_layout_default); a viewer's own toggle choice persists in
    localStorage (jambo.episodeLayout) and wins client-side.

    Inlined (like the footer's scoped CSS) so deploys don't need a
    Vite rebuild to pick up changes.
--}}
<style>
    .jambo-ep-layout-toggle .btn {
        color: rgba(255, 255, 255, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        font-size: 17px;
        line-height: 1;
        padding: 7px 12px;
    }
    .jambo-ep-layout-toggle .btn.active {
        color: #fff;
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    .jambo-ep-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(86px, 1fr));
        gap: 12px;
    }
    /* Load-bearing: .jambo-ep-grid's class display beats a bare
       [hidden] UA rule, so restate it at equal-or-higher specificity. */
    .jambo-ep-grid[hidden] {
        display: none;
    }
    .jambo-ep-grid__item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
        padding: 14px 6px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.02);
        color: #fff;
        text-decoration: none;
        transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
    }
    .jambo-ep-grid__item:hover,
    .jambo-ep-grid__item:focus {
        color: #fff;
        border-color: var(--bs-primary);
        background: rgba(255, 255, 255, 0.06);
        transform: translateY(-2px);
    }
    .jambo-ep-grid__item.is-playing {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }
    .jambo-ep-grid__label {
        font-size: 11px;
        letter-spacing: 0.08em;
        color: var(--bs-primary);
        font-weight: 600;
    }
    .jambo-ep-grid__item.is-playing .jambo-ep-grid__label { color: rgba(255, 255, 255, 0.85); }
    .jambo-ep-grid__num {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.1;
    }
    .jambo-ep-grid__playing {
        position: absolute;
        top: 6px;
        left: 8px;
        font-size: 18px;
    }
</style>
<script>
(function () {
    var KEY = 'jambo.episodeLayout';
    var serverDefault = @json(setting('episode_layout_default', 'scroller') === 'grid' ? 'grid' : 'scroller');
    var buttons = document.querySelectorAll('[data-ep-layout]');
    if (!buttons.length) return;

    function apply(layout) {
        document.querySelectorAll('.jambo-ep-scroller').forEach(function (el) {
            el.hidden = layout === 'grid';
        });
        document.querySelectorAll('.jambo-ep-grid').forEach(function (el) {
            el.hidden = layout !== 'grid';
        });
        buttons.forEach(function (btn) {
            var on = btn.dataset.epLayout === layout;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        // Swiper measures 0 while hidden; poke it after re-showing.
        if (layout !== 'grid') window.dispatchEvent(new Event('resize'));
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var layout = btn.dataset.epLayout;
            try { localStorage.setItem(KEY, layout); } catch (e) {}
            apply(layout);
            // With 90+ episodes the playing one can sit way below the
            // fold of the grid — nudge it into view on switch. Never on
            // page load: that would scroll the viewer away from the
            // player they came to watch. (No-op on the detail page,
            // which has no playing tile.)
            if (layout === 'grid') {
                var playing = document.querySelector('.tab-pane.active .jambo-ep-grid__item.is-playing');
                if (playing && playing.scrollIntoView) {
                    playing.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            }
        });
    });

    // The admin-chosen default is already server-rendered (no flash).
    // A viewer's own explicit choice, saved on a previous page, wins.
    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    if ((saved === 'grid' || saved === 'scroller') && saved !== serverDefault) {
        apply(saved);
    }
})();
</script>
