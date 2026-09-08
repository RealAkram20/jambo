// export-tokens.mjs — the Streamit design system, read off the rendered site.
//
//   node design/export-tokens.mjs                      (live site, the default)
//   node design/export-tokens.mjs --base=http://127.0.0.1:8090
//   node design/export-tokens.mjs --keep-going         (write despite misses)
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
import { writeFileSync, mkdirSync, rmSync, existsSync } from 'node:fs';
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

/**
 * Public pages only, on purpose.
 *
 * Watch and episode pages sit behind login plus a subscription, and every
 * token slice 2a and 2b need is present on these three. When the player is
 * built, the player's own tokens want a signed-in capture and this script
 * grows a login step — the shape is in `D:\OS\tools\cdp-shots.mjs`.
 */
const PAGES = [
  { key: 'home', path: '/' },
  { key: 'movies', path: '/movie' },
  { key: 'pricing', path: '/pricing' },
  { key: 'login', path: '/login' },
];

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
const profile = `${process.env.TEMP ?? '/tmp'}\\jambo-tokens-profile`;
try { rmSync(profile, { recursive: true, force: true }); } catch { /* first run */ }

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
      parent.appendChild(el);
      const style = getComputedStyle(el, probe.pseudo ?? null);
      const values = {};
      for (const prop of probe.props) values[prop] = style.getPropertyValue(prop).trim();
      el.remove();
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
    for (const prop of probe.props) values[prop] = style.getPropertyValue(prop).trim();

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

for (const [vpName, vp] of Object.entries(VIEWPORTS)) {
  await call('Emulation.setDeviceMetricsOverride', {
    width: vp.width, height: vp.height, deviceScaleFactor: vp.scale, mobile: vp.mobile,
  });
  captures[vpName] = {};
  for (const page of PAGES) {
    const url = BASE + page.path;
    process.stdout.write(`  ${vpName.padEnd(6)} ${url} ... `);
    try {
      await goto(url);
      captures[vpName][page.key] = await evaluate(probePage, { probes: PROBES });
      console.log('ok');
    } catch (cause) {
      console.log(`FAILED (${cause.message})`);
      captures[vpName][page.key] = null;
    }
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

function toNumber(value) {
  if (!value) return null;
  const m = value.trim().match(/^(-?[\d.]+)px$/);
  return m ? Math.round(Number(m[1]) * 100) / 100 : null;
}

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

// ------------------------------------------------------------------ output

const missing = REQUIRED.filter((t) => tokens[t] === undefined);
/*
 * A probe is only "missed" on the page it was declared for. Reporting an auth
 * probe as missing because the home page has no sign-in card would put a
 * permanent false alarm on every run, and a warning that is always wrong is a
 * warning nobody reads.
 */
const missedProbes = PROBES.filter((declared) => {
  const page = phone[declared.page ?? 'home'];
  return page?.probes?.[declared.name]?.selector == null;
}).map((declared) => `${declared.name} (on /${declared.page ?? ''})`);

console.log('\nTokens captured:', Object.keys(tokens).length);
if (missedProbes.length) console.log('Probes that matched nothing:', missedProbes.join(', '));

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
  pages: PAGES.map((p) => p.path),
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
