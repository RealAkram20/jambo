import { useCallback } from 'react';
import * as Google from 'expo-auth-session/providers/google';
import * as WebBrowser from 'expo-web-browser';

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
 * permission to call Google's APIs, which this product has no use for. So the
 * response type is `id_token` and nothing is stored from Google beyond what
 * the server derives from it.
 */

/*
 * Closes the browser tab when the app is resumed mid-flow.
 *
 * Without it an abandoned sign-in leaves the Custom Tab sitting on top of the
 * app on Android, so the viewer returns to a browser rather than to Jambo.
 * Called at module scope because it patches a listener, not per render.
 */
WebBrowser.maybeCompleteAuthSession();

export type GoogleSignInResult =
  | { kind: 'token'; idToken: string }
  /** The viewer backed out, or dismissed the tab. Not an error. */
  | { kind: 'cancelled' }
  /** Google answered, but not with an identity we can send on. */
  | { kind: 'failed' };

/**
 * Starts a Google sign-in and resolves with an ID token to POST.
 *
 * Returns a `start` of `null` when this build has no Google client configured,
 * which is how `SignInScreen` knows not to draw the button. A control that
 * cannot complete must not be on screen — that rule is the whole reason this
 * file exists.
 */
export function useGoogleSignIn(): { start: (() => Promise<GoogleSignInResult>) | null } {
  /*
   * Both ids are passed and Google picks by platform. The Android client is
   * what proves the caller is this app — it is keyed to the package name and
   * the signing certificate — and the Web client is the fallback for anything
   * that is not an Android build. Whichever is used ends up in the token's
   * `aud`, and the server accepts either.
   */
  const [request, , promptAsync] = Google.useIdTokenAuthRequest({
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
   * unresponsive. `CAN_SIGN_IN_WITH_GOOGLE` is the build-time half of the same
   * question.
   */
  return { start: CAN_SIGN_IN_WITH_GOOGLE && request !== null ? start : null };
}
