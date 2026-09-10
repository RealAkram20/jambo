/**
 * The profile menu's three decisions, with no React in them.
 *
 * They live apart from `ProfileDrawer.tsx` for the reason `media.ts` and
 * `metrics.ts` do: importing the component pulls in `AuthProvider`, which
 * pulls in AsyncStorage, which needs a native module Jest does not have. A
 * pure module is a module that can be tested without standing up a device.
 *
 * Each of these decides something a viewer reads as a fact about their own
 * account, and each has a wrong answer that looks entirely reasonable on
 * screen — a badge reading zero, a monogram reading nothing, a menu that
 * lights up the wrong row. That is what makes them worth extracting.
 */

/**
 * The initial the website puts in its own sidebar avatar.
 *
 * `_sidebar.blade.php` computes exactly this — first name, else username, else
 * `?` — and it is the reason the drawer does not need a stock photograph for
 * an account that has never uploaded one. The mockup shows a face; most
 * accounts do not have one, and the honest fallback was already designed on
 * the website rather than invented here.
 */
export function avatarInitial(firstName?: string, username?: string): string {
  const source = (firstName ?? '').trim() !== '' ? (firstName ?? '') : (username ?? '');
  const first = source.trim().charAt(0);

  return first === '' ? '?' : first.toUpperCase();
}

/**
 * The unread count as the badge draws it, or null for no badge at all.
 *
 * Null rather than "0" is the whole point. A pill reading zero is a pill
 * saying "you have nothing", which is noise on a menu somebody opened to get
 * somewhere else, and a pill drawn while the count is still loading would
 * flicker from wrong to right. The mockup's own badge reads "99+", so the cap
 * is the mockup's rather than a number chosen here.
 */
export function unreadBadge(count: number | undefined): string | null {
  if (count === undefined || count <= 0) return null;

  return count > 99 ? '99+' : String(count);
}

/**
 * Which row to light up, given the screen underneath the drawer.
 *
 * The website marks its active tab from `$activeTab`; the app reads the route
 * it was opened from. A menu that never shows where you are makes a viewer
 * open a screen to find out they were already on it.
 */
export function activeRowFor(routeName: string | undefined): string | null {
  if (routeName === undefined) return null;

  const map: Record<string, string> = {
    Watchlist: 'watchlist',
    /* None of History, Continue Watching or Streaming preferences is here,
       because none is a row: Rio removed all three on 2026-09-09. The home
       rail already answers Continue Watching, history is collected to train
       the model rather than to be browsed, and the streaming settings are
       applied in the player itself. A route name that lit a row again would
       mean a screen had been quietly reinstated, which is what the test
       asserts. */
    Profile: 'profile',
    ProfileEdit: 'profile',
    Security: 'security',
    Devices: 'devices',
    Notifications: 'notifications',
    Plans: 'membership',
    /* Both billing routes light the Billing row: an invoice is a page inside
       order history, and the website's own invoice view keeps the same tab. */
    Billing: 'billing',
    Invoice: 'billing',
    Wallet: 'wallet',
    Referrals: 'refer',
  };

  return map[routeName] ?? null;
}

/**
 * The row the viewer last opened from the profile menu.
 *
 * Module state rather than component state, and that is the whole reason this
 * exists. Picking a row dismisses the menu — React Navigation pops the modal
 * when it pushes the destination — so anything held in the screen's own
 * `useState` is gone by the time the menu is opened again. Rendered on a real
 * emulator, the active row lit up exactly never; this is what fixed it.
 *
 * **It means "where you last went from here", not "where you are".** Those are
 * the same claim on the website, whose sidebar sits beside the page it is
 * describing, and they are not the same on a phone, where the menu is a screen
 * you leave. The honest version of the mockup's highlight on this shape of
 * navigation is the recent one. If the menu ever becomes reachable from the
 * screens it leads to — an account icon on the Security header, say — then
 * `activeRowFor` answers first and this becomes the fallback it already is.
 *
 * Per session by design. It is a visual aid, not a preference, and restoring
 * it from disk on a cold launch would highlight a row from days ago.
 */
