import { readFileSync } from 'node:fs';
import { join } from 'node:path';

import type { ExpoConfig } from 'expo/config';

/*
 * Read rather than imported, and that is not a style choice: Expo transpiles
 * this config file but not the modules it imports, so `import { tokens } from
 * './src/ui/tokens'` fails at config load with "Cannot find module". The JSON
 * beside it is the same data the app's theme uses.
 */
const tokens = JSON.parse(
  readFileSync(join(__dirname, 'src/ui/tokens.json'), 'utf8'),
) as { color: { bg: string } };

/**
 * The native configuration, in TypeScript so it can read the design tokens.
 *
 * This was `app.json` first, and the move is not cosmetic. The colour behind
 * the app — the window background the system paints before React has mounted
 * anything — has to match the colour the JS theme paints afterwards, or the
 * launch shows a flash of the wrong black on exactly the slower handsets this
 * app is for. As JSON, that meant the same hex typed in two files, and the
 * first version of this file already had them disagreeing: `#0b0d17` here
 * against the `#000000` the site actually renders. Reading `tokens.ts` makes
 * that class of drift impossible.
 *
 * `src/ui/tokens.ts` rather than `design/tokens.json` deliberately: EAS
 * uploads this directory, so a config reaching outside it builds locally and
 * fails in the cloud.
 */
const background = tokens.color.bg;

export default (): ExpoConfig => ({
  name: 'Jambo',
  slug: 'jambo',
  version: '1.0.0',
  orientation: 'default',
  scheme: 'jambo',
  userInterfaceStyle: 'dark',
  backgroundColor: background,

  /*
   * The launcher icon, built from the admin's own branding.
   *
   * `design/export-branding.mjs` pulls the `logo`, `favicon` and `preloader`
   * settings off the running site, and `design/build-app-icons.php` turns the
   * favicon into these two files — opaque for the legacy icon, inset inside
   * Android's adaptive safe zone for the foreground layer. Neither is drawn by
   * hand, so changing the mark in the admin and re-running both scripts is the
   * whole update path.
   */
  icon: './assets/icon.png',

  android: {
    package: 'com.jambofilms.app',
    predictiveBackGestureEnabled: false,
    softwareKeyboardLayoutMode: 'pan',
    permissions: ['INTERNET'],
    adaptiveIcon: {
      foregroundImage: './assets/adaptive-icon.png',
      backgroundColor: background,
    },
  },

  plugins: [
    'expo-secure-store',
    'expo-font',

    /*
     * Required for `userInterfaceStyle` and for the window background above to
     * take effect at all — without it, prebuild warns and both are ignored.
     */
    'expo-system-ui',

    /*
     * Light status bar icons, dark chrome.
     *
     * There is no `backgroundColor` here and there is no longer one in
     * `androidStatusBar` either: SDK 57 draws Android edge-to-edge, so the
     * status bar is transparent and takes the colour of whatever the app paints
     * behind it. The first version of this config set `androidStatusBar` and
     * `androidNavigationBar` colours; prebuild reported both as deprecated and
     * having no effect, which is the kind of thing that only surfaces when the
     * build is actually run.
     */
    ['expo-status-bar', { style: 'light' }],

    /*
     * The launch screen: the site's wordmark on the site's black.
     *
     * This is the one piece of branding that cannot be made dynamic, and the
     * reason is worth stating so nobody tries. Android shows this drawable
     * from the moment the process starts — before JavaScript exists, before a
     * network call is possible. It is read out of the APK, so it changes when
     * a new APK ships and at no other time. The site's animated preloader is
     * used instead everywhere the app is actually running (see `Loading` in
     * src/ui/components.tsx), which is where a GIF can play at all.
     */
    [
      'expo-splash-screen',
      {
        image: './assets/branding/logo.png',
        backgroundColor: background,
        imageWidth: 220,
        resizeMode: 'contain',
      },
    ],

    'expo-image',

    '@sentry/react-native',

    /*
     * Phone and TV from one codebase (ADR-0002). Inert unless EXPO_TV=1, which
     * only the TV EAS profile will set — and that profile is Phase 4. It is
     * here now so the door stays open without a native-toolchain change later.
     */
    '@react-native-tvos/config-tv',
  ],

  experiments: {
    reactCompiler: false,
  },
});
