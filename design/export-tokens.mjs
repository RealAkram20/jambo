// export-tokens.mjs — the Streamit design system, read off the rendered site.
//
//   node design/export-tokens.mjs                      (live site, the default)
//   node design/export-tokens.mjs --base=http://127.0.0.1:8090
//   node design/export-tokens.mjs --keep-going         (write despite misses)
//
// The player's tokens need a SIGNED-IN capture, because the watch page is
// behind login plus a subscription:
//
//   node design/export-tokens.mjs --base=http://127.0.0.1:8090 \
//        --login=someone@example.test --password=...
//   JAMBO_EXPORT_EMAIL=... JAMBO_EXPORT_PASSWORD=... node design/export-tokens.mjs
//   ... --watch=/watch/some-slug                       (skip discovery)
//
// Credentials are never written to tokens.json and there is no default
// account. A run without them keeps the previously exported player tokens and
// says so, rather than silently emitting a file with the player block gone.
//
// Writes two files from one run, so they cannot drift apart:
//   design/tokens.json          the record, every token carrying its provenance
//   mobile/src/ui/tokens.json   the values alone (generated; do not edit)
//
// The second lives inside mobile/ because EAS uploads only that directory, and
// it is JSON rather than TypeScript because two different things read it:
// Metro, through `src/ui/tokens.ts`, and Node, in `app.config.ts` — and Expo's
// config loader transpiles the config file but not the TypeScript it imports.
//
// WHY A BROWSER, AND NOT THE SCSS
//
// The Streamit bundle ships four skins and Jambo runs one of them. Reading
// `_variables.scss` would give the right value about a quarter of the time and
// no way to tell which quarter. The rendered page has exactly one answer, and
// `Modules/Frontend/resources/views/layouts/master.blade.php` builds it from
// two sources — a Vite SCSS bundle and `public/frontend/css/jambo-header.css`
// (ADR-0001) — which only a browser resolves in the right order.
//
// WHY A PHONE VIEWPORT
//
// Capture happens at 390x844, the phone metrics, not at desktop width. Colours
// do not care, but every size does: Streamit's own mobile breakpoints define
// the phone layout (plan §6), so a font-size read at 1920px is the wrong
// number to hand a handset. Sizes are captured again at tablet width and kept
// in `raw` for reference; the canonical set is the phone one.
//
// WHY NOT D:\OS\tools\cdp-shots.mjs
//
// That tool captures pixels and this one captures values, and a repository
// script must not depend on an absolute path on one machine. The DevTools
// boilerplate below is deliberately the same shape as that tool's, so a fix to
// one is a legible fix to the other.
//
// No npm dependencies: Node 22+ built-in WebSocket and fetch, headless Edge.
import { spawn } from 'node:child_process';
import { writeFileSync, mkdirSync, rmSync, existsSync, readFileSync, readdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const REPO = resolve(HERE, '..');

const args = Object.fromEntries(
  process.argv.slice(2).map((a) => {
    const [k, v] = a.replace(/^--/, '').split('=');
    return [k, v ?? true];
  }),
);

const BASE = (args.base ?? 'https://jambofilms.com').toString().replace(/\/$/, '');
const KEEP_GOING = args['keep-going'] === true;

/*
 * Credentials for the signed-in capture. Flags for a one-off, environment
 * variables for anything repeated, and NEITHER is ever written to disk: the
 * provenance strings below record which page a token came from, never who
 * fetched it.
 *
 * There is no default account and there must not be one. A password in a
 * repository script is a password in the repository.
 */
const LOGIN_EMAIL = (args.login ?? process.env.JAMBO_EXPORT_EMAIL ?? '').toString();
const LOGIN_PASSWORD = (args.password ?? process.env.JAMBO_EXPORT_PASSWORD ?? '').toString();
const CAN_SIGN_IN = LOGIN_EMAIL !== '' && LOGIN_PASSWORD !== '';

/**
 * Four public pages, and one behind the paywall.
 *
 * The watch page sits behind login PLUS a subscription, which is why this
 * script grew a sign-in step for the player slice — the shape is the one in
 * `D:\OS\tools\cdp-shots.mjs`. Its path is not written down here because a
 * slug hard-coded into a repository script is a slug that rots the first time
 * the catalogue changes; `discoverWatchPath()` finds one after signing in, and
 * `--watch=/watch/some-slug` overrides it.
 *
 * A run with no credentials skips it and CARRIES THE PREVIOUS PLAYER TOKENS
 * FORWARD rather than dropping them (see `carriedForward` at the output). The
 * alternative is worse than it looks: silently emitting a tokens file with no
 * player block would leave `tokens:check` perfectly green, because that check
 * compares the two generated files against each other and not against the
 * site. Both would be consistently wrong.
 */
const PAGES = [
  { key: 'home', path: '/' },
  { key: 'movies', path: '/movie' },
  { key: 'pricing', path: '/pricing' },
  { key: 'login', path: '/login' },
  { key: 'watch', path: null, auth: true },
  /*
   * The profile hub's billing page, which is the only place the account
   * screens' own components render: the hub card, the status badges and the
   * invoice's dark table all come from CSS written INLINE in
   * `profile-hub/_layout.blade.php`, so they do not exist on any public page
   * and cannot be read by injecting a `.badge` into the home page. They have
   * to be captured where they live.
   *
   * Added 2026-09-09 for Rio's ruling that the app reuses the webapp's own
   * assets at their exact design, behaviour and size. Before it, the app's
   * order badge was approximated from semantic colour tokens and drew an
   * outline where the site paints a solid fill.
   */
  { key: 'hub', path: null, auth: true },
  /*
   * The hub's own notification inbox.
   *
   * A third signed-in page rather than more probes on `hub`, because
   * `.jambo-hub-inbox__row` and `.jambo-pref-row` are declared in a `<style>`
   * block inside `profile-hub/notifications.blade.php`. They exist on that one
   * URL and nowhere else, so injecting them into the billing page reads the
   * page's defaults and calls them the row.
   *
   * Added 2026-09-09 for the app's notification screen. Its rows are injected
   * rather than selected: whether this page renders any depends on what the
   * capture account happens to have been sent, and a fresh account has none.
   */
  { key: 'inbox', path: null, auth: true },
];

/** Page keys whose tokens survive a run that could not sign in. */
const AUTH_PAGE_KEYS = new Set(PAGES.filter((p) => p.auth).map((p) => p.key));

const VIEWPORTS = {
  phone: { width: 390, height: 844, scale: 3, mobile: true },
  tablet: { width: 800, height: 1280, scale: 2, mobile: true },
};

/**
 * What to read, and where from.
 *
 * Each probe lists candidate selectors rather than one, because these class
 * names are Iqonic's and this script must not pretend to know them better than
 * the rendered page does. The first selector that matches wins and its name is
 * written into `tokens.json` beside the value, so every token in that file can
 * be traced back to the element it came from.
 */
const RADIUS = 'border-top-left-radius'; // the shorthand computes to '' in Chromium

const PROBES = [
  { name: 'body', selectors: ['body'], props: ['background-color', 'color', 'font-family', 'font-size', 'line-height'] },
  { name: 'heroTitle', selectors: ['h1', '.main-title', '.section-title'], props: ['font-family', 'font-size', 'font-weight', 'line-height', 'color'] },
  { name: 'railHeading', selectors: ['.main-title', 'h4.main-title', '.section-title'], props: ['font-size', 'font-weight', 'line-height', 'margin-bottom', 'color'] },
  { name: 'primaryButton', selectors: ['.btn-primary', '.iq-button', '.btn.btn-hover', 'a.btn', 'button.btn'], props: ['background-color', 'color', RADIUS, 'font-size', 'font-weight', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right', 'min-height'] },
  { name: 'poster', selectors: ['.block-images img', '.swiper-slide img', '.block-images'], props: ['background-color', RADIUS] },
  { name: 'link', selectors: ['main a', '.container a', 'a'], props: ['color'] },
  { name: 'header', selectors: ['#main-header', 'header', '.jambo-header'], props: ['background-color', 'height'] },

  /*
   * The home rails and the cards in them (slice 2b).
   *
   * WHY THE HOME RAIL RHYTHM IS NOT `--jambo-rail-gap`. That custom property
   * is already captured as `space.railGap`, and it is the wrong number for
   * this screen: jambo-header.css scopes it to `.jambo-detail-rails`, the
   * wrapper the detail, watch and episode pages carry and the home page does
   * not. Home keeps the vendor's own 3.75em between rails. Reusing the token
   * that happens to exist would have made the app's home screen 24px where
   * the website's is 60 — a value that is honest about a different page.
   *
   * Likewise the heading gap. §6.1 of the plan records 24px from a desktop
   * capture; the markup is `mb-2 pb-1 mb-md-4 pb-md-0`, so a phone gets 12
   * and only a tablet gets 24. That is what a phone viewport is for.
   */
  { name: 'railSection', selectors: ['.latest-block.section-wraper', '.section-wraper', '.card-style-slider'], props: ['margin-bottom', 'padding-bottom'] },
  { name: 'railSwiper', selectors: ['.card-style-slider .swiper-card', '.swiper-card', '.section-wraper .swiper'], props: ['margin-bottom', 'padding-bottom'] },
  { name: 'railHeadingRow', selectors: ['.section-wraper .d-flex.justify-content-between', '.section-wraper > .d-flex'], props: ['margin-bottom', 'padding-bottom'] },
  { name: 'railViewAll', selectors: ['.iq-view-all'], props: ['color', 'font-size', 'font-weight'] },

  /*
   * The poster card. `.swiper-slide` width is the used value the browser
   * computed from Streamit's own `data-mobile="3.5"` — three and a half cards
   * across a phone, the half being the peek that says the rail scrolls. It is
   * read rather than divided out by hand so the app cannot disagree with the
   * site about how wide a poster is.
   */
  // Swiper is configured `spaceBetween: 0` at every breakpoint
  // (public/frontend/js/swiper.js), so the visible gutter between posters is
  // the slide's own padding, not a margin. Both are read; whichever is real
  // is what the app spaces its rail by.
  { name: 'posterSlide', selectors: ['.swiper-card .swiper-slide', '.card-style-slider .swiper-slide', '.swiper-slide'], props: ['width', 'margin-right', 'padding-left', 'padding-right'] },
  { name: 'railContainer', selectors: ['.card-style-slider', '.section-wraper'], props: ['padding-left', 'padding-right'] },
  { name: 'posterImage', selectors: ['.iq-card .block-images img', '.block-images img'], props: ['width', 'height', RADIUS] },
  { name: 'cardTitle', selectors: ['.iq-card .iq-title', '.iq-title'], props: ['font-size', 'font-weight', 'color', 'line-height'] },
  { name: 'cardMeta', selectors: ['.iq-card .cart-content small', '.cart-content small', '.iq-card small'], props: ['font-size', 'color'] },
  { name: 'premiumBadge', selectors: ['.premium-product'], props: ['background-color', 'width', 'height', RADIUS] },
  // The crown's own colour, not the badge div's. They happen to be the same
  // here (the icon inherits) but reading the element that draws the glyph is
  // the only version of this that stays right if Streamit ever styles the `i`.
  { name: 'premiumBadgeIcon', selectors: ['.premium-product i', '.premium-product'], props: ['color', 'font-size'] },

  /*
   * The Top 10 numeral. Streamit draws it as a texture-filled outline rather
   * than a solid colour, so the fill lives in `background-image` and the
   * outline in `-webkit-text-stroke-*`. All three are captured: an app that
   * read `color` alone would get `transparent` and draw nothing.
   */
  { name: 'topTenNumber', selectors: ['.iq-top-ten-block .top-ten-numbers', '.top-ten-numbers'], props: ['font-size', 'font-weight', 'line-height', 'color', '-webkit-text-stroke-color', '-webkit-text-stroke-width', 'background-image'] },

  /* The hero headline, which is `.big-font`, not the `h1` the heroTitle probe finds. */
  { name: 'heroHeadline', selectors: ['.slider-content .big-font', '.banner-bg .big-font', '.big-font'], props: ['font-size', 'font-weight', 'line-height', 'letter-spacing', 'color'] },

  /*
   * Continue Watching. A guest home page has no such rail, so the progress
   * bar and its overlay are injected — the same trick the auth alert uses,
   * and for the same reason: the page's own stylesheet is asked what the
   * state looks like instead of a hex being copied out of the SCSS by hand.
   */
  { name: 'progressTrack', inject: { className: 'progress' }, props: ['background-color', RADIUS] },
  { name: 'progressBar', inject: { className: 'progress-bar', wrap: 'progress' }, props: ['background-color'] },
  /*
   * The Continue Watching card is NOT a poster. Streamit gives it
   * `aspect-ratio: 5/3` and lays the still over a dark gradient on
   * `.iq-image-box`, which is where the readable text at the bottom comes
   * from — `.iq-preogress` itself has no background at all, so probing it was
   * asking the wrong element. The full ancestry is injected because a guest
   * home page has no Continue Watching rail to read.
   */
  { name: 'watchingBox', inject: { className: 'iq-image-box', wrap: ['iq-watching-block', 'block-images'], parent: 'body' }, props: ['background-image', RADIUS] },
  { name: 'watchingStill', inject: { className: 'x', wrap: ['iq-watching-block', 'block-images', 'iq-image-box'], parent: 'body', tag: 'img' }, props: ['aspect-ratio', 'mix-blend-mode', 'object-fit'] },

  /*
   * The phone bottom navigation — `.jambo-mobile-nav`, which the website
   * renders below 992px and the app reproduces as its tab bar. Captured at
   * the phone viewport where it is actually displayed; the tablet capture
   * reads the same values through `display: none`, which is harmless.
   */
  { name: 'tabBar', selectors: ['.jambo-mobile-nav'], props: ['background-color', 'border-top-color', 'border-top-width', 'padding-top', 'padding-left'] },
  // `:not(.is-active)` is load-bearing. The first `.jambo-mobile-nav__item` in
  // the document is the Home tab, and on the home page that tab is active — so
  // the bare selector handed back #ffffff and the app's idle and active tabs
  // were the same colour. The site's real idle is rgba(255,255,255,0.65).
  { name: 'tabItem', selectors: ['.jambo-mobile-nav__item:not(.is-active)'], props: ['color', RADIUS, 'padding-top', 'padding-left'] },
  // No fallback selector on purpose. `.jambo-mobile-nav__item` would match and
  // hand back the *inactive* colour, which is a plausible-looking wrong answer
  // — and the active tab is the one thing this probe exists to find. The home
  // page has Home active, so a miss here is a real miss.
  { name: 'tabItemActive', selectors: ['.jambo-mobile-nav__item.is-active'], props: ['color'] },
  { name: 'tabIcon', selectors: ['.jambo-mobile-nav__icon'], props: ['font-size'] },
  { name: 'tabLabel', selectors: ['.jambo-mobile-nav__label'], props: ['font-size', 'line-height', 'letter-spacing'] },

  /*
   * The sign-in screen's own tokens, read from the site's sign-in page.
   *
   * These are Jambo's classes rather than Streamit's — `jambo-auth-card`,
   * `jambo-field`, `jambo-auth-btn` in resources/views/auth/login.blade.php —
   * and they are the right source precisely because the app's sign-in screen
   * should look like the site's, not like a generic Streamit form. The first
   * pass of this script read `input.form-control` from the home page and got
   * the header's search box, whose right corners are square because it is
   * grouped with a button: a plausible-looking wrong answer, which is the
   * failure mode a provenance-carrying token file exists to catch.
   */
  { name: 'authCard', page: 'login', selectors: ['.jambo-auth-card'], props: ['background-color', 'color', RADIUS, 'border-color', 'border-width', 'padding-top', 'padding-left'] },
  { name: 'authField', page: 'login', selectors: ['.jambo-field input', '.jambo-field', 'input.form-control'], props: ['background-color', 'color', 'border-color', RADIUS, 'font-size', 'height', 'padding-left'] },
  { name: 'authFieldFocus', page: 'login', focus: true, selectors: ['.jambo-field input'], props: ['background-color', 'border-color'] },
  { name: 'authButton', page: 'login', selectors: ['.jambo-auth-btn'], props: ['background-color', 'color', RADIUS, 'font-size', 'font-weight', 'height', 'min-height', 'padding-top', 'padding-bottom'] },
  /*
   * The error and success states, injected because a cleanly loaded sign-in
   * page has neither. This is where the app's error colour comes from, and it
   * has to: the site's `--bs-danger` is #545e75, a slate blue, so the Bootstrap
   * token that every other project would reach for is unusable here.
   */
  { name: 'authAlert', page: 'login', inject: { className: 'jambo-auth-alert', parent: '.jambo-auth-card' }, props: ['background-color', 'color', 'border-color', RADIUS, 'font-size'] },
  { name: 'authAlertSuccess', page: 'login', inject: { className: 'jambo-auth-alert jambo-auth-alert--success', parent: '.jambo-auth-card' }, props: ['background-color', 'color', 'border-color'] },
  { name: 'authMuted', page: 'login', selectors: ['.jambo-auth-card__subtitle', '.jambo-auth-meta', '.text-muted'], props: ['color', 'font-size'] },
  { name: 'authPlaceholder', page: 'login', selectors: ['.jambo-field input'], props: ['color'], pseudo: '::placeholder' },

  /*
   * The player, read off the real watch page — which needs a signed-in
   * capture, because it sits behind login plus a subscription.
   *
   * WHY THE RENDERED PAGE IS THE ONLY HONEST SOURCE HERE, more so than for
   * any other block in this file. `public/frontend/css/player.css` styles the
   * control bar almost entirely in `currentColor` and relative `oklch()`:
   *
   *   .media-slider__track { background-color: oklch(from currentColor l c h / 0.2); }
   *   .media-slider__fill  { background-color: currentColor; }
   *
   * Reading that stylesheet tells you the track is "20% of whatever colour is
   * inherited", which is not a value an app can use. Only a browser that has
   * actually cascaded the page resolves it to a number.
   *
   * The controls are `@videojs/html`'s minimal skin (`.media-*`), which is
   * what `Pages/Movies/watch-page.blade.php` includes. The settings popover
   * is Jambo's own (`public/frontend/js/jambo-settings-menu.js`), and it is
   * where the website's Data Saver toggle lives — so the app's quality menu
   * has a real design to match rather than one invented for it.
   */
  { name: 'playerControls', page: 'watch', selectors: ['.media-minimal-skin .media-controls', '.media-controls'], props: ['background-color', 'backdrop-filter', 'color', 'padding-top', 'padding-left'] },
  { name: 'playerOverlay', page: 'watch', selectors: ['.media-minimal-skin .media-overlay', '.media-overlay'], props: ['background-color', 'background-image'] },
  { name: 'playerButton', page: 'watch', selectors: ['.media-minimal-skin .media-button', '.media-button'], props: [RADIUS, 'padding-top', 'padding-left', 'color'] },
  { name: 'playerButtonIcon', page: 'watch', selectors: ['.media-button--icon'], props: ['width', 'height', RADIUS] },
  { name: 'playerButtonPrimary', page: 'watch', selectors: ['.media-button--primary'], props: ['background-color', 'color'] },
  /*
   * The resting background of a control-bar button, which is `transparent`.
   *
   * Probed rather than assumed, because "transparent" is a design decision
   * here and not an omission: `.media-button--subtle` declares
   * `background: transparent` and only fills to 10% white on hover or focus.
   * The app's icons therefore sit directly on the scrim exactly as the
   * site's do, and the focus state comes from `Focusable`'s ring rather than
   * from a plate nobody asked for.
   */
  { name: 'playerButtonSubtle', page: 'watch', selectors: ['.media-button--subtle', '.media-button'], props: ['background-color'] },
  /*
   * `.media-slider__track` before `.media-slider`: the slider itself is a 2rem
   * hit area with no background, and probing it returns transparent — a
   * plausible-looking wrong answer of exactly the kind this file's provenance
   * strings exist to expose.
   */
  { name: 'playerSliderTrack', page: 'watch', selectors: ['.media-slider__track'], props: ['background-color', 'height', RADIUS] },
  { name: 'playerSliderFill', page: 'watch', selectors: ['.media-slider__fill'], props: ['background-color'] },
  { name: 'playerSliderBuffer', page: 'watch', selectors: ['.media-slider__buffer'], props: ['background-color'] },
  { name: 'playerSliderThumb', page: 'watch', selectors: ['.media-slider__thumb'], props: ['background-color', 'width', 'height'] },
  { name: 'playerSlider', page: 'watch', selectors: ['.media-slider'], props: ['height'] },
  { name: 'playerTime', page: 'watch', selectors: ['.media-time--current', '.media-time'], props: ['font-size', 'color', 'font-weight'] },
  { name: 'playerTimeDuration', page: 'watch', selectors: ['.media-time--duration'], props: ['color'] },
  { name: 'playerBuffering', page: 'watch', selectors: ['.media-buffering-indicator'], props: ['color', 'width', 'height'] },

  /* Jambo's own settings popover — the website's quality and Data Saver menu. */
  { name: 'playerMenu', page: 'watch', selectors: ['.jambo-settings-popover'], props: ['background-color', RADIUS, 'border-color', 'border-width', 'color', 'padding-top'] },
  { name: 'playerMenuRow', page: 'watch', selectors: ['.jambo-settings-row'], props: ['color', 'font-size', 'padding-top', 'padding-left', 'min-height'] },
  { name: 'playerMenuLabel', page: 'watch', selectors: ['.jambo-settings-row .jambo-settings-label', '.jambo-settings-label'], props: ['font-size', 'color'] },
  { name: 'playerMenuValue', page: 'watch', selectors: ['.jambo-settings-row .jambo-settings-value', '.jambo-settings-value'], props: ['color', 'font-size'] },
  { name: 'playerDataSaverDot', page: 'watch', selectors: ['.jambo-settings-row .jambo-ds-dot', '.jambo-ds-dot'], props: ['background-color', 'width', 'height'] },

  /*
   * The profile hub's own components, read off the real billing page.
   *
   * ADDED FOR RIO'S RULING, 2026-09-09: the app reuses the webapp's assets at
   * their exact design, behaviour and size, and an inspirational screen never
   * replaces one. Before this block the app's order-status badge had been
   * approximated from the semantic ok/warning tokens and drew a hollow
   * outline; the site paints a solid fill. That is the "foreign design" the
   * ruling names, and the fix is to read the real element rather than to pick
   * a nicer approximation.
   *
   * These have to come from a signed-in hub page and cannot be injected into
   * a public one: `.jambo-hub-card` is defined in a `<style>` block inside
   * `profile-hub/_layout.blade.php`, so it exists nowhere else. The badges and
   * the table are the site's global CSS, but they are read here anyway so
   * every value in the block comes from the page it is actually used on.
   *
   * The badges are INJECTED rather than selected, because which badges a hub
   * page renders depends on what the capture account has bought — a fresh
   * account has no orders and therefore no badges at all. Injecting the same
   * `class` the blade writes reads the same CSS without depending on anyone's
   * billing history.
   */
  { name: 'hubCard', page: 'hub', selectors: ['.jambo-hub-card'], props: ['background-color', 'border-color', 'border-width', RADIUS, 'padding-top', 'padding-left'] },
  { name: 'hubCardTitle', page: 'hub', selectors: ['.jambo-hub-card h5'], props: ['font-size', 'color', 'font-weight'] },
  { name: 'hubCardSubtitle', page: 'hub', selectors: ['.jambo-hub-card__subtitle'], props: ['font-size', 'color'] },

  { name: 'badgeOk', page: 'hub', inject: { className: 'badge bg-success', parent: '.jambo-hub-card' }, props: ['background-color', 'color', RADIUS, 'font-size', 'font-weight', 'padding-top', 'padding-left'] },
  { name: 'badgeWarn', page: 'hub', inject: { className: 'badge bg-warning', parent: '.jambo-hub-card' }, props: ['background-color', 'color'] },
  { name: 'badgePrimary', page: 'hub', inject: { className: 'badge bg-primary', parent: '.jambo-hub-card' }, props: ['background-color', 'color'] },

  /*
   * The invoice's line-item table. `invoice.blade.php` renders
   * `table.table-dark`, and the app had hand-built a table beside it instead
   * of wearing this one.
   */
  { name: 'hubTable', page: 'hub', inject: { className: 'table table-dark', tag: 'table', parent: '.jambo-hub-card' }, props: ['background-color', 'color', 'font-size'] },
  { name: 'hubTableCell', page: 'hub', selectors: ['.jambo-hub-card table td', '.jambo-hub-card table th'], props: ['padding-top', 'padding-left', 'border-color', 'border-width'] },
  { name: 'hubTableHead', page: 'hub', selectors: ['.jambo-hub-card thead th', 'table thead th'], props: ['color', 'font-size', 'font-weight'] },

  /*
   * ── The notification inbox ─────────────────────────────────────────
   *
   * The app's notification screen wears this row. Rio's mockup draws a larger
   * thumbnail; his ruling of 2026-09-09 was to hold the site's size exactly,
   * which is what makes capturing these worth doing rather than eyeballing
   * them from the drawing.
   *
   * Injected for the same reason the badges are: whether this page renders a
   * single row depends on what the capture account has been sent, and a fresh
   * account has an empty inbox. Injecting the class the blade writes reads the
   * same CSS without depending on anybody's notification history.
   *
   * The unread row is a separate probe because it is a separate design state,
   * and it is the one that settled a question the mockup kept raising: the
   * site already tints unread rows with the brand blue, so the mockup's
   * crimson had an answer on the site rather than needing a fourth ruling.
   */
  { name: 'inboxRow', page: 'inbox', inject: { className: 'jambo-hub-inbox__row', parent: '.jambo-hub-card' }, props: ['background-color', 'border-color', 'border-width', RADIUS, 'padding-top', 'padding-left', 'column-gap'] },
  { name: 'inboxRowUnread', page: 'inbox', inject: { className: 'jambo-hub-inbox__row is-unread', parent: '.jambo-hub-card' }, props: ['background-color', 'border-color'] },
  { name: 'inboxTile', page: 'inbox', inject: { className: 'jambo-hub-inbox__icon', parent: '.jambo-hub-card' }, props: ['width', 'height', RADIUS, 'font-size'] },
  { name: 'inboxImage', page: 'inbox', inject: { className: 'jambo-hub-inbox__image', tag: 'img', parent: '.jambo-hub-card' }, props: ['width', 'height', RADIUS, 'object-fit'] },

  /*
   * The five tile tones.
   *
   * The blade writes `bg-{colour}-subtle text-{colour}-emphasis` from the
   * notification's own `colour` field, so a movie tile and a payment tile are
   * different colours on the site. Capturing all five is what lets the app
   * tint them identically instead of painting every icon one colour.
   */
  { name: 'inboxTonePrimary', page: 'inbox', inject: { className: 'jambo-hub-inbox__icon bg-primary-subtle text-primary-emphasis', parent: '.jambo-hub-card' }, props: ['background-color', 'color'] },
  { name: 'inboxToneSuccess', page: 'inbox', inject: { className: 'jambo-hub-inbox__icon bg-success-subtle text-success-emphasis', parent: '.jambo-hub-card' }, props: ['background-color', 'color'] },
  { name: 'inboxToneWarning', page: 'inbox', inject: { className: 'jambo-hub-inbox__icon bg-warning-subtle text-warning-emphasis', parent: '.jambo-hub-card' }, props: ['background-color', 'color'] },
  { name: 'inboxToneDanger', page: 'inbox', inject: { className: 'jambo-hub-inbox__icon bg-danger-subtle text-danger-emphasis', parent: '.jambo-hub-card' }, props: ['background-color', 'color'] },
  { name: 'inboxToneInfo', page: 'inbox', inject: { className: 'jambo-hub-inbox__icon bg-info-subtle text-info-emphasis', parent: '.jambo-hub-card' }, props: ['background-color', 'color'] },

  /*
   * The row's own type scale.
   *
   * Injected into the card because the row inherits its font-size from there
   * and sets none of its own, so `fw-semibold` inside the card computes
   * exactly what the title computes inside the row.
   *
   * These three exist because the first cut of the app's row guessed them —
   * 15px title, 12px timestamp — and a guessed type scale is the same defect
   * as a guessed radius. The site's answers are 16 semibold, 14 muted, 14
   * muted, and the timestamp being the same size as the message is a real
   * design decision rather than an oversight to correct.
   */
  /*
   * ── The header, both rows ──────────────────────────────────────────
   *
   * Rio, 2026-09-10: *"let us use the exact header as it is on our web app."*
   * The app's header had a logo, a search icon and a generic account glyph;
   * the site's has a notification bell with an unread badge, a real avatar,
   * and a second row of genre chips. All of it is on the public home page, so
   * none of these need a signed-in capture.
   *
   * The chip's active state is a separate probe because it is a separate
   * design state — the same reason the inbox's unread row is.
   */
  /*
   * The brand mark, measured rather than guessed.
   *
   * The app had `{ width: 108, height: 32 }` typed into a stylesheet and Rio
   * spotted it was too small against the website. The site sets
   * `height: 36px; width: auto` and steps down at its narrow breakpoint, so
   * the honest answer is whatever it renders at a phone viewport — which is
   * what this reads. Both dimensions are captured because React Native cannot
   * do `width: auto` on an image without an intrinsic aspect ratio, and the
   * app draws it with `contentFit: contain` so a differently-shaped logo
   * letterboxes rather than stretches.
   */
  { name: 'headerLogo', selectors: ['.jambo-header__logo img'], props: ['width', 'height'] },
  { name: 'headerIcon', selectors: ['.jambo-header__icon'], props: ['color', 'font-size', 'width', 'height'] },
  { name: 'headerBadge', selectors: ['.jambo-notif-badge'], props: ['background-color', 'color', 'font-size', RADIUS, 'min-width', 'height'] },
  /*
   * The avatar is on a signed-in page, because the header only draws one for
   * an authenticated viewer — a guest gets a Sign in link in its place. The
   * first cut probed it on `/` and matched nothing, which the run reported
   * rather than silently writing a null.
   */
  { name: 'headerAvatar', page: 'hub', selectors: ['.jambo-header__avatar img', '.jambo-header__avatar'], props: ['width', 'height', RADIUS, 'border-color', 'border-width'] },
  { name: 'genresBar', selectors: ['.jambo-genres-bar'], props: ['background-color', 'border-bottom-color', 'border-bottom-width', 'padding-top', 'padding-left'] },
  /*
   * `:not(.active)` with no fallback selector, and both halves are deliberate.
   *
   * The bare `.jambo-genre-chip` matched the FIRST chip in the bar, which is
   * "All", which is active on the home page — so the resting chip captured as
   * white-on-black and the active state came back identical to it. Every genre
   * chip in the app would have been drawn as the selected one.
   *
   * Same trap as the sign-in field's focused border, and the same fix: ask for
   * the state you mean. No fallback, because a fallback here would silently
   * reintroduce the bug the day the markup changes.
   */
  { name: 'genreChip', selectors: ['.jambo-genre-chip:not(.active)'], props: ['background-color', 'color', RADIUS, 'font-size', 'font-weight', 'padding-top', 'padding-left', 'border-color', 'border-width'] },
  { name: 'genreChipActive', selectors: ['.jambo-genre-chip.active'], props: ['background-color', 'color'] },

  { name: 'inboxTitle', page: 'inbox', inject: { className: 'fw-semibold', parent: '.jambo-hub-card' }, props: ['font-size', 'font-weight', 'color'] },
  { name: 'inboxMessage', page: 'inbox', inject: { className: 'text-muted small', parent: '.jambo-hub-card' }, props: ['font-size', 'color'] },
  { name: 'inboxTime', page: 'inbox', inject: { className: 'text-muted', tag: 'small', parent: '.jambo-hub-card' }, props: ['font-size', 'color'] },

  /*
   * ── The pricing card ───────────────────────────────────────────────
   *
   * The app's Membership screen wears this card, and until 2026-09-09 it did
   * not: it drew a plan as a list row because the ladder was a placeholder.
   * Rio's mockup asks for the website's card, so §4a applies — design,
   * behaviour AND size come from the rendered page rather than from the
   * drawing.
   *
   * `/pricing` is public and renders these for a guest, so nothing here has to
   * be injected: the paid tiers are seeded and the page draws real cards.
   * The one exception is the "Most popular" ribbon, which only renders on the
   * single highlighted tier — selecting it is safe because that tier always
   * exists when any paid tier does.
   *
   * **The mockup is crimson and the brand is blue.** These probes are what
   * settle that: the card's own fill, border and price colour are captured,
   * and the app's card is drawn in them. Rio approved the same substitution
   * for the profile menu on 2026-09-09 — his layout, Jambo's colours.
   */
  { name: 'planCard', page: 'pricing', selectors: ['.pricing-plan-wrapper'], props: ['background-color', 'border-color', 'border-width', RADIUS, 'padding-top', 'padding-left'] },
  { name: 'planName', page: 'pricing', selectors: ['.pricing-plan-wrapper .plan-name'], props: ['font-size', 'font-weight', 'color'] },
  { name: 'planPrice', page: 'pricing', selectors: ['.pricing-plan-wrapper .plan-main-price'], props: ['font-size', 'font-weight', 'color'] },
  { name: 'planPeriod', page: 'pricing', selectors: ['.pricing-plan-wrapper .plan-period-time'], props: ['font-size', 'color'] },
  { name: 'planFeature', page: 'pricing', selectors: ['.pricing-plan-description li span', '.pricing-plan-description li'], props: ['font-size', 'font-weight', 'color'] },
  { name: 'planFeatureIcon', page: 'pricing', selectors: ['.pricing-plan-description li i'], props: ['font-size', 'color'] },
  { name: 'planRibbon', page: 'pricing', selectors: ['.pricing-plan-discount'], props: ['background-color', 'color', 'padding-top', 'font-size'] },
  { name: 'planCta', page: 'pricing', inject: { className: 'btn btn-primary fw-semibold rounded-3 w-100', tag: 'button', parent: '.pricing-plan-footer .iq-button' }, props: ['background-color', 'color', RADIUS, 'font-size', 'font-weight', 'padding-top', 'height'] },
  { name: 'planCtaQuiet', page: 'pricing', inject: { className: 'btn btn-outline-primary fw-semibold rounded-3 w-100', tag: 'button', parent: '.pricing-plan-footer .iq-button' }, props: ['background-color', 'color', 'border-color', 'border-width'] },

  /*
   * The billing-period tabs, which the mockup draws as a segmented control.
   *
   * Same component either way — `nav-pills` with one active pill — so the
   * active fill, the resting colour and the pill radius all come from here
   * rather than from an eye on a screenshot.
   */
  { name: 'periodTab', page: 'pricing', selectors: ['.jambo-period-tabs .nav-link'], props: ['background-color', 'color', RADIUS, 'font-size', 'font-weight', 'padding-top', 'padding-left'] },
  { name: 'periodTabActive', page: 'pricing', selectors: ['.jambo-period-tabs .nav-link.active'], props: ['background-color', 'color'] },

  /*
   * The "Current plan" strip above the grid. The app draws the same strip in
   * the same place, so it is captured rather than approximated from the hub
   * card it resembles.
   */
  { name: 'planStrip', page: 'pricing', selectors: ['.jambo-strip'], props: ['background-color', 'border-color', 'border-width', RADIUS, 'padding-top', 'padding-left'] },
];

/**
 * The tokens the app cannot start without.
 *
 * A missing one is an error rather than a default, and that is the whole point
 * of the file: a theme that silently falls back to a plausible blue is how a
 * design drifts away from the site it is supposed to match. `--keep-going`
 * exists for exploring a page, not for producing a shippable tokens.ts.
 */
const REQUIRED = ['color.primary', 'color.bg', 'color.text', 'color.surface', 'font.family'];

// ---------------------------------------------------------------- DevTools

const EDGE_CANDIDATES = [
  process.env.EDGE_PATH,
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
].filter(Boolean);

const BROWSER = EDGE_CANDIDATES.find((p) => existsSync(p));
if (!BROWSER) {
  console.error('No Edge or Chrome found. Set EDGE_PATH to the executable.');
  process.exit(1);
}

const PORT = 9334;

/*
 * A FRESH PROFILE PER RUN, and this is a correctness fix rather than tidiness.
 *
 * This used to be one fixed directory deleted at startup inside a try/catch.
 * The delete fails on Windows whenever the previous run's browser is still
 * holding the files — and it usually is, because `browser.kill()` kills the
 * process this script spawned while Edge's renderer and GPU children survive
 * it. Twenty of them were counted after two runs.
 *
 * That was harmless while every page was public. The moment this script
 * learned to sign in it stopped being harmless: run two inherited run one's
 * session cookie, so `/login` redirected to the account page, and every
 * `authCard` / `authField` probe matched nothing. The sign-in screen's design
 * would have been quietly dropped from the token file. It was caught only
 * because `color.surface` is on the REQUIRED list and comes from that page.
 *
 * A unique directory cannot be defeated by a lock. Old ones are swept
 * best-effort on the way in, so a machine does not accumulate 100MB a run.
 */
const TEMP_DIR = process.env.TEMP ?? '/tmp';
const PROFILE_PREFIX = 'jambo-tokens-profile';
const profile = resolve(TEMP_DIR, `${PROFILE_PREFIX}-${process.pid}-${Date.now()}`);

for (const stale of readdirSync(TEMP_DIR, { withFileTypes: true })) {
  if (!stale.isDirectory() || !stale.name.startsWith(PROFILE_PREFIX)) continue;
  try {
    rmSync(resolve(TEMP_DIR, stale.name), { recursive: true, force: true });
  } catch {
    // Still locked by a browser that outlived its run. The unique directory
    // above is what makes that survivable rather than silently wrong.
  }
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = spawn(BROWSER, [
  '--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-first-run', '--disable-extensions',
  `--remote-debugging-port=${PORT}`, `--user-data-dir=${profile}`, 'about:blank',
], { stdio: 'ignore' });

let wsUrl = null;
for (let i = 0; i < 60 && !wsUrl; i++) {
  try {
    const r = await fetch(`http://127.0.0.1:${PORT}/json/version`);
    wsUrl = (await r.json()).webSocketDebuggerUrl;
  } catch { await sleep(200); }
}
if (!wsUrl) { browser.kill(); throw new Error('The browser did not expose DevTools'); }

const ws = new WebSocket(wsUrl);
await new Promise((res, rej) => { ws.onopen = res; ws.onerror = rej; });

let nextId = 0;
const pending = new Map();
const events = [];
ws.onmessage = (e) => {
  const msg = JSON.parse(e.data);
  if (msg.id !== undefined && pending.has(msg.id)) {
    const { resolve: done, reject } = pending.get(msg.id);
    pending.delete(msg.id);
    msg.error ? reject(new Error(JSON.stringify(msg.error))) : done(msg.result);
  } else if (msg.method) {
    events.push(msg.method);
  }
};

function send(method, params = {}, sessionId) {
  const id = ++nextId;
  return new Promise((res, rej) => {
    pending.set(id, { resolve: res, reject: rej });
    ws.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
  });
}

const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
const call = (method, params) => send(method, params, sessionId);

await call('Page.enable');
await call('Runtime.enable');

async function goto(url) {
  events.length = 0;
  await call('Page.navigate', { url });
  for (let i = 0; i < 150; i++) {
    if (events.includes('Page.loadEventFired')) break;
    await sleep(100);
  }
  // Streamit builds its sliders after load; a beat here is the difference
  // between reading a card's real radius and reading it before Swiper has
  // laid the rail out.
  await sleep(1200);
}

async function evaluate(fn, arg) {
  const { result, exceptionDetails } = await call('Runtime.evaluate', {
    expression: `(${fn.toString()})(${JSON.stringify(arg ?? null)})`,
    returnByValue: true,
    awaitPromise: true,
  });
  if (exceptionDetails) throw new Error(exceptionDetails.text ?? 'probe threw');
  return result.value;
}

// ------------------------------------------------------------- signing in

/**
 * Sign in through the site's own form.
 *
 * Through the FORM, not by posting to the endpoint or forging a session
 * cookie, and the reason is the same reason this whole script drives a
 * browser: what is wanted is the page as a signed-in viewer's browser
 * actually renders it, cascade and all. A synthesised session would be a
 * second implementation of Laravel's auth that could drift from it.
 *
 * The CSRF token is left to the form. Reading `_token` out of the DOM and
 * posting it by hand would work today and break the first time the site adds
 * a field, whereas submitting the form submits whatever the form contains.
 */
async function signIn() {
  await goto(BASE + '/login');

  const result = await evaluate(async (creds) => {
    const form =
      document.querySelector('form[action*="login"]') ??
      document.querySelector('.jambo-auth-card form') ??
      document.querySelector('form');

    if (!form) return { ok: false, why: 'no form on /login' };

    const email = form.querySelector('input[name="email"]');
    const password = form.querySelector('input[name="password"]');

    if (!email || !password) return { ok: false, why: 'no email/password field' };

    /*
     * Set through the native value setter and dispatch `input`. Assigning
     * `.value` directly is invisible to any framework listening for the event,
     * and a form that validates on input would submit thinking both fields are
     * still empty.
     */
    const setValue = (el, value) => {
      const proto = Object.getPrototypeOf(el);
      const desc = Object.getOwnPropertyDescriptor(proto, 'value');
      desc?.set?.call(el, value);
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    setValue(email, creds.email);
    setValue(password, creds.password);

    form.submit();
    return { ok: true };
  }, { email: LOGIN_EMAIL, password: LOGIN_PASSWORD });

  if (!result.ok) return { ok: false, why: result.why };

  // form.submit() navigates, and it does not fire the load event this script's
  // goto() waits on, so the wait is here instead.
  await sleep(2500);

  const who = await evaluate(() => ({
    url: location.href,
    // The site renders the account menu only for a signed-in viewer, which is
    // a more honest test than "we are no longer on /login" — a failed sign-in
    // that redirects back to /login with an error would otherwise read as
    // success on any page that is not the login page.
    signedIn:
      document.querySelector('a[href*="/logout"], form[action*="logout"], .jambo-mobile-nav__item[href*="/watchlist"]') !== null,
    hasError: document.querySelector('.jambo-auth-alert, .invalid-feedback, .alert-danger') !== null,
  }));

  if (!who.signedIn) {
    return { ok: false, why: who.hasError ? 'the site rejected those credentials' : `still signed out at ${who.url}` };
  }

  return { ok: true, url: who.url };
}

/**
 * Find a watch page to read, rather than hard-coding a slug.
 *
 * A slug written into this file is a slug that goes stale the first time the
 * catalogue changes, and on the live site it would name one particular film
 * in a repository for no reason. `--watch=/watch/some-slug` overrides.
 */
/**
 * The signed-in viewer's own billing page.
 *
 * The hub lives at `/{username}/billing` and the username is not derivable
 * from the login email, so it is read off a link the signed-in header already
 * renders rather than written into this file. `--hub=/someone/billing`
 * overrides.
 *
 * Billing rather than any other hub tab because it is the one page carrying
 * all three components this exists to read: the hub card, both status badges,
 * and the table. It renders its empty state for an account with no orders and
 * the card and table are still there, so it does not depend on the capture
 * account having ever paid.
 */
async function discoverHubPath() {
  if (typeof args.hub === 'string') return args.hub;

  await goto(BASE + '/');

  const found = await evaluate(() => {
    const link = document.querySelector(
      'a[href*="/watchlist"], a[href*="/membership"], a[href*="/security"]',
    );
    if (!link) return null;

    // Any hub tab gives the username; billing is the tab we actually want.
    const parts = new URL(link.href).pathname.split('/').filter(Boolean);
    return parts.length >= 2 ? `/${parts[0]}/billing` : null;
  });

  return found;
}

async function discoverWatchPath() {
  if (typeof args.watch === 'string') return args.watch;

  await goto(BASE + '/movie');

  const found = await evaluate(() => {
    const direct = document.querySelector('a[href*="/watch/"]');
    if (direct) return { path: new URL(direct.href).pathname };

    const detail = document.querySelector('a[href*="/movie-detail/"]');
    if (detail) return { detail: new URL(detail.href).pathname };

    return {};
  });

  if (found.path) return found.path;
  if (!found.detail) return null;

  await goto(BASE + found.detail);

  const fromDetail = await evaluate(() => {
    const play = document.querySelector('a[href*="/watch/"]');
    return play ? new URL(play.href).pathname : null;
  });

  if (fromDetail) return fromDetail;

  /*
   * Last resort: the site's own convention, `/movie-detail/{slug}` and
   * `/watch/{slug}` (Modules/Frontend/routes/web.php). Derived rather than
   * guessed, and it still gets verified — a wrong path simply misses every
   * player probe and is reported as a miss.
   */
  const slug = found.detail.split('/').filter(Boolean).pop();
  return slug ? `/watch/${slug}` : null;
}

// -------------------------------------------------------------- the probe

/**
 * Runs inside the page. Nothing here may reference anything from this file.
 *
 * Async because state changes here are not instantaneous. `.jambo-field input`
 * carries `transition: border-color 0.15s, background 0.15s`, and
 * `getComputedStyle` during a transition returns the value the transition is
 * currently *at* — so blurring and reading in the same tick returns the
 * focused colour, and focusing and reading in the same tick returns the
 * resting one. Both wrong, both plausible, and each the exact opposite of what
 * was asked for. The settle below is the fix.
 */
async function probePage(input) {
  const { probes } = input;
  const settle = (ms) => new Promise((r) => setTimeout(r, ms));
  const TRANSITION_MS = 300; // the site's longest is 0.15s
  const out = { custom: {}, probes: {}, title: document.title, url: location.href };

  /*
   * Turn modern colour syntax into something Node can read, by RENDERING it.
   *
   * `public/frontend/css/player.css` is written almost entirely in `oklch()`
   * and relative colour — `oklch(from currentColor l c h / 0.2)` — and
   * Chromium returns those from `getComputedStyle` verbatim rather than as
   * `rgb()`. The exporter's `toHex()` understood only `#rgb` and `rgba()`, so
   * every colour in the control bar came back null and the player block was
   * silently half-empty: sizes captured, colours gone.
   *
   * Three ways to convert were tried against this browser and only one works
   * (HeadlessChrome 149 / Edge 149, measured, not assumed):
   *
   *   getComputedStyle(el).color   -> 'oklch(1 0 0)'      preserved
   *   canvas ctx.fillStyle          -> 'oklch(1 0 0)'      echoed, not normalised
   *   fillRect + getImageData       -> 255,255,255,255     CONVERTED
   *
   * So the colour is painted onto a one-pixel canvas and the pixel is read
   * back. `oklch(0.7 0.15 250)` comes out `75,163,247`, a real chromatic
   * conversion, and it is the strongest possible answer to "what colour is
   * this": not a parse, but the value the compositor actually puts on screen.
   * That keeps the browser as the single authority, which is this script's
   * whole premise — the alternative was reimplementing OKLab-to-sRGB in the
   * repository and owning a second opinion about colour that could drift from
   * the one the site displays.
   */
  const probeCanvas = document.createElement('canvas');
  probeCanvas.width = 1;
  probeCanvas.height = 1;
  const ctx = probeCanvas.getContext('2d', { willReadFrequently: true });

  const REJECT_SENTINEL = '#010203';

  const normalise = (value) => {
    if (typeof value !== 'string' || value === '') return value;
    // Single colour values only. A gradient is kept as the string it is.
    if (!/^(oklch|oklab|lch|lab|hwb|color|color-mix)\(/i.test(value.trim())) return value;

    try {
      // fillStyle echoes an accepted value and KEEPS ITS PREVIOUS ONE when the
      // value is refused, so this comparison is the rejection test. Without it
      // a refused colour would paint nothing and read back as transparent
      // black — a wrong answer that looks like a real one.
      ctx.fillStyle = REJECT_SENTINEL;
      ctx.fillStyle = value;
      if (ctx.fillStyle === REJECT_SENTINEL) return value;

      ctx.clearRect(0, 0, 1, 1);
      ctx.fillRect(0, 0, 1, 1);
      const [r, g, b, a] = ctx.getImageData(0, 0, 1, 1).data;

      if (a === 0) return 'rgba(0, 0, 0, 0)';
      if (a === 255) return `rgb(${r}, ${g}, ${b})`;

      /*
       * getImageData returns PREMULTIPLIED alpha, so a translucent colour
       * comes back darkened: oklch(1 0 0 / 0.2) reads 255,255,255,51 here but
       * a genuinely premultiplied buffer would give 51,51,51,51. Chromium
       * un-premultiplies on read, so the channels are already straight and
       * dividing again would wash every translucent token out.
       */
      return `rgba(${r}, ${g}, ${b}, ${Math.round((a / 255) * 1000) / 1000})`;
    } catch {
      return value;
    }
  };

  /*
   * Blur whatever the page focused on load, before reading anything.
   *
   * The sign-in page's email input carries `autofocus`, and
   * `.jambo-field input:focus` changes both the border colour and the
   * background. Without this, the "resting" field tokens are the focused ones:
   * a border that is really the primary blue gets captured as the neutral
   * hairline, and every quiet outline in the app comes out bright blue. That
   * is a wrong value that looks entirely plausible in a token file, which is
   * the kind this whole script exists to avoid.
   */
  if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
  await settle(TRANSITION_MS);

  const rootStyle = getComputedStyle(document.documentElement);
  for (let i = 0; i < rootStyle.length; i++) {
    const prop = rootStyle[i];
    if (prop && prop.startsWith('--')) {
      out.custom[prop] = rootStyle.getPropertyValue(prop).trim();
    }
  }

  // Chromium enumerates custom properties on computed style, but only those it
  // considers registered on the element. Same-origin stylesheets are scraped
  // as well so a `--bs-*` declared on `:root` and never inherited still lands.
  for (const sheet of Array.from(document.styleSheets)) {
    let rules;
    try { rules = sheet.cssRules; } catch { continue; } // cross-origin (fonts)
    if (!rules) continue;
    for (const rule of Array.from(rules)) {
      if (!rule.selectorText || !rule.style) continue;
      if (!/^(:root|html|body)\b/.test(rule.selectorText)) continue;
      for (let i = 0; i < rule.style.length; i++) {
        const prop = rule.style[i];
        if (prop && prop.startsWith('--') && out.custom[prop] === undefined) {
          out.custom[prop] = rule.style.getPropertyValue(prop).trim();
        }
      }
    }
  }

  for (const probe of probes) {
    /*
     * Some states only exist on screen for a moment — a validation error, a
     * flash message — so there is nothing to query on a page loaded cleanly.
     * The element is created, attached where the real one would be attached so
     * it inherits the same cascade, read, and removed. This asks the page's own
     * stylesheet what an error looks like, rather than copying a hex out of the
     * CSS by hand and having the token file quietly go stale.
     */
    if (probe.inject) {
      const parent = document.querySelector(probe.inject.parent) ?? document.body;
      const el = document.createElement(probe.inject.tag ?? 'div');
      el.className = probe.inject.className;
      el.textContent = 'probe';
      /*
       * `wrap` exists because some components are only themselves inside their
       * own parent. Bootstrap declares the progress *bar*'s colour as
       * `--bs-progress-bar-bg` on `.progress`, so a `.progress-bar` injected
       * on its own computes to transparent — which reads as "the bar has no
       * colour" rather than "you asked the wrong element". Injecting the
       * wrapper too asks the question the way the page answers it.
       */
      let attach = el;
      const wraps = probe.inject.wrap
        ? (Array.isArray(probe.inject.wrap) ? probe.inject.wrap : [probe.inject.wrap])
        : [];
      // Outermost first, so `['iq-watching-block', 'block-images']` builds the
      // ancestry a descendant selector like
      // `.iq-watching-block .block-images .iq-image-box` actually needs.
      for (const className of [...wraps].reverse()) {
        const wrapper = document.createElement('div');
        wrapper.className = className;
        wrapper.appendChild(attach);
        attach = wrapper;
      }
      parent.appendChild(attach);
      const style = getComputedStyle(el, probe.pseudo ?? null);
      const values = {};
      for (const prop of probe.props) values[prop] = normalise(style.getPropertyValue(prop).trim());
      attach.remove();
      out.probes[probe.name] = { selector: `injected .${probe.inject.className}`, values };
      continue;
    }

    let matched = null;
    let element = null;
    for (const selector of probe.selectors) {
      const found = document.querySelector(selector);
      if (found) { matched = selector; element = found; break; }
    }
    if (!element) {
      out.probes[probe.name] = { selector: null, values: {} };
      continue;
    }

    // A focused control is a different design state with different values, and
    // the app needs both. Reading it means putting the page into that state
    // and letting the transition finish.
    if (probe.focus && element instanceof HTMLElement) {
      element.focus();
      await settle(TRANSITION_MS);
    }

    const style = getComputedStyle(element, probe.pseudo ?? null);
    const values = {};
    for (const prop of probe.props) values[prop] = normalise(style.getPropertyValue(prop).trim());

    if (probe.focus && element instanceof HTMLElement) {
      element.blur();
      await settle(TRANSITION_MS);
    }

    out.probes[probe.name] = {
      selector: matched + (probe.pseudo ?? '') + (probe.focus ? ':focus' : ''),
      values,
    };
  }

  return out;
}

// ------------------------------------------------------------- collection

const captures = {};

/*
 * Sign in once, before the viewport loop.
 *
 * Once, because the session is a cookie on the browser profile and survives
 * both viewports — and because signing in twice would double the number of
 * failed-login attempts this script makes against a rate limiter that is
 * keyed on email plus IP.
 */
let watchPath = null;
let signInError = null;
let hubPath = null;
/** The same hub, its notifications tab. Derived from hubPath, never guessed. */
let inboxPath = null;

async function setViewport(vp) {
  await call('Emulation.setDeviceMetricsOverride', {
    width: vp.width, height: vp.height, deviceScaleFactor: vp.scale, mobile: vp.mobile,
  });
}

async function capture(vpName, page, path) {
  const url = BASE + path;
  process.stdout.write(`  ${vpName.padEnd(6)} ${url} ... `);
  try {
    await goto(url);
    /*
     * The watch page builds its control bar from a module script and its
     * settings popover from a deferred one, so the 1.2s in goto() is not
     * always enough for `.media-slider__track` to exist. Waiting for the
     * element rather than lengthening the sleep keeps the public pages fast.
     */
    if (page.auth) {
      await evaluate(async () => {
        for (let i = 0; i < 60; i++) {
          if (document.querySelector('.media-slider__track')) return true;
          await new Promise((r) => setTimeout(r, 250));
        }
        return false;
      });
    }
    captures[vpName][page.key] = await evaluate(probePage, { probes: PROBES });
    console.log('ok');
  } catch (cause) {
    console.log(`FAILED (${cause.message})`);
    captures[vpName][page.key] = null;
  }
}

/*
 * PUBLIC PAGES FIRST, AND THE ORDER IS LOAD-BEARING.
 *
 * The first version of this signed in before capturing anything, and every
 * single auth token vanished — because a signed-in browser asking for /login
 * is redirected away from it, so `.jambo-auth-card` matched nothing. The run
 * reported six missed probes and refused to write, which is the good outcome;
 * had `color.surface` not happened to be required, it would have written a
 * tokens file with the sign-in screen's whole design silently absent.
 *
 * Signed out, then signed in. There is no page here that needs the reverse.
 */
for (const [vpName, vp] of Object.entries(VIEWPORTS)) {
  await setViewport(vp);
  captures[vpName] = {};
  for (const page of PAGES.filter((p) => !p.auth)) {
    await capture(vpName, page, page.path);
  }
}

if (CAN_SIGN_IN) {
  process.stdout.write(`  signing in as ${LOGIN_EMAIL} ... `);
  const outcome = await signIn();

  if (outcome.ok) {
    console.log('ok');
    watchPath = await discoverWatchPath();
    console.log(watchPath ? `  watch page: ${watchPath}` : '  no watch page found');
    if (!watchPath) signInError = 'signed in, but no watch page could be found';

    hubPath = await discoverHubPath();
    console.log(hubPath ? `  hub page: ${hubPath}` : '  no hub page found');

    // Same username, its notifications tab. Built from the discovered path so
    // it cannot drift from whichever account actually signed in.
    inboxPath = hubPath === null ? null : hubPath.replace(/\/billing$/, '/notifications');
    console.log(inboxPath ? `  inbox page: ${inboxPath}` : '  no inbox page found');
  } else {
    console.log(`FAILED (${outcome.why})`);
    signInError = outcome.why;
  }
} else {
  signInError = 'no credentials given';
  console.log('  no --login/--password, so the signed-in pages are skipped');
}

for (const [vpName, vp] of Object.entries(VIEWPORTS)) {
  await setViewport(vp);
  for (const page of PAGES.filter((p) => p.auth)) {
    const path = { hub: hubPath, inbox: inboxPath, watch: watchPath }[page.key] ?? null;

    if (path === null) {
      captures[vpName][page.key] = null;
      continue;
    }
    await capture(vpName, page, path);
  }
}

ws.close();
browser.kill();

// ------------------------------------------------------------ normalising

function toHex(value) {
  if (!value) return null;
  const v = value.trim();
  if (v.startsWith('#')) return v.toLowerCase();
  const m = v.match(/^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)\s*(?:[,/]\s*([\d.]+)\s*)?\)$/i);
  if (!m) return null;
  const [r, g, b] = [m[1], m[2], m[3]].map((n) => Math.round(Number(n)));
  const a = m[4] === undefined ? 1 : Number(m[4]);
  // Transparent is a real answer and must not become black: a card that reads
  // rgba(0,0,0,0) is inheriting the page, and the theme wants to know that.
  if (a === 0) return 'transparent';
  if (a < 1) return `rgba(${r}, ${g}, ${b}, ${a})`;
  return '#' + [r, g, b].map((n) => n.toString(16).padStart(2, '0')).join('');
}

/*
 * Exponent notation is accepted because `calc(infinity * 1px)` is a real thing
 * in this stylesheet — it is how player.css asks for a pill, and Chromium
 * computes it to `3.35544e+07px`. The old `^[\d.]+px$` rejected that and the
 * slider's radius came back null, which reads as "no radius" when the truth is
 * the opposite. Consumers treat anything past PILL_RADIUS as fully rounded.
 */
function toNumber(value) {
  if (!value) return null;
  const m = value.trim().match(/^(-?[\d.]+(?:e[+-]?\d+)?)px$/i);
  if (!m) return null;
  const n = Number(m[1]);
  if (!Number.isFinite(n)) return null;
  return Math.round(n * 100) / 100;
}

/** Above this, a captured radius means "pill", not a literal pixel count. */
const PILL_RADIUS = 9999;

function firstFamily(value) {
  if (!value) return null;
  const first = value.split(',')[0]?.trim() ?? '';
  return first.replace(/^["']|["']$/g, '') || null;
}

/** A token plus where it came from, so tokens.json can be audited line by line. */
const tokens = {};
function put(name, value, from) {
  if (value === null || value === undefined || value === '') return false;
  tokens[name] = { value, from };
  return true;
}

const phone = captures.phone ?? {};
const home = phone.home ?? null;

function custom(prop) {
  for (const page of Object.values(phone)) {
    const v = page?.custom?.[prop];
    if (v) return v;
  }
  return null;
}

/**
 * Reads one property of one probe, from the page the probe belongs to.
 *
 * A probe with a `page` is read only from that page. Without the scoping, an
 * auth token would be satisfied by whatever the home page happened to have
 * under the same selector, which is how the first pass produced a search box
 * where a sign-in field was wanted.
 */
function probe(name, prop) {
  const declared = PROBES.find((p) => p.name === name);
  const pages = declared?.page ? [[declared.page, phone[declared.page]]] : Object.entries(phone);
  for (const [pageKey, page] of pages) {
    const p = page?.probes?.[name];
    const v = p?.values?.[prop];
    if (v) return { value: v, from: `${p.selector} { ${prop} } @ ${pageKey}` };
  }
  return { value: null, from: null };
}

/**
 * The same read, from a named viewport rather than the canonical phone one.
 *
 * Needed because the player's skin is driven by CONTAINER queries, not by the
 * device. `player.css` has `@container media-root (width > 42rem)`, and the
 * app's player is fullscreen — so in landscape it is always in the wide state
 * even on a phone, while the phone-viewport capture reads the narrow one.
 * Both are real; the app switches between them exactly as the site does.
 */
function probeIn(viewport, name, prop) {
  const declared = PROBES.find((p) => p.name === name);
  const pages = captures[viewport] ?? {};
  const keys = declared?.page ? [declared.page] : Object.keys(pages);
  for (const pageKey of keys) {
    const p = pages[pageKey]?.probes?.[name];
    const v = p?.values?.[prop];
    if (v) return { value: v, from: `${p.selector} { ${prop} } @ ${pageKey} (${viewport})` };
  }
  return { value: null, from: null };
}

if (!home) {
  console.error('\nThe home page did not capture. Nothing can be written from that.');
  process.exit(1);
}

// Colours -------------------------------------------------------------------
const primaryRaw = custom('--bs-primary');
put('color.primary', toHex(primaryRaw), primaryRaw ? ':root { --bs-primary }' : null);

const bodyBg = probe('body', 'background-color');
put('color.bg', toHex(bodyBg.value), bodyBg.from);

const bodyColor = probe('body', 'color');
put('color.text', toHex(bodyColor.value), bodyColor.from);

const cardBg = probe('authCard', 'background-color');
put('color.surface', toHex(cardBg.value), cardBg.from);

const headerBg = probe('header', 'background-color');
put('color.header', toHex(headerBg.value), headerBg.from);

const mutedColor = probe('authMuted', 'color');
put('color.textMuted', toHex(mutedColor.value), mutedColor.from);

const placeholderColor = probe('authPlaceholder', 'color');
put('color.placeholder', toHex(placeholderColor.value), placeholderColor.from);

const linkColor = probe('link', 'color');
put('color.link', toHex(linkColor.value), linkColor.from);

const inputBorder = probe('authField', 'border-color');
put('color.border', toHex(inputBorder.value), inputBorder.from);

const inputBg = probe('authField', 'background-color');
put('color.inputBg', toHex(inputBg.value), inputBg.from);

const fieldText = probe('authField', 'color');
put('color.fieldText', toHex(fieldText.value), fieldText.from);

const focusBorder = probe('authFieldFocus', 'border-color');
put('color.borderFocus', toHex(focusBorder.value), focusBorder.from);

const focusBg = probe('authFieldFocus', 'background-color');
put('color.inputBgFocus', toHex(focusBg.value), focusBg.from);

const surfaceBorder = probe('authCard', 'border-color');
put('color.surfaceBorder', toHex(surfaceBorder.value), surfaceBorder.from);

for (const [prefix, probeName] of [['error', 'authAlert'], ['ok', 'authAlertSuccess']]) {
  for (const [suffix, prop] of [['Bg', 'background-color'], ['Text', 'color'], ['Border', 'border-color']]) {
    const read = probe(probeName, prop);
    put(`color.${prefix}${suffix}`, toHex(read.value), read.from);
  }
}

const alertRadius = probe('authAlert', RADIUS);
put('radius.alert', toNumber(alertRadius.value), alertRadius.from);

const onPrimary = probe('primaryButton', 'color');
put('color.onPrimary', toHex(onPrimary.value), onPrimary.from);

for (const [token, prop] of [
  ['color.success', '--bs-success'],
  ['color.warning', '--bs-warning'],
  ['color.danger', '--bs-danger'],
  ['color.info', '--bs-info'],
]) {
  const raw = custom(prop);
  put(token, toHex(raw), raw ? `:root { ${prop} }` : null);
}

// Type ----------------------------------------------------------------------
const family = probe('body', 'font-family');
put('font.family', firstFamily(family.value), family.from);

const bodySize = probe('body', 'font-size');
put('font.size.body', toNumber(bodySize.value), bodySize.from);

const bodyLine = probe('body', 'line-height');
put('font.lineHeight.body', toNumber(bodyLine.value), bodyLine.from);

const headingSize = probe('heroTitle', 'font-size');
put('font.size.heroTitle', toNumber(headingSize.value), headingSize.from);

const headingWeight = probe('heroTitle', 'font-weight');
put('font.weight.heroTitle', headingWeight.value ? Number(headingWeight.value) : null, headingWeight.from);

const fieldSize = probe('authField', 'font-size');
put('font.size.field', toNumber(fieldSize.value), fieldSize.from);

const mutedSize = probe('authMuted', 'font-size');
put('font.size.caption', toNumber(mutedSize.value), mutedSize.from);

const railSize = probe('railHeading', 'font-size');
put('font.size.railHeading', toNumber(railSize.value), railSize.from);

const railWeight = probe('railHeading', 'font-weight');
put('font.weight.railHeading', railWeight.value ? Number(railWeight.value) : null, railWeight.from);

const buttonSize = probe('primaryButton', 'font-size');
put('font.size.button', toNumber(buttonSize.value), buttonSize.from);

const buttonWeight = probe('primaryButton', 'font-weight');
put('font.weight.button', buttonWeight.value ? Number(buttonWeight.value) : null, buttonWeight.from);

// Shape ---------------------------------------------------------------------
/*
 * The site's buttons are pills — the first run read 600px off `.btn-primary`,
 * which is a `border-radius` large enough to be a capsule at any height rather
 * than a mistake. Named for what it is.
 */
const buttonRadius = probe('primaryButton', RADIUS);
put('radius.pill', toNumber(buttonRadius.value), buttonRadius.from);

const cardRadius = probe('poster', RADIUS);
put('radius.card', toNumber(cardRadius.value), cardRadius.from);

const inputRadius = probe('authField', RADIUS);
put('radius.input', toNumber(inputRadius.value), inputRadius.from);

const surfaceRadius = probe('authCard', RADIUS);
put('radius.surface', toNumber(surfaceRadius.value), surfaceRadius.from);

const inputHeight = probe('authField', 'height');
put('size.inputHeight', toNumber(inputHeight.value), inputHeight.from);

const buttonHeight = probe('authButton', 'height');
put('size.buttonHeight', toNumber(buttonHeight.value), buttonHeight.from);

const surfacePadding = probe('authCard', 'padding-left');
put('space.surfacePadding', toNumber(surfacePadding.value), surfacePadding.from);

const headerHeight = probe('header', 'height');
put('size.headerHeight', toNumber(headerHeight.value), headerHeight.from);

// Rhythm --------------------------------------------------------------------
const railGap = custom('--jambo-rail-gap');
if (railGap) {
  // Declared in em in jambo-header.css; the browser hands back the used value
  // in px only when it is a resolved length, so an em here is converted
  // against the body size rather than guessed.
  const em = railGap.match(/^([\d.]+)em$/);
  const bodyPx = tokens['font.size.body']?.value ?? 16;
  put(
    'space.railGap',
    em ? Math.round(Number(em[1]) * bodyPx) : toNumber(railGap),
    ':root { --jambo-rail-gap }',
  );
}

const railHeadingGap = probe('railHeading', 'margin-bottom');
put('space.railHeadingGap', toNumber(railHeadingGap.value), railHeadingGap.from);

// Home rails ----------------------------------------------------------------
/*
 * The rhythm of the home screen, which is NOT `space.railGap` above.
 *
 * That token is `--jambo-rail-gap`, scoped in jambo-header.css to
 * `.jambo-detail-rails` — the wrapper the detail, watch and episode pages
 * carry. The home page does not carry it and keeps the vendor's own spacing,
 * so the app needs its own number or its home screen is a third of the gap
 * the website draws.
 *
 * The heading gap is summed rather than read from one property because the
 * markup expresses it as two utilities, `mb-2 pb-1`, and the app wants the
 * distance between the heading and the first poster, not either half of it.
 */
const homeRailGap = probe('railSwiper', 'margin-bottom');
put('space.homeRailGap', toNumber(homeRailGap.value), homeRailGap.from);

const headingRowMargin = probe('railHeadingRow', 'margin-bottom');
const headingRowPadding = probe('railHeadingRow', 'padding-bottom');
if (headingRowMargin.value !== null) {
  const gap = (toNumber(headingRowMargin.value) ?? 0) + (toNumber(headingRowPadding.value) ?? 0);
  put('space.homeRailHeadingGap', gap, `${headingRowMargin.from} + padding-bottom`);
}

const viewAllColor = probe('railViewAll', 'color');
put('color.viewAll', toHex(viewAllColor.value), viewAllColor.from);

const viewAllSize = probe('railViewAll', 'font-size');
put('font.size.viewAll', toNumber(viewAllSize.value), viewAllSize.from);

// The poster card -----------------------------------------------------------
/*
 * `size.posterWidth` is the used width the browser computed from Streamit's
 * `data-mobile="3.5"` at a 390px viewport: three and a half cards across, the
 * half being the peek that tells a viewer the rail scrolls. Read, never
 * divided out by hand — and `size.posterAspect` comes from the rendered box
 * rather than from the 2:3 everyone assumes, because the site's own posters
 * are whatever the admin uploaded.
 */
const slideWidth = probe('posterSlide', 'width');
put('size.posterWidth', toNumber(slideWidth.value), slideWidth.from);

/*
 * The gutter between posters. Swiper runs `spaceBetween: 0` at every
 * breakpoint, so a margin of 0 is the expected answer and the real gap — if
 * there is one — is the slide's padding. Recorded from whichever is non-zero
 * rather than assumed to be either.
 */
const slideMargin = toNumber(probe('posterSlide', 'margin-right').value) ?? 0;
const slidePad = toNumber(probe('posterSlide', 'padding-right').value) ?? 0;
put(
  'space.posterGap',
  slideMargin || slidePad * 2,
  slidePad && !slideMargin
    ? `${probe('posterSlide', 'padding-right').from} × 2 (swiper spaceBetween is 0)`
    : probe('posterSlide', 'margin-right').from,
);

const railPadding = probe('railContainer', 'padding-left');
put('space.railInset', toNumber(railPadding.value), railPadding.from);

const posterW = toNumber(probe('posterImage', 'width').value);
const posterH = toNumber(probe('posterImage', 'height').value);
if (posterW && posterH) {
  put(
    'size.posterAspect',
    Math.round((posterW / posterH) * 1000) / 1000,
    `${probe('posterImage', 'width').from} ÷ height`,
  );
}

const posterRadius = probe('posterImage', RADIUS);
put('radius.poster', toNumber(posterRadius.value), posterRadius.from);

const cardTitleSize = probe('cardTitle', 'font-size');
put('font.size.cardTitle', toNumber(cardTitleSize.value), cardTitleSize.from);

const cardTitleWeight = probe('cardTitle', 'font-weight');
put('font.weight.cardTitle', cardTitleWeight.value ? Number(cardTitleWeight.value) : null, cardTitleWeight.from);

const cardTitleColor = probe('cardTitle', 'color');
put('color.cardTitle', toHex(cardTitleColor.value), cardTitleColor.from);

const cardMetaSize = probe('cardMeta', 'font-size');
put('font.size.cardMeta', toNumber(cardMetaSize.value), cardMetaSize.from);

const badgeBg = probe('premiumBadge', 'background-color');
put('color.premiumBadgeBg', toHex(badgeBg.value), badgeBg.from);

const badgeFg = probe('premiumBadgeIcon', 'color');
put('color.premiumBadge', toHex(badgeFg.value), badgeFg.from);

const badgeIconSize = probe('premiumBadgeIcon', 'font-size');
put('size.premiumBadgeIcon', toNumber(badgeIconSize.value), badgeIconSize.from);

const badgeSize = probe('premiumBadge', 'width');
put('size.premiumBadge', toNumber(badgeSize.value), badgeSize.from);

/*
 * The badge is a circle, declared as `border-radius: 50%`, and `toNumber`
 * only understands px. Half the measured width is the same circle in the
 * units a React Native style accepts, and the provenance says it was a
 * percentage so nobody later reads 14 as a designed corner radius.
 */
const badgeRadius = probe('premiumBadge', RADIUS);
if (badgeRadius.value === '50%' && toNumber(badgeSize.value)) {
  put('radius.premiumBadge', toNumber(badgeSize.value) / 2, `${badgeRadius.from} = 50%, resolved against width`);
} else {
  put('radius.premiumBadge', toNumber(badgeRadius.value), badgeRadius.from);
}

// Top 10 --------------------------------------------------------------------
/*
 * The rank numeral is not a coloured glyph. `color` computes to transparent
 * and there is no text stroke: Streamit fills the letterform with a texture
 * image through `background-clip: text`. An app that read `color` alone would
 * draw nothing at all, which is why the capture asks for `background-image`.
 *
 * The URL is recorded rather than the bytes. The file is Streamit's own, and
 * it is already in this repository at `public/frontend/images/pages/` — so the
 * app copies it from source control instead of downloading it, and this token
 * is the record of which file the site is actually using.
 */
const topTenSize = probe('topTenNumber', 'font-size');
put('font.size.topTenNumber', toNumber(topTenSize.value), topTenSize.from);

const topTenWeight = probe('topTenNumber', 'font-weight');
put('font.weight.topTenNumber', topTenWeight.value ? Number(topTenWeight.value) : null, topTenWeight.from);

/*
 * Recorded as a SITE-RELATIVE PATH, not the absolute URL the page renders.
 *
 * The computed `background-image` is absolute against whatever host was
 * captured, so exporting from the live site wrote
 * `https://jambofilms.com/frontend/...` and exporting from a local one wrote
 * `http://127.0.0.1:8090/frontend/...` — the same file, a different token, and
 * a diff on every run from a different machine. This token exists to say WHICH
 * FILE the site uses (the app ships its own copy from source control), and the
 * path says that without pinning the host it happened to be read from.
 */
const topTenFill = probe('topTenNumber', 'background-image');
const topTenAbsolute = topTenFill.value?.match(/url\(["']?([^"')]+)["']?\)/)?.[1] ?? null;
const topTenUrl = topTenAbsolute?.startsWith(BASE)
  ? topTenAbsolute.slice(BASE.length)
  : topTenAbsolute;
put('asset.topTenTexture', topTenUrl, topTenFill.from);

// Continue Watching ---------------------------------------------------------
const progressTrackBg = probe('progressTrack', 'background-color');
put('color.progressTrack', toHex(progressTrackBg.value), progressTrackBg.from);

/*
 * Read from a `.progress-bar` injected inside a `.progress`, because Bootstrap
 * declares the fill as `--bs-progress-bar-bg` on the wrapper. Injected alone
 * it computes to transparent, and "the progress bar is transparent" is the
 * kind of answer that looks like a finding and is really a bad question.
 */
const progressBarBg = probe('progressBar', 'background-color');
put('color.progressBar', toHex(progressBarBg.value), progressBarBg.from);

/*
 * The scrim under the title and progress strip. It belongs to `.iq-image-box`,
 * not to `.iq-preogress` — that element has no background at all, which is
 * what the first pass of this probe found and what would have been recorded as
 * "the overlay is transparent" if the question had not been asked again.
 */
const watchScrim = probe('watchingBox', 'background-image');
put('gradient.watchingScrim', watchScrim.value === 'none' ? null : watchScrim.value, watchScrim.from);

const watchRadius = probe('watchingBox', RADIUS);
put('radius.watchingCard', toNumber(watchRadius.value), watchRadius.from);

/*
 * 5:3, and read rather than assumed. Continue Watching is the one rail on the
 * home screen that is not a portrait poster, so a card component that took the
 * poster aspect would crop every still.
 */
const watchAspect = probe('watchingStill', 'aspect-ratio');
if (watchAspect.value) {
  const [w, h] = watchAspect.value.split('/').map((n) => Number(n.trim()));
  put('size.watchingAspect', w && h ? Math.round((w / h) * 1000) / 1000 : null, watchAspect.from);
}

/*
 * The blade sets `style="height: 2px"` inline on the bar, so the stylesheet's
 * own track height is not what a viewer sees. The inline value is the design
 * and it is recorded here with that provenance rather than probed, because
 * there is nothing on a guest page to probe it from.
 */
put('size.progressBarHeight', 2, 'cards/continue-watch-card.blade.php { style="height: 2px" }');

// The phone tab bar ---------------------------------------------------------
const tabBarBg = probe('tabBar', 'background-color');
put('color.tabBarBg', toHex(tabBarBg.value), tabBarBg.from);

const tabBarBorder = probe('tabBar', 'border-top-color');
put('color.tabBarBorder', toHex(tabBarBorder.value), tabBarBorder.from);

const tabBarPad = probe('tabBar', 'padding-top');
put('space.tabBarPadding', toNumber(tabBarPad.value), tabBarPad.from);

const tabIdle = probe('tabItem', 'color');
put('color.tabIdle', toHex(tabIdle.value), tabIdle.from);

const tabActive = probe('tabItemActive', 'color');
put('color.tabActive', toHex(tabActive.value), tabActive.from);

const tabIconSize = probe('tabIcon', 'font-size');
put('size.tabIcon', toNumber(tabIconSize.value), tabIconSize.from);

const tabLabelSize = probe('tabLabel', 'font-size');
put('font.size.tabLabel', toNumber(tabLabelSize.value), tabLabelSize.from);

// The hero ------------------------------------------------------------------
const heroSize = probe('heroHeadline', 'font-size');
put('font.size.heroHeadline', toNumber(heroSize.value), heroSize.from);

const heroWeight = probe('heroHeadline', 'font-weight');
put('font.weight.heroHeadline', heroWeight.value ? Number(heroWeight.value) : null, heroWeight.from);

const heroTracking = probe('heroHeadline', 'letter-spacing');
put('size.heroTracking', toNumber(heroTracking.value), heroTracking.from);

// The player ----------------------------------------------------------------
//
// Every value here is resolved from `currentColor` and relative `oklch()` by
// the browser. There is no hex in player.css to copy even if someone wanted to.

const controlsBg = probe('playerControls', 'background-color');
put('color.playerControlsBg', toHex(controlsBg.value), controlsBg.from);

const controlsFg = probe('playerControls', 'color');
put('color.playerControlsFg', toHex(controlsFg.value), controlsFg.from);

const controlsPadY = probe('playerControls', 'padding-top');
put('space.playerControlsY', toNumber(controlsPadY.value), controlsPadY.from);

const controlsPadX = probe('playerControls', 'padding-left');
put('space.playerControlsX', toNumber(controlsPadX.value), controlsPadX.from);

/*
 * The scrim behind the controls, captured as the gradient string it really is.
 *
 * React Native has no `backdrop-filter` and no CSS gradients, so this token is
 * not consumed as a style — it is the evidence that decides whether the app's
 * control bar should be `expo-blur` or a LinearGradient, which is the choice
 * §6.4 of the plan says must be settled by what the site renders rather than
 * by preference.
 */
const controlsBlur = probe('playerControls', 'backdrop-filter');
put('effect.playerControlsBackdrop', controlsBlur.value || null, controlsBlur.from);

const overlayImage = probe('playerOverlay', 'background-image');
put('effect.playerOverlay', overlayImage.value || null, overlayImage.from);

const playerButtonRadius = probe('playerButton', RADIUS);
put('radius.playerButton', toNumber(playerButtonRadius.value), playerButtonRadius.from);

const playerButtonPadY = probe('playerButton', 'padding-top');
put('space.playerButtonY', toNumber(playerButtonPadY.value), playerButtonPadY.from);

const playerButtonPadX = probe('playerButton', 'padding-left');
put('space.playerButtonX', toNumber(playerButtonPadX.value), playerButtonPadX.from);

const playerIconW = probe('playerButtonIcon', 'width');
put('size.playerIconButton', toNumber(playerIconW.value), playerIconW.from);

const playerPrimaryBg = probe('playerButtonPrimary', 'background-color');
put('color.playerButtonPrimaryBg', toHex(playerPrimaryBg.value), playerPrimaryBg.from);

const playerPrimaryFg = probe('playerButtonPrimary', 'color');
put('color.playerButtonPrimaryFg', toHex(playerPrimaryFg.value), playerPrimaryFg.from);

const playerSubtleBg = probe('playerButtonSubtle', 'background-color');
put('color.playerButtonBg', toHex(playerSubtleBg.value), playerSubtleBg.from);

const trackBg = probe('playerSliderTrack', 'background-color');
put('color.playerTrack', toHex(trackBg.value), trackBg.from);

const trackH = probe('playerSliderTrack', 'height');
put('size.playerTrackHeight', toNumber(trackH.value), trackH.from);

/*
 * The track is a pill: player.css says `border-radius: calc(infinity * 1px)`,
 * which Chromium computes to 33,554,432px. Clamped to the track's own height
 * so the app draws a rounded bar rather than passing an absurd number into a
 * layout engine that has no reason to tolerate it.
 */
const trackRadius = probe('playerSliderTrack', RADIUS);
const trackRadiusPx = toNumber(trackRadius.value);
put(
  'radius.playerTrack',
  trackRadiusPx === null ? null : Math.min(trackRadiusPx, (toNumber(trackH.value) ?? 6) / 2),
  trackRadiusPx !== null && trackRadiusPx >= PILL_RADIUS
    ? `${trackRadius.from} (pill, clamped to half the track height)`
    : trackRadius.from,
);

const fillBg = probe('playerSliderFill', 'background-color');
put('color.playerTrackFill', toHex(fillBg.value), fillBg.from);

const bufferBg = probe('playerSliderBuffer', 'background-color');
put('color.playerTrackBuffer', toHex(bufferBg.value), bufferBg.from);

const thumbBg = probe('playerSliderThumb', 'background-color');
put('color.playerThumb', toHex(thumbBg.value), thumbBg.from);

const thumbW = probe('playerSliderThumb', 'width');
put('size.playerThumb', toNumber(thumbW.value), thumbW.from);

/*
 * The slider's own height is the TOUCH TARGET, not the visible bar: player.css
 * gives `.media-slider` 2rem and `.media-slider__track` 3px. Capturing both is
 * what stops the app drawing a 3px-tall thing nobody can grab.
 */
const sliderH = probe('playerSlider', 'height');
put('size.playerSliderHitArea', toNumber(sliderH.value), sliderH.from);

const timeSize = probe('playerTime', 'font-size');
put('font.size.playerTime', toNumber(timeSize.value), timeSize.from);

const timeColor = probe('playerTime', 'color');
put('color.playerTime', toHex(timeColor.value), timeColor.from);

/*
 * Read from the TABLET capture on purpose, and this is not a shortcut.
 *
 * `.media-time--duration` is 60% white only inside
 * `@container media-root (width > 42rem)`; below that the whole time group
 * collapses to the duration alone and it renders solid. The phone capture
 * therefore returns pure white, which is a true value for a 390px-wide player
 * and the wrong one for this app — the app's player is FULLSCREEN, so in
 * landscape it is always past 42rem even on a phone.
 *
 * Both states exist in the site and both are captured: `color.playerTime` is
 * the current-time colour, this is the muted duration the wide layout uses,
 * and the player picks between them on its own measured width the way the
 * container query does.
 */
const timeDurationColor = probeIn('tablet', 'playerTimeDuration', 'color');
put('color.playerTimeMuted', toHex(timeDurationColor.value), timeDurationColor.from);

const bufferingColor = probe('playerBuffering', 'color');
put('color.playerBuffering', toHex(bufferingColor.value), bufferingColor.from);

const menuBg = probe('playerMenu', 'background-color');
put('color.playerMenuBg', toHex(menuBg.value), menuBg.from);

const menuRadius = probe('playerMenu', RADIUS);
put('radius.playerMenu', toNumber(menuRadius.value), menuRadius.from);

const menuBorder = probe('playerMenu', 'border-color');
put('color.playerMenuBorder', toHex(menuBorder.value), menuBorder.from);

const menuFg = probe('playerMenu', 'color');
put('color.playerMenuFg', toHex(menuFg.value), menuFg.from);

/*
 * The row's padding, not a min-height: `.jambo-settings-row` declares none, so
 * probing `min-height` returned a confident 0. The row's height on the site is
 * its padding plus its label, so those are what get captured and the app
 * computes the same way. A token reading 0 is worse than no token, because it
 * looks like an answer.
 */
const menuRowPadY = probe('playerMenuRow', 'padding-top');
put('space.playerMenuRowY', toNumber(menuRowPadY.value), menuRowPadY.from);

const menuRowPadX = probe('playerMenuRow', 'padding-left');
put('space.playerMenuRowX', toNumber(menuRowPadX.value), menuRowPadX.from);

const menuLabelSize = probe('playerMenuLabel', 'font-size');
put('font.size.playerMenuLabel', toNumber(menuLabelSize.value), menuLabelSize.from);

const menuValueColor = probe('playerMenuValue', 'color');
put('color.playerMenuValue', toHex(menuValueColor.value), menuValueColor.from);

const dsDot = probe('playerDataSaverDot', 'background-color');
put('color.playerDataSaverDot', toHex(dsDot.value), dsDot.from);

/*
 * The profile hub: the card, the status badges and the invoice table.
 *
 * Rio's ruling of 2026-09-09 is what these exist for — the app reuses the
 * webapp's assets at their exact design, behaviour and size, so the account
 * screens' badge is the site's badge rather than an approximation built from
 * the semantic ok/warning colours. Read off `/{username}/billing`, which is
 * the only page where all three render.
 */
const hubCardBg = probe('hubCard', 'background-color');
put('color.hubCardBg', toHex(hubCardBg.value), hubCardBg.from);

const hubCardBorder = probe('hubCard', 'border-color');
put('color.hubCardBorder', toHex(hubCardBorder.value), hubCardBorder.from);

const hubCardRadius = probe('hubCard', RADIUS);
put('radius.hubCard', toNumber(hubCardRadius.value), hubCardRadius.from);

const hubCardPad = probe('hubCard', 'padding-top');
put('space.hubCardPadding', toNumber(hubCardPad.value), hubCardPad.from);

const hubTitleSize = probe('hubCardTitle', 'font-size');
put('font.size.hubCardTitle', toNumber(hubTitleSize.value), hubTitleSize.from);

const hubSubtitleSize = probe('hubCardSubtitle', 'font-size');
put('font.size.hubCardSubtitle', toNumber(hubSubtitleSize.value), hubSubtitleSize.from);

const hubSubtitleColor = probe('hubCardSubtitle', 'color');
put('color.hubCardSubtitle', toHex(hubSubtitleColor.value), hubSubtitleColor.from);

/*
 * The badges. `bg-success` and `bg-warning` are SOLID FILLS on this site, and
 * their foreground is not white — Bootstrap picks a dark text colour against a
 * light warning fill. Both halves are captured, because a badge whose fill is
 * right and whose text colour is guessed is illegible in exactly one of the
 * two states and nobody notices until an order fails.
 */
const badgeOkBg = probe('badgeOk', 'background-color');
put('color.badgeOkBg', toHex(badgeOkBg.value), badgeOkBg.from);

const badgeOkFg = probe('badgeOk', 'color');
put('color.badgeOkFg', toHex(badgeOkFg.value), badgeOkFg.from);

const badgeWarnBg = probe('badgeWarn', 'background-color');
put('color.badgeWarnBg', toHex(badgeWarnBg.value), badgeWarnBg.from);

const badgeWarnFg = probe('badgeWarn', 'color');
put('color.badgeWarnFg', toHex(badgeWarnFg.value), badgeWarnFg.from);

const badgePrimaryBg = probe('badgePrimary', 'background-color');
put('color.badgePrimaryBg', toHex(badgePrimaryBg.value), badgePrimaryBg.from);

const badgePrimaryFg = probe('badgePrimary', 'color');
put('color.badgePrimaryFg', toHex(badgePrimaryFg.value), badgePrimaryFg.from);

const statusBadgeSize = probe('badgeOk', 'font-size');
put('font.size.badge', toNumber(statusBadgeSize.value), statusBadgeSize.from);

const statusBadgeWeight = probe('badgeOk', 'font-weight');
/* A font weight is unitless, so it does NOT go through toNumber(), which
   requires a px suffix and returns null for '700'. Same as cardTitle and
   topTenNumber above. Caught by the typecheck, which is the point of naming
   every token in one file. */
put('font.weight.badge', statusBadgeWeight.value ? Number(statusBadgeWeight.value) : null, statusBadgeWeight.from);

const statusBadgeRadius = probe('badgeOk', RADIUS);
put('radius.badge', toNumber(statusBadgeRadius.value), statusBadgeRadius.from);

const badgePadY = probe('badgeOk', 'padding-top');
put('space.badgeY', toNumber(badgePadY.value), badgePadY.from);

const badgePadX = probe('badgeOk', 'padding-left');
put('space.badgeX', toNumber(badgePadX.value), badgePadX.from);

/* The invoice's line-item table. */
const hubTableBg = probe('hubTable', 'background-color');
put('color.hubTableBg', toHex(hubTableBg.value), hubTableBg.from);

const hubTableFg = probe('hubTable', 'color');
put('color.hubTableFg', toHex(hubTableFg.value), hubTableFg.from);

const hubTableSize = probe('hubTable', 'font-size');
put('font.size.hubTable', toNumber(hubTableSize.value), hubTableSize.from);

const hubCellPadY = probe('hubTableCell', 'padding-top');
put('space.hubTableCellY', toNumber(hubCellPadY.value), hubCellPadY.from);

const hubCellPadX = probe('hubTableCell', 'padding-left');
put('space.hubTableCellX', toNumber(hubCellPadX.value), hubCellPadX.from);

const hubCellBorder = probe('hubTableCell', 'border-color');
put('color.hubTableBorder', toHex(hubCellBorder.value), hubCellBorder.from);

const hubHeadColor = probe('hubTableHead', 'color');
put('color.hubTableHead', toHex(hubHeadColor.value), hubHeadColor.from);

const hubHeadSize = probe('hubTableHead', 'font-size');
put('font.size.hubTableHead', toNumber(hubHeadSize.value), hubHeadSize.from);

/*
 * The notification row.
 *
 * Read rather than eyeballed because Rio's ruling on the mockup was to hold
 * the site's size exactly: 40x40 tile, 10px radius. A row measured off a
 * drawing would have been close and wrong, which is the failure the asset
 * ruling exists to stop.
 */
const inboxRowBg = probe('inboxRow', 'background-color');
put('color.inboxRowBg', toHex(inboxRowBg.value), inboxRowBg.from);

const inboxRowBorder = probe('inboxRow', 'border-color');
put('color.inboxRowBorder', toHex(inboxRowBorder.value), inboxRowBorder.from);

const inboxRowBorderWidth = probe('inboxRow', 'border-width');
put('size.inboxRowBorder', toNumber(inboxRowBorderWidth.value), inboxRowBorderWidth.from);

const inboxRowRadius = probe('inboxRow', RADIUS);
put('radius.inboxRow', toNumber(inboxRowRadius.value), inboxRowRadius.from);

const inboxRowPadY = probe('inboxRow', 'padding-top');
put('space.inboxRowY', toNumber(inboxRowPadY.value), inboxRowPadY.from);

const inboxRowPadX = probe('inboxRow', 'padding-left');
put('space.inboxRowX', toNumber(inboxRowPadX.value), inboxRowPadX.from);

const inboxRowGap = probe('inboxRow', 'column-gap');
put('space.inboxRowGap', toNumber(inboxRowGap.value), inboxRowGap.from);

/*
 * The unread state, and the reason the app's unread tint is not a decision.
 * The site already paints it in the brand blue at 7% over an 18% border.
 */
const inboxUnreadBg = probe('inboxRowUnread', 'background-color');
put('color.inboxUnreadBg', toHex(inboxUnreadBg.value), inboxUnreadBg.from);

const inboxUnreadBorder = probe('inboxRowUnread', 'border-color');
put('color.inboxUnreadBorder', toHex(inboxUnreadBorder.value), inboxUnreadBorder.from);

/* The 40x40 leading square, in both its forms. */
const inboxTileSize = probe('inboxTile', 'width');
put('size.inboxTile', toNumber(inboxTileSize.value), inboxTileSize.from);

const inboxTileRadius = probe('inboxTile', RADIUS);
put('radius.inboxTile', toNumber(inboxTileRadius.value), inboxTileRadius.from);

const inboxTileIcon = probe('inboxTile', 'font-size');
put('font.size.inboxTileIcon', toNumber(inboxTileIcon.value), inboxTileIcon.from);

const inboxImageSize = probe('inboxImage', 'width');
put('size.inboxImage', toNumber(inboxImageSize.value), inboxImageSize.from);

const inboxImageRadius = probe('inboxImage', RADIUS);
put('radius.inboxImage', toNumber(inboxImageRadius.value), inboxImageRadius.from);

/*
 * The five tile tones, both halves of each.
 *
 * Same lesson as the badges: a tile whose fill is captured and whose glyph
 * colour is guessed is unreadable in exactly one tone, and it is never the one
 * being looked at during review.
 */
for (const [tone, probeName] of [
  ['Primary', 'inboxTonePrimary'],
  ['Success', 'inboxToneSuccess'],
  ['Warning', 'inboxToneWarning'],
  ['Danger', 'inboxToneDanger'],
  ['Info', 'inboxToneInfo'],
]) {
  const bg = probe(probeName, 'background-color');
  put(`color.inboxTone${tone}Bg`, toHex(bg.value), bg.from);

  const fg = probe(probeName, 'color');
  put(`color.inboxTone${tone}Fg`, toHex(fg.value), fg.from);
}

/* The row's own type scale, so the app stops guessing at it. */
const inboxTitleSize = probe('inboxTitle', 'font-size');
put('font.size.inboxTitle', toNumber(inboxTitleSize.value), inboxTitleSize.from);

const inboxTitleWeight = probe('inboxTitle', 'font-weight');
/* Unitless, so not through toNumber() — same trap as the badge weight. */
put('font.weight.inboxTitle', inboxTitleWeight.value ? Number(inboxTitleWeight.value) : null, inboxTitleWeight.from);

const inboxTitleColor = probe('inboxTitle', 'color');
put('color.inboxTitle', toHex(inboxTitleColor.value), inboxTitleColor.from);

const inboxMessageSize = probe('inboxMessage', 'font-size');
put('font.size.inboxMessage', toNumber(inboxMessageSize.value), inboxMessageSize.from);

const inboxMessageColor = probe('inboxMessage', 'color');
put('color.inboxMessage', toHex(inboxMessageColor.value), inboxMessageColor.from);

const inboxTimeSize = probe('inboxTime', 'font-size');
put('font.size.inboxTime', toNumber(inboxTimeSize.value), inboxTimeSize.from);

const inboxTimeColor = probe('inboxTime', 'color');
put('color.inboxTime', toHex(inboxTimeColor.value), inboxTimeColor.from);

/*
 * The pricing card, the period tabs and the current-plan strip.
 *
 * The Membership screen's plan cards, captured off `/pricing` rather than read
 * off Rio's mockup. Rule 4a of the `screen` skill: a component that exists on
 * the web product takes its design, behaviour AND size from a probe, not from
 * an eye on a drawing.
 *
 * The mockup is crimson and the brand is blue. These are the values that make
 * the same layout come out in Jambo's colours, which is the substitution Rio
 * approved for the profile menu on the same day.
 */

const p0 = probe('planCard', 'background-color');
put('color.planCardBg', toHex(p0.value), p0.from);
const p1 = probe('planCard', 'border-color');
put('color.planCardBorder', toHex(p1.value), p1.from);
const p2 = probe('planCard', 'border-width');
put('size.planCardBorder', toNumber(p2.value), p2.from);
const p3 = probe('planCard', RADIUS);
put('radius.planCard', toNumber(p3.value), p3.from);
const p4 = probe('planCard', 'padding-top');
put('space.planCardY', toNumber(p4.value), p4.from);
const p5 = probe('planCard', 'padding-left');
put('space.planCardX', toNumber(p5.value), p5.from);

const p6 = probe('planName', 'font-size');
put('font.size.planName', toNumber(p6.value), p6.from);
const p7 = probe('planName', 'font-weight');
/* Unitless, so not through toNumber() — the same trap as the badge weight. */
put('font.weight.planName', p7.value ? Number(p7.value) : null, p7.from);
const p8 = probe('planName', 'color');
put('color.planName', toHex(p8.value), p8.from);

const p9 = probe('planPrice', 'font-size');
put('font.size.planPrice', toNumber(p9.value), p9.from);
const p10 = probe('planPrice', 'font-weight');
/* Unitless, so not through toNumber() — the same trap as the badge weight. */
put('font.weight.planPrice', p10.value ? Number(p10.value) : null, p10.from);
const p11 = probe('planPrice', 'color');
put('color.planPrice', toHex(p11.value), p11.from);

const p12 = probe('planPeriod', 'font-size');
put('font.size.planPeriod', toNumber(p12.value), p12.from);
const p13 = probe('planPeriod', 'color');
put('color.planPeriod', toHex(p13.value), p13.from);

const p14 = probe('planFeature', 'font-size');
put('font.size.planFeature', toNumber(p14.value), p14.from);
const p15 = probe('planFeature', 'font-weight');
/* Unitless, so not through toNumber() — the same trap as the badge weight. */
put('font.weight.planFeature', p15.value ? Number(p15.value) : null, p15.from);
const p16 = probe('planFeature', 'color');
put('color.planFeature', toHex(p16.value), p16.from);

const p17 = probe('planFeatureIcon', 'font-size');
put('size.planFeatureIcon', toNumber(p17.value), p17.from);
const p18 = probe('planFeatureIcon', 'color');
put('color.planFeatureIcon', toHex(p18.value), p18.from);

const p19 = probe('planRibbon', 'background-color');
put('color.planRibbonBg', toHex(p19.value), p19.from);
const p20 = probe('planRibbon', 'color');
put('color.planRibbonFg', toHex(p20.value), p20.from);
const p21 = probe('planRibbon', 'padding-top');
put('space.planRibbonY', toNumber(p21.value), p21.from);
const p22 = probe('planRibbon', 'font-size');
put('font.size.planRibbon', toNumber(p22.value), p22.from);

const p23 = probe('planCta', 'background-color');
put('color.planCtaBg', toHex(p23.value), p23.from);
const p24 = probe('planCta', 'color');
put('color.planCtaFg', toHex(p24.value), p24.from);
const p25 = probe('planCta', RADIUS);
put('radius.planCta', toNumber(p25.value), p25.from);
const p26 = probe('planCta', 'font-size');
put('font.size.planCta', toNumber(p26.value), p26.from);
const p27 = probe('planCta', 'font-weight');
/* Unitless, so not through toNumber() — the same trap as the badge weight. */
put('font.weight.planCta', p27.value ? Number(p27.value) : null, p27.from);
const p28 = probe('planCta', 'height');
put('size.planCtaHeight', toNumber(p28.value), p28.from);

const p29 = probe('planCtaQuiet', 'color');
put('color.planCtaQuietFg', toHex(p29.value), p29.from);
const p30 = probe('planCtaQuiet', 'border-color');
put('color.planCtaQuietBorder', toHex(p30.value), p30.from);

const p31 = probe('periodTab', 'background-color');
put('color.periodTabBg', toHex(p31.value), p31.from);
const p32 = probe('periodTab', 'color');
put('color.periodTabFg', toHex(p32.value), p32.from);
const p33 = probe('periodTab', RADIUS);
put('radius.periodTab', toNumber(p33.value), p33.from);
const p34 = probe('periodTab', 'font-size');
put('font.size.periodTab', toNumber(p34.value), p34.from);
const p35 = probe('periodTab', 'font-weight');
/* Unitless, so not through toNumber() — the same trap as the badge weight. */
put('font.weight.periodTab', p35.value ? Number(p35.value) : null, p35.from);
const p36 = probe('periodTab', 'padding-top');
put('space.periodTabY', toNumber(p36.value), p36.from);
const p37 = probe('periodTab', 'padding-left');
put('space.periodTabX', toNumber(p37.value), p37.from);

const p38 = probe('periodTabActive', 'background-color');
put('color.periodTabActiveBg', toHex(p38.value), p38.from);
const p39 = probe('periodTabActive', 'color');
put('color.periodTabActiveFg', toHex(p39.value), p39.from);

const p40 = probe('planStrip', 'background-color');
put('color.planStripBg', toHex(p40.value), p40.from);
const p41 = probe('planStrip', 'border-color');
put('color.planStripBorder', toHex(p41.value), p41.from);
const p42 = probe('planStrip', RADIUS);
put('radius.planStrip', toNumber(p42.value), p42.from);
const p43 = probe('planStrip', 'padding-top');
put('space.planStrip', toNumber(p43.value), p43.from);

/* The header, both rows — Rio's "exact header as it is on our web app". */
const logoW = probe('headerLogo', 'width');
put('size.headerLogoWidth', toNumber(logoW.value), logoW.from);

const logoH = probe('headerLogo', 'height');
put('size.headerLogoHeight', toNumber(logoH.value), logoH.from);

const headerIconColor = probe('headerIcon', 'color');
put('color.headerIcon', toHex(headerIconColor.value), headerIconColor.from);

const headerIconSize = probe('headerIcon', 'font-size');
put('font.size.headerIcon', toNumber(headerIconSize.value), headerIconSize.from);

const headerIconBox = probe('headerIcon', 'width');
put('size.headerIcon', toNumber(headerIconBox.value), headerIconBox.from);

const hdrBadgeBg = probe('headerBadge', 'background-color');
put('color.headerBadgeBg', toHex(hdrBadgeBg.value), hdrBadgeBg.from);

const hdrBadgeFg = probe('headerBadge', 'color');
put('color.headerBadgeFg', toHex(hdrBadgeFg.value), hdrBadgeFg.from);

const hdrBadgeSize = probe('headerBadge', 'font-size');
put('font.size.headerBadge', toNumber(hdrBadgeSize.value), hdrBadgeSize.from);

const hdrBadgeRadius = probe('headerBadge', RADIUS);
put('radius.headerBadge', toNumber(hdrBadgeRadius.value), hdrBadgeRadius.from);

const hdrBadgeHeight = probe('headerBadge', 'height');
put('size.headerBadge', toNumber(hdrBadgeHeight.value), hdrBadgeHeight.from);

const hdrAvatarSize = probe('headerAvatar', 'width');
put('size.headerAvatar', toNumber(hdrAvatarSize.value), hdrAvatarSize.from);

const hdrAvatarRadius = probe('headerAvatar', RADIUS);
put('radius.headerAvatar', toNumber(hdrAvatarRadius.value), hdrAvatarRadius.from);

const genresBarBg = probe('genresBar', 'background-color');
put('color.genresBarBg', toHex(genresBarBg.value), genresBarBg.from);

const genresBarBorder = probe('genresBar', 'border-bottom-color');
put('color.genresBarBorder', toHex(genresBarBorder.value), genresBarBorder.from);

const genresBarBorderWidth = probe('genresBar', 'border-bottom-width');
/* Captured because `border-bottom-color` defaults to `currentColor` when no
   border is set — so a colour alone cannot tell a hairline from a phantom. */
put('size.genresBarBorder', toNumber(genresBarBorderWidth.value), genresBarBorderWidth.from);

const genresBarPadY = probe('genresBar', 'padding-top');
put('space.genresBarY', toNumber(genresBarPadY.value), genresBarPadY.from);

const genresBarPadX = probe('genresBar', 'padding-left');
put('space.genresBarX', toNumber(genresBarPadX.value), genresBarPadX.from);

const hdrChipBg = probe('genreChip', 'background-color');
put('color.genreChipBg', toHex(hdrChipBg.value), hdrChipBg.from);

const hdrChipFg = probe('genreChip', 'color');
put('color.genreChipFg', toHex(hdrChipFg.value), hdrChipFg.from);

const hdrChipRadius = probe('genreChip', RADIUS);
put('radius.genreChip', toNumber(hdrChipRadius.value), hdrChipRadius.from);

const hdrChipSize = probe('genreChip', 'font-size');
put('font.size.genreChip', toNumber(hdrChipSize.value), hdrChipSize.from);

const hdrChipWeight = probe('genreChip', 'font-weight');
/* Unitless, so not through toNumber() — the badge-weight trap again. */
put('font.weight.genreChip', hdrChipWeight.value ? Number(hdrChipWeight.value) : null, hdrChipWeight.from);

const hdrChipPadY = probe('genreChip', 'padding-top');
put('space.genreChipY', toNumber(hdrChipPadY.value), hdrChipPadY.from);

const hdrChipPadX = probe('genreChip', 'padding-left');
put('space.genreChipX', toNumber(hdrChipPadX.value), hdrChipPadX.from);

const hdrChipBorder = probe('genreChip', 'border-color');
put('color.genreChipBorder', toHex(hdrChipBorder.value), hdrChipBorder.from);

const hdrChipBorderWidth = probe('genreChip', 'border-width');
put('size.genreChipBorder', toNumber(hdrChipBorderWidth.value), hdrChipBorderWidth.from);

const hdrChipActiveBg = probe('genreChipActive', 'background-color');
put('color.genreChipActiveBg', toHex(hdrChipActiveBg.value), hdrChipActiveBg.from);

const hdrChipActiveFg = probe('genreChipActive', 'color');
put('color.genreChipActiveFg', toHex(hdrChipActiveFg.value), hdrChipActiveFg.from);

// ------------------------------------------------------------------ output

/*
 * Carry the signed-in tokens forward when this run could not sign in.
 *
 * THE FAILURE THIS PREVENTS IS SILENT, which is why it is worth the code.
 * `npm run tokens:check` compares design/tokens.json with
 * mobile/src/ui/tokens.json — the two files this script writes together. It
 * does NOT re-run the capture. So a run with no credentials would drop every
 * player token from BOTH files, leave the check perfectly green, and the app
 * would fall back to whatever `theme.ts` does when a token is missing. The
 * design would drift off the site with nothing failing anywhere.
 *
 * So: a token whose provenance ends `@ watch` is kept from the previous file
 * when this run produced no watch capture, and its provenance is stamped with
 * the run that actually read it. A stale value that says it is stale beats a
 * missing one that says nothing.
 */
const carriedForward = [];
const watchCaptured = captures.phone?.watch != null;

if (!watchCaptured) {
  let previous = null;
  try {
    previous = JSON.parse(readFileSync(resolve(HERE, 'tokens.json'), 'utf8'));
  } catch {
    previous = null; // first run, or the file was never written
  }

  const previousTokens = previous?.tokens ?? {};
  const previousAt = previous?.$generated?.at ?? 'an earlier run';

  for (const [name, entry] of Object.entries(previousTokens)) {
    if (tokens[name] !== undefined) continue;

    const from = typeof entry?.from === 'string' ? entry.from : '';
    /*
     * The page key is the last `@ <word>` in the provenance string, and the
     * parenthetical that may follow it is deliberately ignored: `@ watch` and
     * `@ watch (tablet)` are both the watch page. Taking everything after the
     * last `@ ` missed the second form and silently dropped
     * `color.playerTimeMuted` from a carried-forward run — 114 tokens where
     * the signed-in run writes 116, with nothing anywhere reporting it.
     */
    const pageKeys = [...from.matchAll(/@\s+([A-Za-z0-9_-]+)/g)];
    const pageKey = pageKeys.length > 0 ? pageKeys[pageKeys.length - 1][1] : null;
    if (!AUTH_PAGE_KEYS.has(pageKey)) continue;

    // Do not stack the note on a value carried forward more than once; the
    // first date is the one that says when the value was actually read.
    const note = from.includes('carried forward from')
      ? from
      : `${from} (carried forward from ${previousAt}: ${signInError ?? 'no signed-in capture'})`;

    tokens[name] = { value: entry.value, from: note };
    carriedForward.push(name);
  }
}

const missing = REQUIRED.filter((t) => tokens[t] === undefined);
/*
 * A probe is only "missed" on the page it was declared for. Reporting an auth
 * probe as missing because the home page has no sign-in card would put a
 * permanent false alarm on every run, and a warning that is always wrong is a
 * warning nobody reads.
 */
const missedProbes = PROBES.filter((declared) => {
  // A probe on a page that never loaded is not a missed selector, it is a
  // missing page, and it is reported as that instead.
  if (AUTH_PAGE_KEYS.has(declared.page) && !watchCaptured) return false;
  const page = phone[declared.page ?? 'home'];
  return page?.probes?.[declared.name]?.selector == null;
}).map((declared) => `${declared.name} (on /${declared.page ?? ''})`);

console.log('\nTokens captured:', Object.keys(tokens).length);
if (missedProbes.length) console.log('Probes that matched nothing:', missedProbes.join(', '));
if (carriedForward.length) {
  console.log(
    `\nNo signed-in capture (${signInError}).\n` +
      `${carriedForward.length} player tokens were carried forward from the previous export ` +
      'and are marked as such in tokens.json.\n' +
      'Re-run with --login and --password to refresh them.',
  );
}

if (missing.length && !KEEP_GOING) {
  console.error('\nMissing required tokens:', missing.join(', '));
  console.error('Nothing written. Fix the probe selectors, or pass --keep-going to inspect the raw capture.');
  process.exit(1);
}

const generated = {
  at: new Date().toISOString(),
  base: BASE,
  tool: 'design/export-tokens.mjs',
  viewport: `${VIEWPORTS.phone.width}x${VIEWPORTS.phone.height}`,
  pages: PAGES.map((p) => (p.auth ? watchPath : p.path)).filter(Boolean),
  // Whether the paywalled capture happened, recorded in the file itself so a
  // reader can tell a fresh player token from one carried forward without
  // reading every provenance string.
  signedIn: watchCaptured,
  ...(watchCaptured ? {} : { signedInSkipped: signInError }),
  ...(carriedForward.length ? { carriedForward } : {}),
  note: 'Sizes are the phone-viewport values. Colours are viewport-independent. Raw captures for both viewports are kept below.',
};

mkdirSync(HERE, { recursive: true });
writeFileSync(
  resolve(HERE, 'tokens.json'),
  JSON.stringify({ $generated: generated, tokens, raw: captures }, null, 2) + '\n',
  'utf8',
);
console.log('Wrote design/tokens.json');

// The TypeScript module is generated in the same run rather than by a second
// script, so design/tokens.json and mobile/src/ui/tokens.ts cannot disagree.
const tree = {};
for (const [path, entry] of Object.entries(tokens)) {
  const parts = path.split('.');
  let node = tree;
  for (const part of parts.slice(0, -1)) node = node[part] ??= {};
  node[parts.at(-1)] = entry.value;
}

const jsonPath = resolve(REPO, 'mobile', 'src', 'ui', 'tokens.json');
mkdirSync(dirname(jsonPath), { recursive: true });
writeFileSync(jsonPath, JSON.stringify(tree, null, 2) + '\n', 'utf8');
console.log('Wrote mobile/src/ui/tokens.json');

if (missing.length) {
  console.error('\nWritten with --keep-going, and these are still missing:', missing.join(', '));
  process.exit(2);
}
