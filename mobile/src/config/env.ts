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
