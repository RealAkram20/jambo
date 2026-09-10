import {
  activeRowFor,
  avatarInitial,
  groupOf,
  groupRows,
  membershipTitle,
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
  it('lights the profile row from the editor, which is now the only profile', () => {
    expect(activeRowFor('ProfileEdit')).toBe('profile');
  });

  it('lights the money rows', () => {
    expect(activeRowFor('Wallet')).toBe('wallet');
    expect(activeRowFor('Referrals')).toBe('refer');
  });


  /*
   * Six routes that no longer exist. Any of them lighting a row would mean a
   * screen had been quietly reinstated. Four were removed by Rio on
   * 2026-09-09:
   *
   *  - the Account hub, replaced by the Profile screen;
   *  - Continue Watching, because the home rail already answers it;
   *  - History, because it is collected to train the model rather than to be
   *    browsed;
   *  - Streaming preferences, because the settings are applied in the player.
   *
   * **Neither removal touched what the server does.**
   * `PlaybackBeatRecorder::record` writes `WatchHistoryItem` from the playback
   * heartbeat; the screen only ever read it back, and `GET /history` is still
   * served. `GET`/`PATCH /account/preferences` is likewise still served, and
   * the player is now its only writer — `video_quality` and `autoplay_next`
   * are set from `PlayerMenu`. "We deleted the screen" and "we stopped
   * collecting history" — or "we dropped the preference" — are one careless
   * sentence apart, and this is the note that keeps them apart.
   *
   * The other two went on 2026-09-10 and **neither lost anything**, which is
   * what separates them from the four above:
   *
   *  - `Profile`, the read-only profile, which showed a subset of the fields
   *    the editor already displayed. The menu opens the editor now and Member
   *    since crossed over as a caption. Audit section 4.1.
   *  - `Invoice`, which became a sheet over the order history rather than a
   *    page you travel to. Same document, no route. Audit section 4.3.
   */
  it('knows nothing about the six removed routes', () => {
    expect(activeRowFor('Account')).toBeNull();
    expect(activeRowFor('ContinueWatching')).toBeNull();
    expect(activeRowFor('History')).toBeNull();
    expect(activeRowFor('StreamingPreferences')).toBeNull();
    expect(activeRowFor('Profile')).toBeNull();
    expect(activeRowFor('Invoice')).toBeNull();
  });
});

describe('the three groups', () => {
  /*
   * The grouping is the audit's §6 and it is a reading order, not a feature:
   * no row moved to a different destination and no screen was added or
   * removed. What these assertions protect is the two ways the grouping can
   * silently lose a row — a heading drawn over nothing, and a row assigned to
   * no group at all — because neither of them raises anything anywhere.
   */
  const ROWS = [
    { key: 'watchlist' },
    { key: 'profile' },
    { key: 'security' },
    { key: 'devices' },
    { key: 'notifications' },
    { key: 'billing' },
    { key: 'wallet' },
    { key: 'refer' },
  ];

  it('reads Account, then Viewing, then Money', () => {
    expect(groupRows(ROWS).map((group) => group.label)).toEqual(['Account', 'Viewing', 'Money']);
  });

  it('puts every row in exactly one group, and loses none of them', () => {
    const grouped = groupRows(ROWS).flatMap((group) => group.rows.map((row) => row.key));

    expect(grouped).toHaveLength(ROWS.length);
    expect(new Set(grouped)).toEqual(new Set(ROWS.map((row) => row.key)));
  });

  it('groups them the way the plan does', () => {
    expect(groupRows(ROWS).map((group) => group.rows.map((row) => row.key))).toEqual([
      ['profile', 'security', 'devices'],
      ['watchlist', 'notifications'],
      ['billing', 'wallet', 'refer'],
    ]);
  });

  /*
   * Refer & Earn is drawn only when the admin's referral switch is on, so this
   * is a real configuration rather than a hypothetical: MONEY is legitimately
   * two rows on some accounts.
   */
  it('keeps a group that lost a conditional row', () => {
    const withoutReferrals = ROWS.filter((row) => row.key !== 'refer');

    expect(groupRows(withoutReferrals).map((group) => group.label)).toEqual([
      'Account',
      'Viewing',
      'Money',
    ]);
  });

  /* A heading over nothing reads as a feature that failed to load. */
  it('does not draw a group whose rows are all absent', () => {
    const labels = groupRows([{ key: 'profile' }, { key: 'security' }]).map((g) => g.label);

    expect(labels).toEqual(['Account']);
  });

  /*
   * The one that matters most. Adding a row to the menu and forgetting to
   * assign it a group is the obvious mistake; under a design that filtered by
   * membership the only symptom would be a row that silently does not exist.
   */
  it('still draws a row it has never heard of, under no heading', () => {
    const groups = groupRows([{ key: 'profile' }, { key: 'gift-cards' }]);

    expect(groups.map((group) => group.label)).toEqual(['Account', null]);
    expect(groups[1]?.rows).toEqual([{ key: 'gift-cards' }]);
  });

  /*
   * Membership is the promoted card, not a row. If it ever appears in the row
   * list again it lands in the unlabelled trailing group, which is visible on
   * screen — that is the intended way to notice, and this pins the reason.
   */
  it('assigns no group to membership, because it is the card', () => {
    expect(groupOf('membership')).toBeNull();
  });
});

describe('membershipTitle', () => {
  /*
   * Three states, and the third is the one that costs something. Saying "No
   * active membership" to a paying viewer — even for the frame before
   * `/subscription` answers — is the app telling somebody a falsehood about
   * their own money.
   */
  it('says the plan name once it has one', () => {
    expect(membershipTitle('Premium', true)).toBe('Premium');
    expect(membershipTitle('Premium', false)).toBe('Premium');
  });

  it('says Membership while the subscription is still loading', () => {
    expect(membershipTitle(undefined, false)).toBe('Membership');
  });

  it('says there is none only once the server has answered', () => {
    expect(membershipTitle(undefined, true)).toBe('No active membership');
  });

  /* A blank plan name is not a plan name. Rendering it leaves a card with a
     date on it and nothing to say what the date is for. */
  it('treats a blank name as absent', () => {
    expect(membershipTitle('   ', true)).toBe('No active membership');
    expect(membershipTitle('   ', false)).toBe('Membership');
  });
});
