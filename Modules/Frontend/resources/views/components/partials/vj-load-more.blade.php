{{--
    "Load More VJs" wiring for pages built out of vj-carousel rows.

    A block is a `[data-vj-list="<key>"]` container paired with a
    `[data-vj-more="<key>"]` button. Pairing by key (rather than by id,
    which is what /movie, /series and the genre VJ pages each used to do
    with their own copy of this script) lets one page drive several
    independent lists — the taxonomy archives run a Movies list and a
    Series list side by side.

    The endpoint returns server-rendered vj-carousel HTML, so the card
    markup stays in the Blade partial and none of it is duplicated here.

    Include once per page, after the blocks.
--}}
<script>
(function () {
    // Swiper's bootstrap (swiper.js) only walks the markup present at
    // DOMContentLoaded, so rows appended later have to be initialised by
    // hand. These breakpoints mirror that bootstrap's — otherwise a row
    // that arrives via Load More shows a different number of cards than
    // the rows above it.
    function initCardSwipers(root) {
        if (typeof window.Swiper !== 'function') return;
        root.querySelectorAll('.swiper.swiper-card').forEach(function (el) {
            if (el.swiper) return; // already wired
            var d = function (k) { return el.getAttribute('data-' + k); };
            var num = function (v, fb) { var n = parseFloat(v); return isFinite(n) ? n : fb; };
            new Swiper(el, {
                slidesPerView: num(d('slide'), 4),
                spaceBetween: 0,
                loop: d('loop') === 'true',
                navigation: {
                    nextEl: el.querySelector('.swiper-button-next'),
                    prevEl: el.querySelector('.swiper-button-prev'),
                },
                pagination: d('pagination') === 'true'
                    ? { el: el.querySelector('.swiper-pagination'), clickable: true }
                    : false,
                breakpoints: {
                    0:    { slidesPerView: num(d('mobile-sm'), 2) },
                    576:  { slidesPerView: num(d('mobile'),    2) },
                    768:  { slidesPerView: num(d('tab'),       3) },
                    1025: { slidesPerView: num(d('laptop'),    5) },
                    1500: { slidesPerView: num(d('slide'),     7) },
                },
            });
        });
    }

    document.querySelectorAll('[data-vj-more]').forEach(function (btn) {
        var list = document.querySelector('[data-vj-list="' + btn.dataset.vjMore + '"]');
        if (!list) return;
        var label = btn.querySelector('.label');

        btn.addEventListener('click', async function () {
            var offset = parseInt(list.dataset.offset || '0', 10);
            var total  = parseInt(list.dataset.total  || '0', 10);
            if (offset >= total) return;

            btn.disabled = true;
            var originalLabel = label.textContent;
            label.textContent = 'Loading…';

            try {
                // The taxonomy archives carry ?kind= on the endpoint already.
                var sep = btn.dataset.endpoint.indexOf('?') === -1 ? '?' : '&';
                var res = await fetch(btn.dataset.endpoint + sep + 'offset=' + offset + '&limit=5', {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);

                var html = await res.text();
                var holder = document.createElement('div');
                holder.innerHTML = html;
                var appended = [];
                while (holder.firstChild) {
                    var node = holder.firstChild;
                    list.appendChild(node);
                    if (node.nodeType === 1) appended.push(node);
                }

                // Count real VJ rows only — the whitespace text nodes between
                // them would otherwise inflate the offset and skip VJs.
                var newCount = appended.filter(function (n) {
                    return n.classList && n.classList.contains('jambo-vj-row');
                }).length;
                list.dataset.offset = (offset + newCount).toString();

                appended.forEach(initCardSwipers);

                if (parseInt(list.dataset.offset, 10) >= total
                    || res.headers.get('X-Has-More') === '0') {
                    btn.parentElement.style.display = 'none';
                } else {
                    btn.disabled = false;
                    label.textContent = originalLabel;
                }
            } catch (e) {
                console.warn('[vj-load-more]', e);
                btn.disabled = false;
                label.textContent = originalLabel;
            }
        });
    });
})();
</script>
