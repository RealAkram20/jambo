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
    /* The hub the identity block opens. It is the profile area, so the
       Profile row is the honest thing to light. */
    Account: 'profile',
    ProfileEdit: 'profile',
    Security: 'security',
    Devices: 'devices',
    Notifications: 'notifications',
    Plans: 'membership',
    StreamingPreferences: 'streaming',
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
