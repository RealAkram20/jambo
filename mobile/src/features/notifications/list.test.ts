import type { Notification } from '../../api/catalogue';
import {
  categoryForChip,
  emptyTitleFor,
  groupByDay,
  idsOf,
  NOTIFICATION_CHIPS,
  relativeTime,
  toneFor,
  toggle,
  unreadAmong,
} from './list';

const AT = new Date('2026-09-09T14:00:00.000Z').getTime();

function at(iso: string | null): Notification {
  return { id: iso ?? 'none', title: 't', message: 'm', created_at: iso } as Notification;
}

describe('the chips', () => {
  it('sends no category for All', () => {
    expect(categoryForChip('all')).toBeNull();
  });

  it('sends the server the four categories it accepts', () => {
    expect(NOTIFICATION_CHIPS.map((c) => c.category)).toEqual([
      null,
      'movies',
      'series',
      'account',
      'offers',
    ]);
  });

  /* The watchlist's filter already says "TV Shows". Two filters in one app
     disagreeing about the word is the kind of drift nobody files a bug for. */
  it('calls a series a TV Show, as the watchlist does', () => {
    expect(NOTIFICATION_CHIPS.find((c) => c.key === 'series')?.label).toBe('TV Shows');
  });
});

/**
 * The header's unread badge caches at `['notifications', 'unread']`, beside
 * the inbox's `['notifications', <category>]`.
 *
 * 🔴 **This exists because the two once shared a key and it crashed the app.**
 * The header read it with `useQuery` and the inbox with `useInfiniteQuery`;
 * whichever mounted first won the cache entry, and when the header won, the
 * inbox's `getNextPageParam` found no `pages` and threw "Cannot read property
 * 'length' of undefined". The screen could not be opened at all, from a change
 * made to the header.
 *
 * The badge sits under the same prefix on purpose, so invalidating
 * `['notifications']` refreshes it with the list. That is only safe while no
 * category is called "unread" — which is what this asserts.
 */
describe('the unread badge cannot collide with a chip', () => {
  it('has no category named unread', () => {
    expect(NOTIFICATION_CHIPS.map((c) => c.category)).not.toContain('unread');
  });

  /*
   * The All chip sends `null`, and `['notifications', null]` was the exact key
   * the header used to take. Named separately because it is the one that
   * actually collided.
   */
  it('still sends null for All, which is not the string unread', () => {
    expect(categoryForChip('all')).toBeNull();
  });
});

describe('the tile tone', () => {
  it('takes the colour the server sends', () => {
    expect(toneFor('success')).toBe('success');
    expect(toneFor('warning')).toBe('warning');
  });

  it('falls back to primary rather than to no tile', () => {
    expect(toneFor('chartreuse')).toBe('primary');
    expect(toneFor(null)).toBe('primary');
    expect(toneFor(undefined)).toBe('primary');
  });
});

describe('relativeTime', () => {
  it('reads the way the website reads, abbreviated', () => {
    expect(relativeTime('2026-09-09T13:58:00.000Z', AT)).toBe('2m ago');
    expect(relativeTime('2026-09-09T11:00:00.000Z', AT)).toBe('3h ago');
    expect(relativeTime('2026-09-08T14:00:00.000Z', AT)).toBe('1d ago');
    expect(relativeTime('2026-09-07T14:00:00.000Z', AT)).toBe('2d ago');
  });

  it('says now for anything under a minute', () => {
    expect(relativeTime('2026-09-09T13:59:30.000Z', AT)).toBe('now');
  });

  /* Handset clocks are wrong in both directions, and "in 3 minutes" on a
     notification that has already arrived reads as a bug. */
  it('says now for a timestamp in the future', () => {
    expect(relativeTime('2026-09-09T14:30:00.000Z', AT)).toBe('now');
  });

  it('climbs to weeks, months and years', () => {
    expect(relativeTime('2026-08-26T14:00:00.000Z', AT)).toBe('2w ago');
    expect(relativeTime('2026-06-09T14:00:00.000Z', AT)).toBe('3mo ago');
    expect(relativeTime('2024-09-09T14:00:00.000Z', AT)).toBe('2y ago');
  });

  it('draws nothing rather than the words Invalid Date', () => {
    expect(relativeTime('not a date', AT)).toBe('');
    expect(relativeTime('', AT)).toBe('');
    expect(relativeTime(null, AT)).toBe('');
  });
});

