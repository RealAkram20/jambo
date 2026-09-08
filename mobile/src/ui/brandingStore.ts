import AsyncStorage from '@react-native-async-storage/async-storage';
import { Directory, File, Paths } from 'expo-file-system';

import type { AppConfig } from '../api/endpoints';

/**
 * The admin's branding, downloaded once and kept on the device.
 *
 * **Why a real download and not an image cache.** `expo-image` will happily
 * cache a remote URL to disk, and for an ordinary poster that is the right
 * answer. It is the wrong answer here. That cache is an evictable LRU: the
 * library or the OS may clear it whenever storage gets tight, which on a
 * Tecno-class phone with a few downloaded films is not a rare event. A viewer
 * three days into an offline trip would open the app and find the logo gone —
 * and no network to fetch it back. Branding has to be something the app owns.
 *
 * So it is written to `Paths.document`, which Expo documents as the directory
 * safe from being deleted by the system, and rendered from `file://` — never
 * from the network at render time. The chain is:
 *
 *   downloaded file  ->  bundled asset  ->  (nothing else)
 *
 * with the network appearing only in `sync()`, which is allowed to fail.
 *
 * **How "when it changes, download it" works.** `GET /app/config` returns each
 * URL with a `?v=<mtime>` fingerprint, so a replaced logo is a different URL.
 * The manifest below remembers which URL each local file came from; a
 * mismatch is the download trigger and a match is a no-op. No polling, no
 * version protocol, and offline it simply never triggers.
 */

const MANIFEST_KEY = 'jambo.branding.manifest';
const DIR_NAME = 'branding';

export type BrandingKind = 'logo' | 'preloader';

/** What we hold locally: which URL it came from, and where it landed. */
type Entry = { url: string; uri: string };
export type BrandingManifest = Partial<Record<BrandingKind, Entry>>;

/**
 * The files as the UI should use them.
 *
 * A `file://` URI when we have one, `undefined` when we do not — and
 * `undefined` means "fall back to the bundled asset", never "fetch it".
 */
export type BrandingFiles = Partial<Record<BrandingKind, string>>;

function directory(): Directory {
  return new Directory(Paths.document, DIR_NAME);
}

export async function readManifest(): Promise<BrandingManifest> {
  try {
    const raw = await AsyncStorage.getItem(MANIFEST_KEY);
    return raw === null ? {} : (JSON.parse(raw) as BrandingManifest);
  } catch {
    // A corrupt manifest means we re-download, which is cheap and safe. It
    // must never stop the app starting.
    return {};
  }
}

export function filesFrom(manifest: BrandingManifest): BrandingFiles {
  const files: BrandingFiles = {};
  for (const kind of ['logo', 'preloader'] as const) {
    const entry = manifest[kind];
    if (entry !== undefined) files[kind] = entry.uri;
  }
  return files;
}

function urlsFrom(config: AppConfig | null): Partial<Record<BrandingKind, string>> {
  const branding = (
    config as { branding?: { logo_url?: string | null; preloader_url?: string | null } } | null
  )?.branding;

  const wanted: Partial<Record<BrandingKind, string>> = {};
  if (branding?.logo_url) wanted.logo = branding.logo_url;
  if (branding?.preloader_url) wanted.preloader = branding.preloader_url;
  return wanted;
}

/**
 * Bring the local copies in line with what the server is advertising.
 *
 * Returns the manifest to use — unchanged when there is nothing to do, and
 * unchanged when the network fails. Never throws: this runs on the launch
 * path, and a branding refresh must not be able to stop somebody reaching
 * their downloads.
 */
export async function syncBranding(config: AppConfig | null): Promise<BrandingManifest> {
  const current = await readManifest();
  const wanted = urlsFrom(config);

  if (Object.keys(wanted).length === 0) {
    return current;
  }

  let next = current;
  let changed = false;

  for (const kind of ['logo', 'preloader'] as const) {
    const url = wanted[kind];
    if (url === undefined) continue;

    // Same URL and the file is still there: nothing to do. The existence check
    // matters — a manifest entry pointing at a file somebody cleared is worse
    // than no entry, because it renders as a broken image rather than falling
    // back to the bundled one.
    const held = current[kind];
    if (held?.url === url && new File(held.uri).exists) continue;

    try {
      const dir = directory();
      if (!dir.exists) dir.create({ intermediates: true });

      // Staged, then moved into place. Expo documents that on Android the
      // response streams straight into the destination and a failure part way
      // leaves a partial file behind — so downloading directly over the live
      // logo would replace it with a truncated image. The move is the last
      // step and the only one that touches what the UI reads.
      const staging = new Directory(Paths.cache, DIR_NAME);
      if (!staging.exists) staging.create({ intermediates: true });

      // `idempotent` because a previous run killed mid-download can leave a
      // file in staging, and without it the next attempt rejects rather than
      // overwriting — branding that fails permanently after one bad network.
      const downloaded = await File.downloadFileAsync(url, staging, { idempotent: true });

      const target = new File(dir, `${kind}-${Date.now()}`);
      await downloaded.move(target);

      // The old file goes only after the new one is safely in place.
      if (held !== undefined) {
        try {
          const previous = new File(held.uri);
          if (previous.exists) previous.delete();
        } catch {
          // A leftover file costs a few KB. Losing the new one costs the brand.
        }
      }

      next = { ...next, [kind]: { url, uri: target.uri } };
      changed = true;
    } catch {
      // Offline, or the asset moved. Keep what we have — that is the whole
      // point of holding a local copy.
    }
  }

  if (changed) {
    try {
      await AsyncStorage.setItem(MANIFEST_KEY, JSON.stringify(next));
    } catch {
      // The files are on disk; only the record of them failed. They will be
      // re-downloaded next launch, which is a wasted request and nothing worse.
    }
  }

  return next;
}
