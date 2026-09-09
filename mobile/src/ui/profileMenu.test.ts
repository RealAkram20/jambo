import {
  activeRowFor,
  avatarInitial,
  openedRow,
  rememberOpenedRow,
  unreadBadge,
} from './profileMenu';

/**
 * The drawer's three decisions, tested away from the component.
 *
 * These are pure functions on purpose. Each of them decides something a
 * viewer reads as a fact about their own account — whose menu this is, how
 * many notifications are waiting, where they currently are — and each of them
 * has a wrong answer that looks perfectly reasonable on screen: a badge
 * reading zero, a monogram reading "U" for undefined, a menu that lights up
 * nothing. A render test would draw all three of those without complaining.
 */

describe('avatarInitial', () => {
  it('prefers the first name, which is what the website does', () => {
    expect(avatarInitial('Miiro', 'realakram207')).toBe('M');
  });

  it('falls back to the username when there is no first name', () => {
    expect(avatarInitial(undefined, 'realakram207')).toBe('R');
    expect(avatarInitial('', 'realakram207')).toBe('R');
  });

  /*
   * The case that produces a plausible-looking wrong answer. A first name of
   * spaces is not a first name, and trimming it is the difference between "R"
   * and a blank circle that reads as a failed image.
   */
  it('treats whitespace as absent rather than as a letter', () => {
    expect(avatarInitial('   ', 'realakram207')).toBe('R');
  });

  it('gives a question mark rather than an empty circle when it has nothing', () => {
    expect(avatarInitial(undefined, undefined)).toBe('?');
    expect(avatarInitial('', '')).toBe('?');
  });

  it('uppercases, because the circle is drawn for a capital', () => {
    expect(avatarInitial('miiro', undefined)).toBe('M');
  });
});

describe('unreadBadge', () => {
  /*
   * The two that matter, and they are both about NOT drawing a pill.
   *
   * Undefined is "the count has not loaded", zero is "there is nothing".
   * Rendering either one puts a badge on a menu row that a viewer will tap to
   * find an empty screen — and a badge that appears, reads 0, then changes to
   * 3 a moment later is worse than one that arrives once.
   */
  it('draws nothing while the count is unknown', () => {
    expect(unreadBadge(undefined)).toBeNull();
  });

  it('draws nothing for zero, rather than a pill reading 0', () => {
    expect(unreadBadge(0)).toBeNull();
  });

  it('draws a real count', () => {
    expect(unreadBadge(1)).toBe('1');
    expect(unreadBadge(42)).toBe('42');
  });

  /* The mockup's own cap. Three digits do not fit the pill. */
  it('caps at 99+, and 99 itself is not capped', () => {
    expect(unreadBadge(99)).toBe('99');
    expect(unreadBadge(100)).toBe('99+');
    expect(unreadBadge(4821)).toBe('99+');
  });

  /* A negative count is a server fault, not something to render as "-3". */
  it('draws nothing for a negative count', () => {
    expect(unreadBadge(-3)).toBeNull();
  });
});

describe('activeRowFor', () => {
  it('maps a screen to the row that opens it', () => {
    expect(activeRowFor('Security')).toBe('security');
    expect(activeRowFor('Devices')).toBe('devices');
    expect(activeRowFor('Notifications')).toBe('notifications');
    expect(activeRowFor('StreamingPreferences')).toBe('streaming');
  });

  /* Membership and Plans are the same place under two names — the website's
     label and the app's route. The map is where those two are reconciled. */
  it('lights Membership when the viewer is on the Plans screen', () => {
    expect(activeRowFor('Plans')).toBe('membership');
  });

  it('lights the Watchlist row when the viewer is on the Watchlist tab', () => {
    expect(activeRowFor('Watchlist')).toBe('watchlist');
  });

  it('highlights nothing on a screen the menu does not contain', () => {
    expect(activeRowFor('Home')).toBeNull();
    expect(activeRowFor('Title')).toBeNull();
    expect(activeRowFor('Search')).toBeNull();
  });

  it('highlights nothing when there is no screen underneath', () => {
    expect(activeRowFor(undefined)).toBeNull();
  });
});

describe('the last opened row', () => {
  /*
   * The store this covers exists because the component state it replaced was
   * destroyed on every dismissal, so the active row lit up never. The tests
   * that matter are therefore about it surviving and about it being clearable
   * — not about it holding a value, which was never the part that broke.
   */
  afterEach(() => {
    rememberOpenedRow(null);
  });

  it('remembers nothing until a row is opened', () => {
    expect(openedRow()).toBeNull();
  });

  it('survives being read many times, which is once per menu open', () => {
    rememberOpenedRow('security');

    expect(openedRow()).toBe('security');
    expect(openedRow()).toBe('security');
  });

  it('replaces rather than accumulates', () => {
    rememberOpenedRow('security');
    rememberOpenedRow('devices');

    expect(openedRow()).toBe('devices');
  });

  it('can be cleared', () => {
    rememberOpenedRow('devices');
    rememberOpenedRow(null);

    expect(openedRow()).toBeNull();
  });
});

describe('the rows that stopped being pending', () => {
  /*
   * Profile, Wallet and Refer & Earn shipped dimmed with a "Soon" tag while
   * their screens were being built in a parallel session, and went live on
   * 2026-09-09. These assertions are what stops the active-row map from
   * silently falling behind the rows again: a row can be given a destination
   * in the screen file without anyone remembering this map, and the only
   * symptom is a highlight that never appears.
   */
  it('lights the profile row from either profile destination', () => {
    expect(activeRowFor('ProfileEdit')).toBe('profile');
    expect(activeRowFor('Account')).toBe('profile');
  });

  it('lights the money rows', () => {
    expect(activeRowFor('Wallet')).toBe('wallet');
    expect(activeRowFor('Referrals')).toBe('refer');
  });
});
