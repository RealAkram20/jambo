/**
 * The two-factor enrolment flow's pure parts. No React, so they can be tested
 * without standing up a device.
 */

/**
 * The issuer an authenticator app shows beside the code.
 *
 * The server builds its QR with `config('app.name')`, which is `Jambo` on
 * every environment checked. This is a **cosmetic** label: TOTP verification
 * uses the secret alone, so a mismatch between the two clients would change
 * what an authenticator displays and nothing else. Kept as a constant rather
 * than fetched, because `GET /app/config` does not carry the site name and
 * adding a field to a live contract to render a caption is not a trade worth
 * making.
 */
export const TOTP_ISSUER = 'Jambo';

/**
 * The `otpauth://` URI, byte-for-byte as the website's QR encodes it.
 *
 * `TwoFactorAuthentication::qrCodeSvg()` calls
 * `Google2FA::getQRCodeUrl($issuer, $user->email, $secret)`, and
 * `pragmarx/google2fa`'s `Support\QRCode` builds:
 *
 *     otpauth://totp/{issuer}:{holder}?secret={secret}&issuer={issuer}
 *              &algorithm=SHA1&digits=6&period=30
 *
 * with `rawurlencode` on the issuer and the holder and **not** on the secret.
 * That is reproduced exactly rather than approximated, so a viewer who scans
 * on the website and a viewer who scans in the app end up with an identical
 * entry in the same authenticator, under the same name.
 *
 * `encodeURIComponent` is the JavaScript equivalent of `rawurlencode` for
 * every character that can appear in an issuer or an email address: both
 * follow RFC 3986 and both escape `@`, which is the one that matters here.
 *
 * Returns null when there is no secret. A QR drawn from an empty string is a
 * QR that silently enrols nothing.
 */
export function otpauthUri(
  secret: string | null | undefined,
  email: string | null | undefined,
  issuer: string = TOTP_ISSUER,
): string | null {
  if (typeof secret !== 'string' || secret.trim() === '') return null;

  const holder = typeof email === 'string' && email.trim() !== '' ? email.trim() : issuer;
  const company = encodeURIComponent(issuer);

  return (
    `otpauth://totp/${company}:${encodeURIComponent(holder)}` +
    `?secret=${secret.trim()}` +
    `&issuer=${company}` +
    '&algorithm=SHA1&digits=6&period=30'
  );
}

/**
 * The secret, grouped into fours for typing by hand.
 *
 * The website prints it raw inside a `<code>`. On a phone the manual key is
 * not the fallback it is on a desktop — it is the **main** path, because
 * nobody can scan a QR displayed on the device they are holding. A
 * 32-character unbroken string is one somebody will mistype, and every mistype
 * costs a full round trip through an authenticator to discover.
 *
 * Grouping is presentation only. `secretForEntry` never leaves this screen;
 * the value sent anywhere is always the original.
 */
export function groupSecret(secret: string | null | undefined): string {
  if (typeof secret !== 'string' || secret.trim() === '') return '';

  return (secret.trim().match(/.{1,4}/g) ?? []).join(' ');
}

/**
 * Is this six digits?
 *
 * The server validates `size:6` and the input is numeric, so the button is
 * disabled until the code could possibly be right. It is a courtesy, not a
 * check: the server is the authority and a wrong code still comes back as
 * `TWO_FACTOR_INVALID`.
 */
export function isCompleteCode(code: string): boolean {
  return /^\d{6}$/.test(code);
}

/**
 * Only digits, capped at six.
 *
 * Android's numeric keyboard still offers a decimal separator and some
 * keyboards paste spaces out of a notification, so what arrives is filtered
 * rather than trusted. Pasting "123 456" from an authenticator's notification
 * fills the field correctly instead of being refused.
 */
export function sanitiseCode(raw: string): string {
  return raw.replace(/\D/g, '').slice(0, 6);
}
