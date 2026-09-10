import type { Notification } from '../../api/catalogue';

/**
 * The five chips, and the category each asks the server for.
 *
 * `null` is All, which sends no `category` at all rather than sending one that
 * means everything — the endpoint's contract is that the parameter is absent
 * or is one of four values, and inventing a fifth would 422.
 *
 * **"Offers" is admin broadcasts**, by Rio's ruling of 2026-09-09. Jambo has
 * no promotional notification type; a broadcast is how a promotion reaches a
 * viewer today, so the chip points at the real thing and holds nothing until
 * somebody sends one.
 *
 * "TV Shows" rather than "Series" because that is what the watchlist's own
 * filter already says, and two filters in one app should not disagree about
 * what a television programme is called.
 */
export const NOTIFICATION_CHIPS = [
  { key: 'all', label: 'All', category: null },
  { key: 'movies', label: 'Movies', category: 'movies' },
  { key: 'series', label: 'TV Shows', category: 'series' },
  { key: 'account', label: 'Account', category: 'account' },
  { key: 'offers', label: 'Offers', category: 'offers' },
] as const;

export type NotificationChip = (typeof NOTIFICATION_CHIPS)[number]['key'];

export type NotificationCategory = 'movies' | 'series' | 'account' | 'offers';

/** The category to send for a chip, or null for All. */
export function categoryForChip(chip: NotificationChip): NotificationCategory | null {
  return NOTIFICATION_CHIPS.find((c) => c.key === chip)?.category ?? null;
}

/* ── the icon tile's tone ────────────────────────────────────────────── */

export type Tone = 'primary' | 'success' | 'warning' | 'danger' | 'info';

const TONES: readonly Tone[] = ['primary', 'success', 'warning', 'danger', 'info'];

/**
 * Which of the five tile tones a notification wears.
 *
 * The server sends the same `colour` the website paints the tile with, so a
 * payment receipt is green on both surfaces. Anything unrecognised falls back
 * to primary rather than to no tile: a notification with a broken colour is
 * still a notification, and an uncoloured square in a coloured list reads as a
 * rendering fault.
 */
export function toneFor(colour: string | null | undefined): Tone {
  if (typeof colour !== 'string') return 'primary';

  return TONES.includes(colour as Tone) ? (colour as Tone) : 'primary';
}

/* ── when it happened ────────────────────────────────────────────────── */

const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;
const WEEK = 7 * DAY;

/**
 * "2m ago", "3h ago", "1d ago".
 *
 * The website renders this column with Carbon's `diffForHumans()`, and this is
 * its short form — the same answer, abbreviated, which is what Rio's mockup
 * draws. Anything the clock cannot answer returns an empty string, and the row
 * then draws no timestamp rather than the word "Invalid Date".
 *
 * A future timestamp reads as "now". Clocks on handsets are wrong in both
 * directions, and "in 3 minutes" on a notification that has already arrived is
 * a bug report waiting to happen.
 */
export function relativeTime(iso: string | null | undefined, now: number = Date.now()): string {
  if (typeof iso !== 'string' || iso === '') return '';

  const when = new Date(iso).getTime();
  if (Number.isNaN(when)) return '';

  const elapsed = now - when;
  if (elapsed < MINUTE) return 'now';
  if (elapsed < HOUR) return `${Math.floor(elapsed / MINUTE)}m ago`;
  if (elapsed < DAY) return `${Math.floor(elapsed / HOUR)}h ago`;
  if (elapsed < WEEK) return `${Math.floor(elapsed / DAY)}d ago`;

  const weeks = Math.floor(elapsed / WEEK);
  if (weeks < 5) return `${weeks}w ago`;

  const months = Math.floor(elapsed / (30 * DAY));
  return months < 12 ? `${months}mo ago` : `${Math.floor(elapsed / (365 * DAY))}y ago`;
}

/* ── the day headings ────────────────────────────────────────────────── */

export type NotificationSection = {
  title: string;
  data: Notification[];
};

/** Midnight at the start of the day `at` falls in, in the device's zone. */
function startOfDay(at: number): number {
  const d = new Date(at);
  d.setHours(0, 0, 0, 0);
  return d.getTime();
}

/**
 * Today, This Week, Earlier — in that order, and empty sections omitted.
 *
 * Three headings rather than four: Rio's mockup files a one-day-old
 * notification under This Week, not under a Yesterday of its own, so this
 * follows it. "Today" is the calendar day rather than the last 24 hours,
 * because a viewer reading a heading at 1am means the date, not the interval.
 *
 * The input order is preserved inside each section. The server already sorted
 * the list newest first and re-sorting here would be a second opinion about an
 * order that is already correct.
 */
export function groupByDay(
  items: readonly Notification[],
  now: number = Date.now(),
): NotificationSection[] {
  const today = startOfDay(now);
  const weekAgo = today - 6 * DAY;

  const buckets: { title: string; data: Notification[] }[] = [
    { title: 'Today', data: [] },
    { title: 'This Week', data: [] },
    { title: 'Earlier', data: [] },
  ];

  for (const item of items) {
    const created = typeof item.created_at === 'string' ? new Date(item.created_at).getTime() : NaN;

    // A row whose timestamp is unreadable is not dropped. It sorts with the
    // newest, where the server put it, because the alternative is a
    // notification a viewer never sees because of a date-parsing failure.
    if (Number.isNaN(created) || created >= today) buckets[0]!.data.push(item);
    else if (created >= weekAgo) buckets[1]!.data.push(item);
    else buckets[2]!.data.push(item);
  }

  return buckets.filter((b) => b.data.length > 0);
}

/**
 * The empty state's line, which differs by why the list is empty.
 *
 * A viewer whose whole inbox is empty is told so. A viewer looking at an empty
 * chip has the other chips on screen beside it and needs no sentence about
 * them, which is the difference Rio's copy rule turns on.
 */
export function emptyTitleFor(chip: NotificationChip): string {
  if (chip === 'all') return 'No notifications';

  const label = NOTIFICATION_CHIPS.find((c) => c.key === chip)?.label ?? '';
  return `Nothing under ${label}`;
}

/**
 * Add or remove one id from the selection.
 *
 * A new Set every time, never a mutation of the one held in state. React
 * compares by reference, so `set.add(id); setSelected(set)` renders nothing —
 * the selection would appear to work on the row pressed second and never on
 * the first, which is the kind of bug that reads as a flaky device.
 */
export function toggle(current: ReadonlySet<string>, id: string): ReadonlySet<string> {
  const next = new Set(current);
  if (!next.delete(id)) next.add(id);
  return next;
}

/**
 * The ids of the selected rows, in the order the list holds them.
 *
 * Driven off the list rather than off the Set, so a row that has since been
 * deleted or filtered away cannot contribute an id to a bulk request. The
 * order matters only in that a partial failure stops somewhere a viewer can
 * make sense of: the top of what they picked.
 */
export function idsOf(
  items: readonly Notification[],
  selected: ReadonlySet<string>,
): string[] {
  return items
    .map((item) => item.id)
    .filter((id): id is string => typeof id === 'string' && selected.has(id));
}

/** How many of the selected rows are still unread — the count "Mark read" acts on. */
export function unreadAmong(
  items: readonly Notification[],
  selected: ReadonlySet<string>,
): number {
  return items.filter(
    (item) => typeof item.id === 'string' && selected.has(item.id) && item.read !== true,
  ).length;
}