let lastOpenedRow: string | null = null;

export function rememberOpenedRow(key: string | null): void {
  lastOpenedRow = key;
}

export function openedRow(): string | null {
  return lastOpenedRow;
}

/**
 * The three groups the menu's rows are read in.
 *
 * **Nine identical rows is what made the account area feel large**, and it was
 * the cheapest finding in `docs/plans/account-area-audit.md` §6: same height,
 * same weight, same chevron, no hierarchy, so the eye reads all nine every
 * time and the screen behaves like a settings app. **No row moved and no
 * destination changed** — only the reading order and three labels above it.
 *
 * The order is the plan's, which is ordered by how often a viewer of a
 * streaming product opens each one rather than by the website's sidebar. The
 * website has no groups at all: on a desktop the rail sits beside the page it
 * describes and is scanned once, and on a phone it is the whole screen.
 */
export type MenuGroupKey = 'account' | 'viewing' | 'money';

const GROUP_ORDER: readonly { key: MenuGroupKey; label: string }[] = [
  { key: 'account', label: 'Account' },
  { key: 'viewing', label: 'Viewing' },
  { key: 'money', label: 'Money' },
];

/**
 * Which group each row belongs to.
 *
 * `membership` is deliberately absent: it is the promoted card above the
 * groups rather than a row, which is the other half of §6. Anything else
 * missing here is a mistake rather than a decision, and `groupRows` renders it
 * visibly rather than swallowing it — see below.
 */
const GROUP_OF: Readonly<Record<string, MenuGroupKey>> = {
  profile: 'account',
  security: 'account',
  devices: 'account',
  watchlist: 'viewing',
  notifications: 'viewing',
  billing: 'money',
  wallet: 'money',
  refer: 'money',
};

export function groupOf(rowKey: string): MenuGroupKey | null {
  return GROUP_OF[rowKey] ?? null;
}

/** A group as the screen draws it. A null label is a group with no heading. */
export type MenuGroup<T> = { key: string; label: string | null; rows: T[] };

/**
 * The rows, grouped, in the order the screen renders them.
 *
 * Two behaviours here are load-bearing and both are about not lying:
 *
 * **An empty group is not drawn.** Refer & Earn is conditional on the admin's
 * referral switch, so MONEY can legitimately be shorter — but a heading over
 * nothing would read as a feature that failed to load.
 *
 * **A row this file has never heard of is still drawn**, in a trailing group
 * with no heading, rather than dropped. Adding a row to the menu and
 * forgetting to add it here is the obvious mistake, and its only symptom under
 * the other design would be a row that silently does not exist. Loud is
 * cheaper than invisible.
 */
export function groupRows<T extends { key: string }>(rows: readonly T[]): MenuGroup<T>[] {
  const groups: MenuGroup<T>[] = GROUP_ORDER.map((group) => ({
    key: group.key,
    label: group.label,
    rows: rows.filter((row) => groupOf(row.key) === group.key),
  })).filter((group) => group.rows.length > 0);

  const ungrouped = rows.filter((row) => groupOf(row.key) === null);

  return ungrouped.length > 0
    ? [...groups, { key: 'ungrouped', label: null, rows: ungrouped }]
    : groups;
}

/**
 * The title on the promoted Membership card.
 *
 * Three states rather than two, and the third is the one that matters: while
 * the subscription is still loading the card says "Membership", because
 * "No active membership" on a paying viewer's screen — even for one frame — is
 * the app telling them something false about their own money. It resolves to
 * the real plan name the moment either `/me` or `/subscription` answers.
 */
export function membershipTitle(tierName: string | undefined, settled: boolean): string {
  if (tierName !== undefined && tierName.trim() !== '') return tierName;

  return settled ? 'No active membership' : 'Membership';
}
