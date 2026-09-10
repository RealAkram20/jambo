import { useCallback } from 'react';

import { CAN_SIGN_IN_WITH_GOOGLE, GOOGLE_ANDROID_CLIENT_ID, GOOGLE_WEB_CLIENT_ID } from '../config/env';

/**
 * Getting a Google ID token, in the system browser.
 *
 * **This exists because the button did not.** `SignInScreen` drew a "Google"
 * control whose handler was `navigation.navigate('SignIn')` — it navigated to
 * the screen it was already on, so tapping it did nothing at all. The server
 * half has been built and correct since 1.8.23: `POST /api/v1/auth/google`
 * verifies the token with Google, checks the audience against our own OAuth
 * clients, refuses an unverified address and issues a device-bound session.
 * The only missing piece was the app asking Google for a token. Rio found it
 * on the live build, 2026-09-11.
 *
 * **The system browser, not the WebView this app already has.** Google refuses
 * OAuth inside embedded WebViews and answers `disallowed_useragent`, so the
 * `react-native-webview` that PesaPal checkout uses cannot be reused here.
 * `expo-auth-session` opens a Chrome Custom Tab, which is what Google asks
 * for and what keeps the viewer's existing Google session available to them.
 *
 * **An ID token, not an access token.** The server wants proof of WHO this is,
 * signed by Google and checkable without trusting us; an access token would be
 * permission to call Google's APIs, which this product has no use for.
 *
 * ── 🔴 Why this file loads its dependency by hand ──────────────────────
 *
 * `import * as Google from 'expo-auth-session/providers/google'` at the top of
 * this file **crashed the app on its first screen**, and Rio hit it minutes
 * after the feature was committed: `expo-auth-session` pulls in
 * `expo-application`, which is a NATIVE module, and a dev client built before
 * that dependency existed does not contain it. The failure is
 * `Cannot find native module 'ExpoApplication'`, thrown while `SignInScreen`
 * renders — so the app cannot reach sign-in at all, which is every screen.
 *
 * A static import is loaded when this module is, and this module is loaded
 * because the sign-in screen imports it. Nothing about the button being hidden
 * helps: the crash happens before any of that is evaluated.
 *
 * So the dependency is required at runtime and only when it can work. Two
 * guards, and both are needed:
 *
 *  - `CAN_SIGN_IN_WITH_GOOGLE` — this build has no client id, so there is
 *    nothing to load it for;
 *  - the `try`/`catch` — a build that HAS ids can still be running in a dev
 *    client that predates the native module, and a missing Google button is a
 *    survivable state where a white screen is not.
 *
 * The rule this is an instance of: **a native module added to an Expo app is
 * not available until something rebuilds the client, and reaching for one that
 * is missing is a crash rather than a null.** Anything optional must be
 * required lazily behind a guard.
 */

type GoogleProviders = typeof import('expo-auth-session/providers/google');
type WebBrowserModule = typeof import('expo-web-browser');

/**
 * The provider module, or null when it cannot be loaded here.
 *
 * Resolved once at module scope rather than per render: the answer cannot
 * change while the app is running, and a `require` inside a hook would run on
 * every render of the sign-in screen.
 */
const google: GoogleProviders | null = (() => {
  if (!CAN_SIGN_IN_WITH_GOOGLE) return null;

  try {
    /* eslint-disable-next-line @typescript-eslint/no-require-imports -- see the note above: a static import crashes a dev client without the native module. */
    const providers = require('expo-auth-session/providers/google') as GoogleProviders;

    /*
     * Closes the browser tab when the app is resumed mid-flow. Without it an
     * abandoned sign-in leaves the Custom Tab on top of the app on Android, so
     * the viewer returns to a browser rather than to Jambo. Inside the guard
     * because it is a second native module.
     */
    /* eslint-disable-next-line @typescript-eslint/no-require-imports -- as above. */
    (require('expo-web-browser') as WebBrowserModule).maybeCompleteAuthSession();

    return providers;
  } catch {
    return null;
  }
})();

/** Whether this build can start a Google sign-in at all. */
export const GOOGLE_AVAILABLE = google !== null;

export type GoogleSignInResult =
  | { kind: 'token'; idToken: string }
  /** The viewer backed out, or dismissed the tab. Not an error. */
  | { kind: 'cancelled' }
  /** Google answered, but not with an identity we can send on. */
  | { kind: 'failed' };

/**
 * Starts a Google sign-in and resolves with an ID token to POST.
 *
 * **Only call this from a component that is mounted when `GOOGLE_AVAILABLE`.**
 * It calls a hook from the provider module, which does not exist otherwise;
 * `SignInScreen` guards on the same constant. A control that cannot complete
 * must not be on screen — that rule is the whole reason this file exists.
 */
export function useGoogleSignIn(): { start: (() => Promise<GoogleSignInResult>) | null } {
  /*
   * Both ids are passed and Google picks by platform. The Android client is
   * what proves the caller is this app — it is keyed to the package name and
   * the signing certificate — and the Web client is the fallback for anything
   * that is not an Android build. Whichever is used ends up in the token's
   * `aud`, and the server accepts either.
   *
   * The non-null assertion is what the guard above buys: this hook is only
   * reached from a component the caller mounted behind `GOOGLE_AVAILABLE`.
   */
  const [request, , promptAsync] = google!.useIdTokenAuthRequest({
    ...(GOOGLE_ANDROID_CLIENT_ID === '' ? {} : { androidClientId: GOOGLE_ANDROID_CLIENT_ID }),
    ...(GOOGLE_WEB_CLIENT_ID === '' ? {} : { webClientId: GOOGLE_WEB_CLIENT_ID }),
  });

  const start = useCallback(async (): Promise<GoogleSignInResult> => {
    const outcome = await promptAsync();

    /*
     * `dismiss` and `cancel` are the viewer changing their mind, and they must
     * not surface as an error: an alert saying sign-in failed because somebody
     * pressed back is the app blaming them for a decision.
     */
    if (outcome.type === 'dismiss' || outcome.type === 'cancel') return { kind: 'cancelled' };

    if (outcome.type !== 'success') return { kind: 'failed' };

    /*
     * The token comes back on `params.id_token` for an implicit id_token
     * request. Checked rather than asserted: an `authentication` object with
     * no id token is a real outcome when a provider is misconfigured, and it
     * would otherwise be sent to the server as the string "undefined".
     */
    const idToken = outcome.params?.['id_token'] ?? outcome.authentication?.idToken;

    return typeof idToken === 'string' && idToken !== ''
      ? { kind: 'token', idToken }
      : { kind: 'failed' };
  }, [promptAsync]);

  /*
   * `request` is null until the discovery document has loaded, and pressing
   * before then does nothing — so the button is disabled rather than
   * unresponsive.
   */
  return { start: request !== null ? start : null };
}
