/*
 * Auto-rotation for the big banner sliders.
 *
 * The Streamit template ships every slider with autoplay off — the hero's
 * `autoplay: true` is literally commented out in frontend/js/swiper.js, and
 * all 39 rails carry data-autoplay="false". That is right for the poster
 * rails and wrong for the banners: a homepage hero that never advances shows
 * only the first of the titles an admin curated under Featured, and the
 * "#N in Movies Today" and "#N in Series Today" banners never reach #2.
 *
 * This file is Jambo's, not the template's (ADR-0001: never edit vendor JS).
 * It drives the existing Swiper instances rather than re-initialising them,
 * so the template keeps ownership of every other slider setting.
 *
 * Deliberately NOT applied to the poster rails. Content that slides away
 * while someone is reaching for it is hostile, and 39 rails moving at once
 * would be unreadable.
 *
 * Pausing (WCAG 2.2.2 — moving content must be stoppable):
 *   - hover or keyboard focus anywhere inside the banner
 *   - the browser tab is hidden
 *   - the viewer swipes or uses the arrows, which pauses for longer
 *   - prefers-reduced-motion: reduce disables it outright
 */
(function () {
    'use strict';

    // selector → milliseconds between slides. The hero carries the most copy
    // (title, synopsis, tags, genres, cast) so it holds longest.
    var BANNERS = [
        ['[data-swiper="slider-images-inner-ott"]', 8000], // homepage hero
        ['[data-swiper="slider-images-inner"]', 7000],     // "#N in Movies Today"
        ['[data-swiper="banner-detail-slider"]', 7000],    // /movie, /series, VJ pages
    ];

    // How long to hold off after the viewer drives the slider themselves.
    var INTERACTION_PAUSE_MS = 20000;

    function reducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function rotate(el, delay) {
        var swiper = el.swiper;
        if (!swiper) return; // Swiper 7 assigns this; nothing to drive without it

        // One slide is not a carousel. Loop mode duplicates slides, so count
        // the real ones.
        var realSlides = swiper.slides ? swiper.slides.length : 0;
        if (swiper.params && swiper.params.loop && swiper.loopedSlides) {
            realSlides = realSlides - (swiper.loopedSlides * 2);
        }
        if (realSlides < 2) return;

        var timer = null;
        var holdUntil = 0;
        var paused = false;

        function step() {
            if (paused || document.hidden || Date.now() < holdUntil) return;

            if (swiper.params && swiper.params.loop) {
                swiper.slideNext();
            } else if (swiper.isEnd) {
                swiper.slideTo(0); // wrap by hand when the template disabled loop
            } else {
                swiper.slideNext();
            }
        }

        function start() {
            if (timer) return;
            timer = window.setInterval(step, delay);
        }

        function stop() {
            window.clearInterval(timer);
            timer = null;
        }

        // The banner is the swiper element itself; hovering or tabbing into it
        // means someone is reading or about to click.
        el.addEventListener('mouseenter', function () { paused = true; });
        el.addEventListener('mouseleave', function () { paused = false; });
        el.addEventListener('focusin', function () { paused = true; });
        el.addEventListener('focusout', function () { paused = false; });

        // A viewer who takes control keeps it for a while.
        function hold() { holdUntil = Date.now() + INTERACTION_PAUSE_MS; }
        swiper.on('touchStart', hold);
        swiper.on('navigationNext', hold);
        swiper.on('navigationPrev', hold);

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { stop(); } else { start(); }
        });

        start();
    }

    function init() {
        if (reducedMotion()) return;

        BANNERS.forEach(function (entry) {
            document.querySelectorAll(entry[0]).forEach(function (el) {
                rotate(el, entry[1]);
            });
        });

        // Opt-in marker for banners whose selector is not unique — the
        // trending tab slider is a .swiper-card like every poster rail.
        document.querySelectorAll('[data-jambo-rotate]').forEach(function (el) {
            var delay = parseInt(el.getAttribute('data-jambo-rotate'), 10);
            rotate(el, isNaN(delay) ? 7000 : delay);
        });
    }

    // swiper.js is deferred, so its instances exist by DOMContentLoaded; the
    // extra tick covers the template's own late re-inits on theme change.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { window.setTimeout(init, 300); });
    } else {
        window.setTimeout(init, 300);
    }
})();
