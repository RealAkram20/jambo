/**
 * The profile screen's formatting decisions, with no React in them.
 *
 * Extracted for the same reason `profileMenu.ts` is: importing the screen
 * pulls in the auth provider and AsyncStorage, which Jest cannot stand up.
 * But the stronger reason is what these functions decide. Each one turns a
 * possibly-absent value into something a viewer reads as a fact about their
 * own account, and each has a wrong answer that looks entirely reasonable on
 * screen — a country that was never set showing as a country, a join date
 * that renders as "Invalid Date", a blank where a dash belongs.
 */

const MONTHS = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];

/**
 * What a detail row shows when there is nothing to show.
 *
 * An em dash, not an empty string and not "N/A". Empty reads as a rendering
 * fault — the viewer wonders whether the app failed to load their email — and
 * "N/A" says the field does not apply, when in fact it applies perfectly well
 * and simply has not been filled in.
 */
export const NOT_SET = '—';

/**
 * "Member Since", as the mockup shows it: month and year, no day.
 *
 * Built by hand rather than through `toLocaleDateString`, for the reason the
 * Account screen already records: Hermes ships different ICU data per build,
 * so a date that renders correctly on a desk can render as "Invalid Date" on
 * a handset — a class of bug that never appears where anyone is looking.
 *
 * Returns the em dash rather than null so a caller cannot forget to handle
 * absence and print "null" onto somebody's profile.
 */
export function memberSince(iso: string | null | undefined): string {
  if (iso === null || iso === undefined || iso === '') return NOT_SET;

  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return NOT_SET;

  const month = MONTHS[date.getMonth()];
  if (month === undefined) return NOT_SET;

  return `${month} ${date.getFullYear()}`;
}

/**
 * The display name for the viewer.
 *
 * First and last name when there is one, else the username — the website's own
 * `$user->full_name ?: $user->username`. Never a placeholder: an empty string
 * comes back for a caller to render nothing at all, because "Unknown" on
 * somebody's own profile is worse than a gap where their name goes.
 */
export function displayName(
  firstName: string | null | undefined,
  lastName: string | null | undefined,
  username: string | null | undefined,
): string {
  const full = [firstName, lastName]
    .map((part) => (part ?? '').trim())
    .filter((part) => part !== '')
    .join(' ');

  if (full !== '') return full;

  return (username ?? '').trim();
}

/**
 * The MIME type to send with a picked photo.
 *
 * The avatar endpoint validates `mimes:jpeg,png,webp,gif`, and a multipart
 * part with no content type arrives as `application/octet-stream` — which
 * fails that rule on a photo that is a perfectly good JPEG. The picker usually
 * reports the type; when it does not, this derives one from the file
 * extension rather than letting the part go out untyped.
 *
 * Falls back to `image/jpeg`, which is what a phone camera produces and what
 * the overwhelming majority of picked photos actually are.
 */
export function imageMimeType(uri: string, reported?: string | null): string {
  if (reported !== null && reported !== undefined && reported.startsWith('image/')) {
    return reported;
  }

  // Strip any query string before reading the extension — a content:// or a
  // cached file URI can carry one, and ".jpg?width=200" is not an extension.
  const withoutQuery = uri.split('?')[0] ?? '';
  const extension = withoutQuery.split('.').pop()?.toLowerCase() ?? '';

  switch (extension) {
    case 'png':
      return 'image/png';
    case 'webp':
      return 'image/webp';
    case 'gif':
      return 'image/gif';
    case 'jpg':
    case 'jpeg':
      return 'image/jpeg';
    default:
      return 'image/jpeg';
  }
}

/**
 * A filename for the uploaded part.
 *
 * Laravel's `image` and `mimes` rules read the extension as well as the
 * detected type, so a part named "upload" with no extension is rejected on a
 * valid image. The name is derived from the MIME type rather than from the
 * source URI, which on Android is often a `content://` id with no filename in
 * it at all.
 */
export function avatarFileName(mimeType: string): string {
  const extension = mimeType === 'image/jpeg' ? 'jpg' : mimeType.replace('image/', '');

  return `avatar.${extension}`;
}
