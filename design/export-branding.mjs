// export-branding.mjs — the app's logo, icon and loader, taken from the site.
//
//   node design/export-branding.mjs                          (local, the default)
//   node design/export-branding.mjs --base=https://jambofilms.com
//
// Writes into mobile/assets/branding/ and records what it took in
// design/branding.json, so every image the app ships can be traced back to the
// admin setting it came from.
//
// WHY SCRAPE RATHER THAN READ THE DATABASE
//
// These three are admin settings (`logo`, `favicon`, `preloader` — see
// app/Http/Controllers/Admin/SettingController.php), resolved through
// `branding_asset()` / `branded_logo()` in app/helpers.php. Reading them from
// the rendered HTML means this script needs no database credentials and works
// against any environment by URL — the same reasoning as export-tokens.mjs.
// It also picks up the *fallbacks*: if an admin setting is empty the page
// serves the bundled template asset, and that is genuinely what a viewer sees.
//
// NOTE ON DRIFT: local and production do not have to agree, and at the time of
// writing they did not. Whatever `--base` points at is what the app will ship.
import { writeFileSync, mkdirSync } from 'node:fs';
import { dirname, extname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const REPO = resolve(HERE, '..');
const OUT_DIR = resolve(REPO, 'mobile', 'assets', 'branding');

const args = Object.fromEntries(
  process.argv.slice(2).map((a) => {
    const [k, v] = a.replace(/^--/, '').split('=');
    return [k, v ?? true];
  }),
);

const BASE = (args.base ?? 'http://localhost/Jambo').toString().replace(/\/$/, '');

async function getHtml(path) {
  const res = await fetch(BASE + path, { headers: { Accept: 'text/html' } });
  if (!res.ok) throw new Error(`${path} answered ${res.status}`);
  return res.text();
}

/**
 * Each asset, and where it shows up in the rendered page.
 *
 * The selectors are deliberately the *rendered* markup rather than the setting
 * name, because that is the thing that is true for a viewer. `preloader` is
 * matched on its alt text, which `loader-component.blade.php` sets to "loader".
 */
const SOURCES = [
  {
    name: 'logo',
    page: '/login',
    // resources/views/layouts/jambo-auth.blade.php — the auth header brand.
    pattern: /<a[^>]*jambo-auth-header__brand[^>]*>\s*<img[^>]*src="([^"]+)"/i,
    describes: 'admin setting `logo`, via branded_logo()',
  },
  {
    name: 'favicon',
    page: '/login',
    pattern: /<link[^>]*rel="shortcut icon"[^>]*href="([^"]+)"/i,
    describes: 'admin setting `favicon`, via branding_asset()',
  },
  {
    name: 'preloader',
    page: '/',
    // Modules/Frontend/resources/views/components/loader-component.blade.php
    pattern: /<img[^>]*src="([^"]+)"[^>]*alt="loader"/i,
    describes: 'admin setting `preloader`, via branding_asset()',
  },
];

function absolute(url) {
  if (/^https?:\/\//i.test(url)) return url;

  // Settings are stored with the install's path prefix already on them
  // (`/Jambo/storage/...` on a subdirectory install), so a root-relative URL is
  // resolved against the ORIGIN, not against BASE — otherwise the prefix is
  // applied twice and every fetch 404s.
  const origin = new URL(BASE).origin;
  return url.startsWith('/') ? origin + url : `${BASE}/${url}`;
}

const record = { $generated: { at: new Date().toISOString(), base: BASE }, assets: {} };
mkdirSync(OUT_DIR, { recursive: true });

for (const source of SOURCES) {
  process.stdout.write(`  ${source.name.padEnd(10)} `);

  const html = await getHtml(source.page);
  const match = html.match(source.pattern);

  if (!match?.[1]) {
    console.log(`NOT FOUND on ${source.page}`);
    record.assets[source.name] = { error: `not found on ${source.page}` };
    continue;
  }

  const url = absolute(match[1].replace(/&amp;/g, '&'));
  const res = await fetch(url);

  if (!res.ok) {
    console.log(`${url} -> ${res.status}`);
    record.assets[source.name] = { url, error: `fetch ${res.status}` };
    continue;
  }

  const bytes = Buffer.from(await res.arrayBuffer());
  const ext = extname(new URL(url).pathname) || '.png';
  const file = `${source.name}${ext}`;

  writeFileSync(resolve(OUT_DIR, file), bytes);

  record.assets[source.name] = {
    file: `mobile/assets/branding/${file}`,
    url,
    bytes: bytes.length,
    from: source.describes,
  };

  console.log(`${file}  ${(bytes.length / 1024).toFixed(0)} KB  <- ${url}`);
}

writeFileSync(resolve(HERE, 'branding.json'), JSON.stringify(record, null, 2) + '\n', 'utf8');
console.log('\nWrote design/branding.json and mobile/assets/branding/');
