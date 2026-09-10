import { API_BASE_URL } from '../config/env';

/**
 * Turning a catalogue image field into something a handset can actually load.
 *
 * The API's resources pass every image through PHP's `media_url()`
 * (`app/helpers.php:94`), which has two properties that are fine for a browser
 * on the same origin and wrong for an app:
 *
 * 1. **It returns app-absolute paths unchanged.** A poster stored as
 *    `/storage/gallery/Moana/download (16).png` comes back exactly like that —
 *    no scheme, no host. A browser resolves it against the page it is on; an
 *    app has no page. Verified against production on 2026-09-09: the live home
 *    page serves `/storage/gallery/...` for images that go through
 *    `media_url()`, so **without this helper no catalogue image would render
 *    in the app at all.** It is invisible on the development database, whose
 *    seed uses absolute `picsum.photos` URLs.
 *
 * 2. **It returns the original upload, at whatever size it was uploaded.** The
 *    website's own cards do not do this: `card-style.blade.php` ships
 *    `media_img($cardImage, 640)` plus a `srcset`, which routes through the
 *    site's `/img` proxy and gets back a resized WebP. A home screen of
 *    thirteen rails of full-size originals into 88dp cards is the difference
 *    between a screen that loads on MTN data and one that does not.
 *
 * Both are fixed here rather than server-side because Phase 2 is scoped to
 * touch no PHP. **The better fix is in the resources** — one place, every
 * client, and the app then stops needing to know the convention. This file is
 * written so that when that happens it degrades to a no-op: an already
 * absolute, already proxied URL passes straight through.
 */

/** The origin the API is served from, e.g. `http://10.0.2.2:8095`. */
const ORIGIN = (() => {
  const match = API_BASE_URL.match(/^(https?:\/\/[^/]+)/i);
  return match?.[1] ?? '';
})();

/**
 * The path the API is mounted under, minus `/api/v1`.
 *
 * A subdirectory install (`http://localhost/Jambo/api/v1`) puts the app's
 * images under `/Jambo/img/...`, not `/img/...`. The website hits the same
 * problem and solves it in `media_img()` by stripping the base path; the app
 * has to add it back.
 */
const BASE_PATH = (() => {
  const rest = API_BASE_URL.slice(ORIGIN.length).replace(/\/api\/v1\/?$/i, '');
  return rest.replace(/\/$/, '');
})();

/** Widths the `/img` proxy is asked for. Kept to a few so it caches well. */
const WIDTH_STEPS = [160, 320, 480, 640, 960, 1280, 1920] as const;

const LARGEST_STEP = WIDTH_STEPS[WIDTH_STEPS.length - 1] ?? 1920;

function stepFor(width: number): number {
  return WIDTH_STEPS.find((w) => w >= width) ?? LARGEST_STEP;
}

/**
 * Resolve a catalogue image URL, optionally at a target width.
 *
 * `width` is the width the image will be *drawn* at, in dp — pass the layout
 * width, not a guess. The device's pixel ratio is applied by the caller
 * through `posterWidth`/`stillWidth` below, because only the caller knows
 * whether it is drawing a poster or a backdrop.
 *
 * External URLs are returned untouched. That is not laziness: the site's own
 * `media_img()` passes them through too, because the Glide proxy cannot fetch
 * a remote source. Several titles in this catalogue are external links the
 * admin pasted to get around the upload limit, so this path is real.
 */
export function imageUrl(raw: string | null | undefined, width?: number): string | null {
  if (raw === null || raw === undefined) return null;

  const value = raw.trim();
  if (value === '') return null;

  // Absolute and off-origin: an admin-pasted Dropbox, Backblaze or stock URL.
  // The proxy cannot resize it and the app must not mangle it.
  if (/^https?:\/\//i.test(value)) {
    return ORIGIN !== '' && value.startsWith(ORIGIN) ? proxied(stripOrigin(value), width) : value;
  }

  // App-absolute (`/storage/...`) or a bare legacy filename. Either way the
  // path is relative to the site's public root once the leading slash is gone.
  return proxied(value.replace(/^\/+/, ''), width);
}

function stripOrigin(absolute: string): string {
  const path = absolute.slice(ORIGIN.length).replace(/^\/+/, '');
  // Already proxied — a second `/img/` prefix would 404.
  return path;
}

/**
 * Build the `/img` URL, mirroring `media_img()` in `app/helpers.php`.
 *
 * Without a width the image is returned at its original size through a plain
 * absolute URL rather than the proxy. That is deliberate: the proxy exists to
 * resize, and asking it for the original is a transcode nobody needed.
 */
function proxied(path: string, width?: number): string {
  if (ORIGIN === '') return `/${path}`;

  // Already a proxy URL (the server may start emitting them). Leave it alone.
  if (path.startsWith('img/') || path.startsWith(`${BASE_PATH.replace(/^\//, '')}/img/`)) {
    return `${ORIGIN}${BASE_PATH}/${path}`;
  }

  const encoded = path.split('/').map(encodeSegment).join('/');

  if (width === undefined) {
    return `${ORIGIN}${BASE_PATH}/${encoded}`;
  }

  return `${ORIGIN}${BASE_PATH}/img/${encoded}?w=${stepFor(Math.ceil(width))}&fm=webp`;
}

/**
 * Percent-encode one path segment, idempotently.
 *
 * The stored paths are **already encoded**: production serves
 * `/storage/gallery/Into%20the%20Badlands/download%20(16).png`, and neither
 * `media_url()` nor Laravel's `url()` encodes anything, so that `%20` is in
 * the database. Encoding it again yields `Into%2520the%2520Badlands` and a
 * 404 on every title whose folder has a space in it — which, in this
 * catalogue, is most of them.
 *
 * Decoding first makes the operation idempotent: a raw space and an existing
 * `%20` both end up as exactly one `%20`. `decodeURIComponent` throws on a
 * malformed escape (a literal `%` in a filename), so a failure means the
 * segment was never encoded and is used as-is.
 */
function encodeSegment(segment: string): string {
  let decoded = segment;
  try {
    decoded = decodeURIComponent(segment);
  } catch {
    decoded = segment;
  }
  return encodeURIComponent(decoded);
}

/**
 * An absolute URL for a non-image asset on the site, with no resizing.
 *
 * Added for the player. `POST /playback/sessions` returns a `url` that is
 * usually an absolute, token-signed CDN link — but not always: when no CDN
 * zone claims it, `CdnUrlResolver::resolve()` returns the stored value
 * untouched, and this database stores bare paths like
 * `/jambo/movies/hidden-storm-29765.mp4`. Confirmed against the running API,
 * not inferred. A native player handed that string fails with a URL error and
 * no useful diagnostic.
 *
 * This is the same class of fault `imageUrl` above exists for, so it reuses
 * the same origin derivation rather than a second copy of it — but it must
 * NOT be `imageUrl`, because that routes through the site's `/img` proxy and
 * asking an image resizer for a two-hour MP4 is not a small mistake.
 */
export function assetUrl(raw: string | null | undefined): string | null {
  if (raw === null || raw === undefined) return null;

  const value = raw.trim();
  if (value === '') return null;

  // Already absolute: a signed CDN URL, or an admin-pasted external link.
  // Handed over exactly as received — a signed URL survives no edit at all,
  // since the signature covers the path and the expiry.
  if (/^https?:\/\//i.test(value)) return value;

  if (ORIGIN === '') return value;

  return `${ORIGIN}${BASE_PATH}/${value.replace(/^\/+/, '')}`;
}