describe('groupByDay', () => {
  /* Local midnight, not UTC: the headings are calendar days in the device's
     own zone, which is what a viewer reading "Today" at 1am means. */
  const localNoon = new Date(2026, 8, 9, 12, 0, 0).getTime();
  const localIso = (d: Date) => d.toISOString();
  const daysAgo = (n: number, hour = 12) =>
    localIso(new Date(2026, 8, 9 - n, hour, 0, 0));

  it('files today, this week and earlier, in that order', () => {
    const sections = groupByDay(
      [at(daysAgo(0)), at(daysAgo(2)), at(daysAgo(30))],
      localNoon,
    );

    expect(sections.map((s) => s.title)).toEqual(['Today', 'This Week', 'Earlier']);
    expect(sections.map((s) => s.data.length)).toEqual([1, 1, 1]);
  });

  /* Rio's mockup files a one-day-old notification under This Week rather than
     under a Yesterday of its own. */
  it('puts yesterday under This Week, not under a heading of its own', () => {
    const sections = groupByDay([at(daysAgo(1))], localNoon);

    expect(sections.map((s) => s.title)).toEqual(['This Week']);
  });

  it('counts Today as the calendar day, not the last 24 hours', () => {
    // 1am today is 11 hours before "now" but is still today.
    const sections = groupByDay([at(localIso(new Date(2026, 8, 9, 1, 0, 0)))], localNoon);

    expect(sections.map((s) => s.title)).toEqual(['Today']);
  });

  it('omits a section with nothing in it', () => {
    const sections = groupByDay([at(daysAgo(0))], localNoon);

    expect(sections).toHaveLength(1);
    expect(sections[0]?.title).toBe('Today');
  });

  it('keeps the order the server sent inside a section', () => {
    const first = at(daysAgo(0, 9));
    const second = at(daysAgo(0, 8));

    const sections = groupByDay([first, second], localNoon);

    expect(sections[0]?.data).toEqual([first, second]);
  });

  /* A date the app cannot parse must not swallow the notification. */
  it('keeps a row whose timestamp is unreadable', () => {
    const sections = groupByDay([at('not a date'), at(null)], localNoon);

    expect(sections.map((s) => s.title)).toEqual(['Today']);
    expect(sections[0]?.data).toHaveLength(2);
  });

  it('returns nothing at all for an empty list', () => {
    expect(groupByDay([], localNoon)).toEqual([]);
  });
});

describe('the empty state', () => {
  it('names the chip a viewer is looking at', () => {
    expect(emptyTitleFor('offers')).toBe('Nothing under Offers');
    expect(emptyTitleFor('series')).toBe('Nothing under TV Shows');
  });

  it('says the inbox is empty when no chip is narrowing it', () => {
    expect(emptyTitleFor('all')).toBe('No notifications');
  });
});

/**
 * Long-press selection, added on Rio's ask of 2026-09-10: *"we can have the
 * long press event like long press to select and we can select multiple."*
 */
describe('the selection', () => {
  it('adds an id that is not in it and removes one that is', () => {
    const one = toggle(new Set(), 'a');
    expect([...one]).toEqual(['a']);

    expect([...toggle(one, 'a')]).toEqual([]);
  });

  /*
   * 🔴 The Set held in state must never be mutated in place. React compares by
   * reference, so `set.add(id); setSelected(set)` renders nothing — the first
   * row a viewer picks would stay unmarked and the second would appear to
   * work, which reads as a flaky device rather than as a bug.
   */
  it('leaves the set it was given untouched', () => {
    const before = new Set(['a']);

    const after = toggle(before, 'b');

    expect([...before]).toEqual(['a']);
    expect(after).not.toBe(before);
  });

  it('keeps the rest of the selection when one is toggled', () => {
    expect([...toggle(new Set(['a', 'b', 'c']), 'b')].sort()).toEqual(['a', 'c']);
  });
});

describe('idsOf', () => {
  const rows = (...ids: string[]) => ids.map((id) => ({ id, title: 't' }) as Notification);

  it('returns the selected ids in the order the list holds them', () => {
    expect(idsOf(rows('a', 'b', 'c'), new Set(['c', 'a']))).toEqual(['a', 'c']);
  });

  /*
   * A row deleted or filtered away while it was selected must not contribute
   * an id to a bulk request. Driving off the list rather than off the Set is
   * what makes that impossible, so this is the assertion that guards it.
   */
  it('drops a selected id the list no longer holds', () => {
    expect(idsOf(rows('a'), new Set(['a', 'gone']))).toEqual(['a']);
  });

  it('ignores a row the server sent without an id', () => {
    const nameless = { title: 't' } as Notification;

    expect(idsOf([nameless, ...rows('a')], new Set(['a']))).toEqual(['a']);
  });

  it('returns nothing when nothing is selected', () => {
    expect(idsOf(rows('a', 'b'), new Set())).toEqual([]);
  });
});

describe('unreadAmong', () => {
  const row = (id: string, read: boolean) => ({ id, title: 't', read }) as Notification;

  /* The count the bar's "Mark read" acts on, and the reason it is drawn at
     all: a selection of rows that are already read has nothing to mark. */
  it('counts only the selected rows that are still unread', () => {
    const items = [row('a', false), row('b', true), row('c', false)];

    expect(unreadAmong(items, new Set(['a', 'b']))).toBe(1);
    expect(unreadAmong(items, new Set(['a', 'c']))).toBe(2);
    expect(unreadAmong(items, new Set(['b']))).toBe(0);
  });

  /* The server omits `read` rather than sending false, and the row treats
     anything that is not exactly true as unread. The count must agree, or a
     freshly arrived notification would offer no way to mark it read. */
  it('treats a missing read flag as unread, as the row does', () => {
    const item = { id: 'a', title: 't' } as Notification;

    expect(unreadAmong([item], new Set(['a']))).toBe(1);
  });
});
