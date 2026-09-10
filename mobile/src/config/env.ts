import { Platform } from 'react-native';

/**
 * Build-time configuration, read once.
 *
 * `EXPO_PUBLIC_*` values are inlined by Metro at build time, so these are
 * constants in the shipped bundle rather than anything a running app can be
 * talked into changing. Each EAS profile in `eas.json` sets them.
 */

export const API_BASE_URL = process.env.EXPO_PUBLIC_API_BASE_URL ?? 'https://jambofilms.com/api/v1';

export type Variant = 'play' | 'direct';

/**
 * Which build this is (ADR-0004).
 *
 * - `play` — the Google Play listing. Consumption only: nothing can be bought,
 *   and nothing may lead a viewer to a payment page.
 * - `direct` — the APK from the Jambo website. Carries PesaPal checkout.
 *
 * **The default is `play`, and that is the safe direction rather than the
 * likely one.** If the flag is ever missing or misspelt in a profile, this
 * decides whether an unconfigured build shows a checkout Google's Payments
 * policy forbids outside IN/KR/EEA/US — a removal risk — or hides a checkout
 * that should have been there, which is a bug report. One of those costs the
 * listing and the other costs a day.
 */
export const VARIANT: Variant =
  process.env.EXPO_PUBLIC_JAMBO_VARIANT === 'direct' ? 'direct' : 'play';

/** Whether this build may show anything that leads to a payment. */
export const CAN_SUBSCRIBE_IN_APP = VARIANT === 'direct';

/**
 * Empty in development and under Jest, which is how the whole observability
 * path stays inert without one.
 */
export const SENTRY_DSN = process.env.EXPO_PUBLIC_SENTRY_DSN ?? '';

/**
 * The Google OAuth clients this build may sign in with.
 *
 * **Two ids, and they are not interchangeable.** Google keys an ANDROID client
 * to the package name and the signing certificate, which is what proves the
 * request came from this app; the WEB client is the one the website already
 * uses. Which of them ends up in the token's `aud` depends on which the
 * request was made with, and the server accepts either — see
 * `config/services.php`.
 *
 * Empty means this build cannot sign in with Google, and the button is not
 * drawn. That is deliberate: the whole reason this work exists is that a
 * button was shipped which could only fail, so an unconfigured build must hide
 * it rather than offer it.
 */
export const GOOGLE_ANDROID_CLIENT_ID = process.env.EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID ?? '';
export const GOOGLE_WEB_CLIENT_ID = process.env.EXPO_PUBLIC_GOOGLE_WEB_CLIENT_ID ?? '';

/**
 * Whether this build has what it needs to start a Google sign-in at all.
 *
 * 🔴 **Android needs the ANDROID client id specifically — the web one is not
 * a substitute.** `expo-auth-session` throws
 * `Client Id property 'androidClientId' must be defined to use Google auth on
 * this platform` from inside the hook, which is during render, which takes the
 * sign-in screen down with it. Found on 2026-09-11 by building a dev client
 * with only the web id set: the button appeared and the screen died.
 *
 * So this asks the platform's own question rather than "is any id present".
 * An Android build with only a web client id can no more sign in than one with
 * no ids at all, and the honest thing is to hide the button in both cases.
 */
export const CAN_SIGN_IN_WITH_GOOGLE =
  Platform.OS === 'android' ? GOOGLE_ANDROID_CLIENT_ID !== '' : GOOGLE_WEB_CLIENT_ID !== '';
