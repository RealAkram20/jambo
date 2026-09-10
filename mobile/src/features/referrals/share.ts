import type { ReferralDashboard } from '../../api/endpoints';

/**
 * Everything Refer & Earn decides, as pure functions.
 *
 * The share destinations are here rather than in the component because a
 * malformed share URL is invisible until somebody taps it and lands on a
 * broken page — the class of bug worth a test, and the cheapest possible one.
 */

/**
 * The message a friend receives.
 *
 * The link, not the code. A tapped link carries the referral through signup on
 * its own; a code has to be typed into the right box at the right moment, and
 * the website's own share control shares the link for the same reason. The
 * code is still on screen for someone who wants to read it out.
 */
export function shareMessage(data: ReferralDashboard): string | null {
  const link = data.share_url;
  if (typeof link !== 'string' || link.trim() === '') return null;
  return `Watch on Jambo Films: ${link.trim()}`;
}

export type ShareTarget = 'whatsapp' | 'facebook' | 'x';

/**
 * Where each share button actually goes.
 *
 * Public web share endpoints, on purpose. `whatsapp://` would need a
 * `canOpenURL` query declared in the Android manifest and shows nothing useful
 * when the app is missing; `https://wa.me/` opens the installed app when there
 * is one and the web client when there is not. Facebook's sharer takes a URL
 * and X's intent takes text, which is why they are built differently rather
 * than from one template.
 */
export function shareUrlFor(target: ShareTarget, data: ReferralDashboard): string | null {
  const link = data.share_url;
  if (typeof link !== 'string' || link.trim() === '') return null;

  const url = link.trim();
  const message = shareMessage(data) ?? url;

  if (target === 'whatsapp') return `https://wa.me/?text=${encodeURIComponent(message)}`;
  if (target === 'facebook') {
    // The sharer takes the URL alone; any text it is given is ignored.
    return `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
  }
  return `https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}`;
}

/**
 * Money, as a string, with its currency.
 *
 * **Never arithmetic.** The balance arrives as a string from a ledger that is
 * the only thing entitled to add money up, and this formats it and nothing
 * else — the same rule `WalletScreen` states.
 *
 * An em dash when the server sent nothing. `UGX 0` for an unknown balance
 * tells a viewer they have earned nothing, when the truth is that nobody
 * asked.
 */
export function money(currency: string | undefined, amount: string | null | undefined): string {
  if (typeof amount !== 'string' || amount.trim() === '') return '—';
  return `${currency ?? ''} ${groupDigits(amount.trim())}`.trim();
}

/**
 * "21000.00" → "21,000", which is what the website prints.
 *
 * `refer.blade.php` renders every one of these figures as
 * `number_format((float) $value, 0)` — grouped, no decimals. The app was
 * showing the raw ledger string, so the same balance read "UGX 21000.00" on a
 * phone and "UGX 21,000" in a browser.
 *
 * **Done as string surgery, not by parsing to a number.** The site casts to
 * float and gets away with it; an app should not, because a balance large
 * enough to lose precision is exactly the balance you must not misreport. This
 * drops the fractional part and groups the integer digits, touching neither.
 * Anything that is not a plain decimal number is returned untouched rather
 * than mangled.
 */
function groupDigits(value: string): string {
  const match = /^(-?)(\d+)(?:\.\d+)?$/.exec(value);
  if (match === null) return value;

  const [, sign = '', digits = ''] = match;
  return sign + digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * A count.
 *
 * A real zero is worth showing: "0 referrals" is true and useful, unlike a
 * zero standing in for a figure nobody has — which is why an absent value is
 * an em dash and not a zero.
 */
export function count(value: number | null | undefined): string {
  return typeof value === 'number' ? String(value) : '—';
}

/**
 * A percentage, trimmed the way the website trims it.
 *
 * `refer.blade.php` strips decimal zeros only: "10.50" becomes "10.5", while a
 * plain "10" keeps its integer zero and must not become "1". Reproduced here
 * so the two clients print the same terms rather than one saying "you earn 10%"
 * and the other "you earn 10.00%".
 */
export function percent(value: string | number | null | undefined): string | null {
  if (value === null || value === undefined) return null;

  const raw = String(value).trim();
  if (raw === '') return null;

  const trimmed = raw.includes('.') ? raw.replace(/0+$/, '').replace(/\.$/, '') : raw;
  return `${trimmed}%`;
}

/**
 * The one line of prose this screen keeps, and why it earns its place.
 *
 * Rio's copy rule of 2026-09-09 removes anything that restates its heading.
 * The mockup's "Share Jambo Films with friends and earn rewards when they
 * join" does exactly that. **This does not**: it carries the two percentages,
 * which are real, come from the server, and appear nowhere else on the screen.
 * It is the website's own sentence, shortened.
 *
 * Null when the server sent no terms, so the screen renders no line at all
 * rather than a sentence with a gap in it.
 */
export function termsLine(data: ReferralDashboard): string | null {
  const reward = percent(data.terms?.reward_percent);
  const discount = percent(data.terms?.discount_percent);

  if (reward === null && discount === null) return null;
  if (reward === null) return `They save ${discount} on their first payment.`;
  if (discount === null) return `You earn ${reward} of what they pay.`;

  return `They save ${discount}. You earn ${reward} of what they pay.`;
}
