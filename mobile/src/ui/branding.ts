import type { ImageSource } from 'expo-image';

import type { BrandingFiles } from './brandingStore';

/**
 * What `expo-image` accepts: a bundled asset (Metro turns `require()` into a
 * module id) or a remote source. Deliberately expo-image's own type rather
 * than React Native's `ImageSourcePropType` — the two are not interchangeable
 * under `exactOptionalPropertyTypes`, because RN's allows `uri?: string |
 * undefined` where expo-image requires a real string when the key is present.
 */
export type BrandSource = ImageSource | number;

/**
 * The app's branding: bundled now, server-driven when the API offers it.
 *
 * Both halves are deliberate.
 *
 * **Bundled**, because the app has to look like Jambo before it has spoken to
 * anything. The sign-in screen is the first thing a viewer sees and it is
 * reachable with no connection at all; a logo that arrives over the network is
 * a logo that is missing exactly when first impressions are formed. These files
 * come from `design/export-branding.mjs`, which reads the `logo`, `favicon`
 * and `preloader` settings off the running site — so they are the admin's own
 * uploads, not artwork invented here.
 *
 * **Server-driven**, because Rio's intent is that branding stays controllable
 * without shipping an APK. `GET /app/config` is already called on every launch
 * and is the natural carrier: when it starts returning branding URLs, the app
 * prefers them and the bundled copies become the offline fallback. Nothing else
 * has to change.
 *
 * **What can never be dynamic**, and it is worth writing down so it is not
 * attempted: the launcher icon and the native splash. Android reads both out
 * of the APK before any JavaScript runs. They change with a build.
 */

export const bundledLogo = require('../../assets/branding/logo.png') as number;

export const bundledPreloader = require('../../assets/branding/preloader.gif') as number;

/**
 * The wordmark, as the sign-in screen and any header shows it.
 *
 * Resolution order, and the order is the point: the file this device
 * downloaded, then the copy bundled into the APK. The network is never in this
 * path — `syncBranding()` does the fetching, on the launch path, where it is
 * allowed to fail. That is what keeps the app branded through an entirely
 * offline session, which is the case Rio named and the reason the download
 * exists at all.
 */
export function logoSource(files: BrandingFiles): BrandSource {
  return files.logo !== undefined ? { uri: files.logo } : bundledLogo;
}

/**
 * The loading animation — the site's own preloader.
 *
 * Same resolution order as the logo. Rendered through `expo-image` because
 * this is an animated GIF and React Native's own `Image` does not animate GIFs
 * on Android without an extra Fresco dependency; it would show a still first
 * frame, which reads as a broken spinner and only on real devices.
 */
export function preloaderSource(files: BrandingFiles): BrandSource {
  return files.preloader !== undefined ? { uri: files.preloader } : bundledPreloader;
}
