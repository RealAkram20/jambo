import { assetUrl, imageUrl } from './media';

/**
 * These cases are not invented. Each one is a real value seen on 2026-09-09:
 * the app-absolute paths came off the live jambofilms.com home page, and the
 * `picsum.photos` URLs are what the development database actually stores.
 *
 * The pair matters. The dev seed is entirely absolute external URLs, so every
 * one of these paths would look fine locally and produce a home screen with no
 * images at all on production. That asymmetry is the reason this file exists.
 */

// `src/config/env.ts` reads EXPO_PUBLIC_API_BASE_URL at import time and falls
// back to production, which is what these expectations are written against.
const ORIGIN = 'https://jambofilms.com';

describe('imageUrl', () => {
  it('makes an app-absolute path absolute — without this nothing renders on production', () => {
    // The exact shape MovieResource::card() emits for a locally stored poster.
    const url = imageUrl('/storage/gallery/Motor City/download (3).png', 88);

    expect(url?.startsWith(`${ORIGIN}/img/storage/gallery/`)).toBe(true);
    expect(url?.startsWith('/storage')).toBe(false);
  });

  it('routes through the /img proxy at a stepped width, as the website does', () => {
    const url = imageUrl('/storage/gallery/Moana/poster.png', 88);

    // 88dp asks for the 160 step: the proxy caches a handful of widths rather
    // than one per device, exactly as the site's srcset does.
    expect(url).toContain('/img/');
    expect(url).toContain('w=160');
    expect(url).toContain('fm=webp');
  });

  it('picks the smallest step that covers the requested width', () => {
    expect(imageUrl('/storage/a.png', 88)).toContain('w=160');
    expect(imageUrl('/storage/a.png', 200)).toContain('w=320');
    expect(imageUrl('/storage/a.png', 640)).toContain('w=640');
    // Above the largest step, clamp rather than ask for something uncached.
    expect(imageUrl('/storage/a.png', 4000)).toContain('w=1920');
  });

  it('leaves an off-origin URL alone — the proxy cannot fetch a remote source', () => {
    // Real value from the development database. Also the shape of the Dropbox
    // and Backblaze links the admin pastes to get around the upload limit.
    const external = 'https://picsum.photos/seed/76KGJoEE/500/750';

    expect(imageUrl(external, 88)).toBe(external);
  });

  it('does not double-encode a path that is already percent-encoded', () => {
    // Production stores the escape, not the space: neither media_url() nor
    // Laravel's url() encodes, and the live page serves `Into%20the%20Badlands`.
    // Encoding again gives %2520 and a 404 on most of the catalogue.
    const url = imageUrl('/storage/gallery/Into%20the%20Badlands/download%20(16).png', 88);

    expect(url).toContain('Into%20the%20Badlands');
    expect(url).not.toContain('%2520');
  });

  it('encodes a raw space to exactly one escape', () => {
    const url = imageUrl('/storage/gallery/Motor City/download (3).png', 88);

    expect(url).toContain('Motor%20City');
    expect(url).not.toContain('%2520');
    expect(url).not.toContain('Motor City');
  });

  it('returns null for the empty and missing cases rather than a broken URL', () => {
    // `media_url()` returns '' when a record has no image and no fallback, so
    // this is a value the app genuinely receives.
    expect(imageUrl('')).toBeNull();
    expect(imageUrl('   ')).toBeNull();
    expect(imageUrl(null)).toBeNull();
    expect(imageUrl(undefined)).toBeNull();
  });

  it('skips the proxy when no width is asked for', () => {
    // Asking the proxy for the original size is a transcode nobody needed.
    const url = imageUrl('/storage/gallery/Moana/poster.png');

    expect(url).toBe(`${ORIGIN}/storage/gallery/Moana/poster.png`);
    expect(url).not.toContain('/img/');
  });

  it('does not prefix /img twice if the server starts emitting proxy URLs', () => {
    // The real fix for all of this is server-side. When it lands, this helper
    // must degrade to a no-op rather than produce /img/img/.
    const url = imageUrl(`${ORIGIN}/img/storage/gallery/a.png?w=640&fm=webp`, 88);

    expect(url).not.toContain('/img/img/');
  });
});

/**
 * The player's URL, which is NOT an image URL.
 *
 * `POST /playback/sessions` usually returns an absolute, token-signed CDN
 * link — but not always. When no CDN zone claims the stored value,
 * `CdnUrlResolver::resolve()` returns it untouched, and this database stores
 * bare paths. Confirmed against the running API on 2026-09-09:
 *
 *   {"source":"file","url":"/jambo/movies/hidden-storm-29765.mp4", ...}
 *
 * A native player handed that fails with a URL error and no useful
 * diagnostic. It is the same class of fault as the image one above, and it
 * needs a different fix: the image proxy must never touch a video.
 */
describe('assetUrl', () => {
  it('leaves a signed CDN URL exactly as it arrived', () => {
    // A signed URL survives no edit at all: the signature covers the path and
    // the expiry, so a single re-encoded character makes it a 403.
    const signed = 'https://jambo.b-cdn.net/movies/film.mp4?token=abc123&expires=1757400000';

    expect(assetUrl(signed)).toBe(signed);
  });

  it('makes a bare path absolute against the API origin', () => {
    expect(assetUrl('/jambo/movies/hidden-storm-29765.mp4')).toBe(
      `${ORIGIN}/jambo/movies/hidden-storm-29765.mp4`,
    );
  });

  it('never routes a video through the image proxy', () => {
    // Asking an image resizer for a two-hour MP4 is not a small mistake, and
    // it is the failure mode of reusing `imageUrl` for this.
    const url = assetUrl('/jambo/movies/hidden-storm-29765.mp4');

    expect(url).not.toContain('/img/');
    expect(url).not.toContain('fm=webp');
  });

  it('gives nothing for a missing or empty source rather than a bare origin', () => {
    // An origin with no path is a URL that looks valid and 404s.
    expect(assetUrl(null)).toBeNull();
    expect(assetUrl(undefined)).toBeNull();
    expect(assetUrl('   ')).toBeNull();
  });
});
