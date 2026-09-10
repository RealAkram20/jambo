import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { useQuery } from '@tanstack/react-query';
import { useNavigationState } from '@react-navigation/native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  Bell,
  BookmarksSimple,
  CaretRight,
  Crown,
  Devices,
  Gift,
  PencilSimple,
  Receipt,
  ShieldCheck,
  SignOut,
  UserCircle,
  Wallet,
  X,
  type Icon,
} from 'phosphor-react-native';

import { api } from '../api/jambo';
import { useAuth } from '../auth/AuthProvider';
import { logoSource } from '../ui/branding';
import { imageUrl } from '../ui/media';
import { Focusable } from '../ui/rails/Focusable';
import {
  activeRowFor,
  avatarInitial,
  openedRow,
  rememberOpenedRow,
  unreadBadge,
} from '../ui/profileMenu';
import { colors, fonts, profileMenu, radius, spacing, typography } from '../ui/theme';
import type { AppScreenProps } from '../navigation/types';

/**
 * The profile menu.
 *
 * **This is the website's own profile sidebar, not a new navigation.** Every
 * row, its order, its icon and its label come from
 * `resources/views/profile-hub/_sidebar.blade.php` — Watchlist, Profile,
 * Security, Devices, Notifications, Membership, Wallet, Refer & Earn, then
 * "Account" and Sign out. The site draws that rail beside the page on a
 * desktop; a phone has nowhere to put a permanent rail, so the same list
 * becomes a screen. That is the project's ground rule working as intended: the
 * app ports the navigation the site has rather than inventing one.
 *
 * Rio's mockup of 2026-09-09 is that sidebar drawn for a phone, and this is
 * that mockup with one substitution he asked for: **the mockup's crimson is
 * Jambo's blue.** The selected row's gradient is `profileMenu.activeFrom` /
 * `activeTo`, which are the sign-in button's own two values rather than a
 * second accent.
 *
 * **A screen, not a drawer**, by Rio's correction on the same day. It was
 * built first as a panel sliding in over the home screen behind a scrim, and
 * that was wrong: there is nothing behind it worth seeing, and a menu you have
 * to dismiss to read is a menu that behaves like a popup. So it fills the
 * display, arrives as a modal, and the X closes it. What went with that
 * decision is worth knowing, because it is all the code that is now absent —
 * no scrim, no panel width, no translateX, no back-button interception and no
 * `accessibilityViewIsModal`. The navigator does every one of those things for
 * a screen, and correctly.
 *
 * Every row here is a row the website's sidebar has, bar Billing. See the
 * list below for the three that were removed and why.
 */

/**
 * One row.
 *
 * `to` is deliberately optional and its absence is a designed state, not a
 * missing case. Profile, Wallet and Refer & Earn are three tabs the website
 * has and this app is building one at a time, and the two dishonest ways to
 * handle that are both easy to reach for: drop the row, so the menu quietly
 * disagrees with the website, or leave it pressable, so a tap does nothing and
 * the viewer concludes the app is broken. A row with no destination renders
 * dimmed, is not pressable, and says "Soon" out loud. Giving it one is a
 * single line here when the screen lands.
 */
type MenuRow = {
  key: string;
  label: string;
  icon: Icon;
  /** Where it goes. Undefined means the screen does not exist yet. */
  to?:
    | 'Profile'
    | 'ProfileEdit'
    | 'Security'
    | 'Devices'
    | 'Notifications'
    | 'Plans'
    | 'Billing'
    | 'Wallet'
    | 'Referrals';
  /** The tab to select, for rows that live in the bottom bar rather than the stack. */
  tab?: 'Home' | 'Movies' | 'Series' | 'Watchlist';
  /** A count to draw on the right. Undefined draws nothing — never a zero. */
  badge?: number | undefined;
};

/**
 * The unread pill.
 *
 * It inverts on the active row, and that is not decoration. The pill is
 * `activeFrom` on a row whose fill is a gradient from `activeFrom` — blue on
 * blue, which rendered on the emulator as a count you had to look for. On the
 * lit row it becomes white with the row's own blue as its text, which is the
 * same pair of colours the other way round.
 */
function Badge({ text, onActive }: { text: string; onActive: boolean }) {
  return (
    <View style={[styles.badge, onActive && styles.badgeOnActive]}>
      <Text style={[styles.badgeText, onActive && styles.badgeTextOnActive]} numberOfLines={1}>
        {text}
      </Text>
    </View>
  );
}

function Row({
  row,
  active,
  onPress,
}: {
  row: MenuRow;
  active: boolean;
  onPress: (() => void) | undefined;
}) {
  const RowIcon = row.icon;
  const badge = unreadBadge(row.badge);
  const pending = onPress === undefined;

  /*
   * Active is a gradient fill AND a heavier label AND a filled icon. Three
   * signals rather than one, because "the blue row" is not a state a viewer
   * with a colour deficiency can read, and this menu is the only place the app
   * says where you already are.
   */
  const tint = active ? colors.onPrimary : pending ? colors.placeholder : colors.text;

  const body = (
    <View style={styles.rowInner}>
      <RowIcon size={profileMenu.iconSize} color={tint} weight={active ? 'fill' : 'regular'} />

      <Text
        style={[styles.rowLabel, active && styles.rowLabelActive, pending && styles.rowLabelPending]}
        numberOfLines={1}
      >
        {row.label}
      </Text>

      {badge !== null ? <Badge text={badge} onActive={active} /> : null}

      {pending ? (
        <Text style={styles.soon}>Soon</Text>
      ) : (
        <CaretRight
          size={profileMenu.chevronSize}
          color={active ? colors.onPrimary : profileMenu.chevronColor}
          weight="bold"
        />
      )}
    </View>
  );

  if (pending) {
    /*
     * Not a Focusable: a remote should skip past a row that cannot be opened,
     * and a screen reader should be told why rather than handed a button that
     * does nothing. `accessible` groups the row into one announcement instead
     * of three.
     */
    return (
      <View accessible accessibilityLabel={`${row.label}, not available yet`} style={styles.row}>
        {body}
      </View>
    );
  }

  return (
    <Focusable
      accessibilityLabel={badge === null ? row.label : `${row.label}, ${String(row.badge)} unread`}
      accessibilityRole="link"
      ringRadius={profileMenu.rowRadius}
      onPress={onPress}
      style={styles.row}
    >
      {active ? (
        <LinearGradient
          colors={[profileMenu.activeFrom, profileMenu.activeTo]}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={StyleSheet.absoluteFill}
          // The gradient is the fill behind the label, never the thing that
          // catches the press.
          pointerEvents="none"
        />
      ) : null}
      {body}
    </Focusable>
  );
}

export function ProfileMenuScreen({ navigation }: AppScreenProps<'ProfileMenu'>) {
  const { user, branding, signOut } = useAuth();
  const insets = useSafeAreaInsets();

  /*
   * The screen this menu was opened over, so its row can be lit. The website
   * marks its active tab from `$activeTab`; the app reads the route below it.
   * A menu that never shows where you are makes a viewer open a screen to find
   * out they were already on it.
   */
  const beneath = useNavigationState((state) => {
    const index = state.routes.findIndex((r) => r.name === 'ProfileMenu');
    const below = index > 0 ? state.routes[index - 1] : undefined;

    if (below === undefined) return undefined;

    /*
     * Opened from the tabs, the route below is the tab navigator and the
     * screen a viewer can actually see is the selected tab inside it. Reading
     * only the outer name would light nothing up when the menu is opened from
     * Watchlist, which is the one tab it can be opened from today.
     */
    if (below.name === 'Tabs') {
      const nested = below.state;
      if (nested?.routes !== undefined && nested.index !== undefined) {
        return nested.routes[nested.index]?.name;
      }
      return undefined;
    }

    return below.name;
  });

  /*
   * The screen underneath wins when it is one of the menu's own destinations —
   * that is the stronger claim, "you are here". Otherwise the row the viewer
   * last opened from this menu answers, which is the only one of the two that
   * is ever true today: the menu is opened from the home header, so `beneath`
   * is the Home tab and matches nothing. See `openedRow` for why that state
   * lives outside this component.
   */
  const active = activeRowFor(beneath) ?? openedRow();

  /*
   * `me` is usually already cached, so the name and plan paint on the first
   * frame. `profile` is the heavier call and is what carries the avatar;
   * opening this screen is also what warms it for the profile screen behind
   * the row.
   */
  const me = useQuery({ queryKey: ['me'], queryFn: () => api.me() });
  const profile = useQuery({ queryKey: ['profile'], queryFn: () => api.profile() });

  /*
   * Same query key as the Notifications screen, so this is one fetch shared
   * between them rather than two, and opening the menu leaves the screen
   * behind the row already loaded.
   */
  const notifications = useQuery({
    queryKey: ['notifications'],
    queryFn: () => api.notifications(),
  });

  const config = useQuery({ queryKey: ['app-config'], queryFn: () => api.appConfig() });

  const card = profile.data;
  const meUser = me.data?.user;

  const fullName = [card?.first_name ?? meUser?.first_name, card?.last_name ?? meUser?.last_name]
    .filter((part) => part !== undefined && part !== '')
    .join(' ');

  /*
   * The session already carries a username, so the handle is on screen from
   * the first frame whether or not either query has answered. The name falls
   * back to it rather than to a placeholder, exactly as the website's sidebar
   * does (`$user->full_name ?: $user->username`).
   */
  const username = card?.username ?? meUser?.username ?? user?.username;
  const displayName = fullName !== '' ? fullName : (username ?? '');

  /*
   * `imageUrl` returns null for an absent or blank URL, and that null is the
   * decision about which of the two avatars to draw. Resolving it once here
   * keeps "no photo" a rendered state rather than an empty circle.
   */
  const avatar = imageUrl(card?.avatar_url, profileMenu.avatarSize * 2);
  const initial = avatarInitial(card?.first_name ?? meUser?.first_name, username);

  /* The real plan name, or no pill at all. "Premium Member" is not a fact this
     app may assert on its own — the plan a viewer pays for has a name. */
  const tier = me.data?.subscription?.tier?.name;

  /*
   * Refer & Earn is conditional on the website too: `_sidebar.blade.php` adds
   * it only when `ReferralSettings::active()`. `/app/config` now carries that
   * same flag, so the app asks rather than guesses — and a viewer is never
   * offered a page the admin has switched off.
   */
  const referralsOn = config.data?.features?.referrals === true;

  const rows: MenuRow[] = [
    { key: 'watchlist', label: 'Watchlist', icon: BookmarksSimple, tab: 'Watchlist' },
    /*
     * **Neither History nor Continue Watching is a row here, and both were
     * removed by Rio on 2026-09-09 within an hour of each other.**
     *
     * Continue Watching: *"remove it, we have it on the homepage."* The home
     * rail is the app's answer to that question and a menu row was a second
     * one.
     *
     * History: *"history is used in background for training our ai system."*
     * It is collected for the model rather than browsed by the viewer, so it
     * has no door. **Removing the screen changed nothing about what is
     * collected** — `PlaybackBeatRecorder::record` writes `WatchHistoryItem`
     * from the playback heartbeat, and the screen only ever read it back.
     *
     * Streaming preferences was the third, later the same day: *"remove the
     * streaming menu, because all of these settings are applied to the player
     * itself."* Quality was always written from the player's own menu, so the
     * row was a second door onto one account field. Autoplay was not — it
     * moved into `PlayerMenu` in the same change rather than being lost.
     *
     * All three screens went with their rows, because the menu was the only
     * thing that navigated to any of them. Worth knowing before adding a row
     * back on the reasoning that a screen is stranded: it is stranded on
     * purpose.
     */
    { key: 'profile', label: 'Profile', icon: UserCircle, to: 'Profile' },
    { key: 'security', label: 'Security', icon: ShieldCheck, to: 'Security' },
    { key: 'devices', label: 'Devices', icon: Devices, to: 'Devices' },
    {
      key: 'notifications',
      label: 'Notifications',
      icon: Bell,
      to: 'Notifications',
      badge: notifications.data?.unread,
    },
    { key: 'membership', label: 'Membership', icon: Crown, to: 'Plans' },
    /*
     * The second row the website's sidebar does not have, and this one is a
     * gap on the website rather than a difference in kind: `profile.billing`
     * exists, renders and is linked from nowhere but the payment-complete
     * page. The app has no payment-complete page, so without this the screen
     * would be unreachable. Rio's call, 2026-09-09: a row here and a row on
     * Membership.
     */
    { key: 'billing', label: 'Billing', icon: Receipt, to: 'Billing' },
    { key: 'wallet', label: 'Wallet', icon: Wallet, to: 'Wallet' },
  ];

  if (referralsOn) {
    rows.push({ key: 'refer', label: 'Refer & Earn', icon: Gift, to: 'Referrals' });
  }

  const go = (row: MenuRow) => {
    /*
     * The destination opens on top of this screen rather than replacing it, so
     * going back returns to the menu. That is what the website's sidebar does
     * — it stays beside the page — and it is what makes Security, then back,
     * then Devices two taps instead of four.
     */
    if (row.tab !== undefined) {
      /*
       * A tab is the exception: it is not pushed on top, it is the navigator
       * underneath. The menu is dismissed rather than left hanging over a tab
       * the viewer asked to see, and nothing is remembered — they are no
       * longer in the menu's stack at all.
       */
      navigation.navigate('Tabs', { screen: row.tab });
      return;
    }

    if (row.to !== undefined) {
      rememberOpenedRow(row.key);
      navigation.navigate(row.to);
    }
  };

  return (
    <View style={[styles.screen, { paddingTop: insets.top + spacing.md }]}>
      <View style={styles.column}>
        <View style={styles.top}>
          {/*
            `logoSource` resolves to the downloaded file first and the bundled
            copy second, and never touches the network — so this screen is
            branded on an offline launch too.
          */}
          <ExpoImage
            source={logoSource(branding)}
            style={styles.logo}
            contentFit="contain"
            contentPosition="left center"
            accessibilityLabel="Jambo Films"
          />

          <Focusable
            accessibilityLabel="Close"
            ringRadius={20}
            onPress={() => navigation.goBack()}
            style={styles.close}
          >
            <X size={22} color={colors.text} weight="bold" />
          </Focusable>
        </View>

        <ScrollView
          contentContainerStyle={[styles.scroll, { paddingBottom: insets.bottom + spacing.xl }]}
          showsVerticalScrollIndicator={false}
        >
          {/*
            The identity block opens Profile.

            It used to open an Account hub — a plain list of buttons that
            predated the Profile screen and duplicated most of this menu.
            Rio, 2026-09-09: *"we should remove this, and link this to our
            account page we have designed, because it is linking to the older
            account page."* The hub is gone.

            Removing it stranded two screens, which is why Continue Watching
            and History are rows above rather than a loss: they were reachable
            only from that hub, and nothing else in the app navigates to
            either. Deleting a screen is only finished when the things behind
            it still have a door.
          */}
          <Focusable
            accessibilityLabel={displayName === '' ? 'Your profile' : `Your profile, ${displayName}`}
            accessibilityRole="link"
            ringRadius={profileMenu.rowRadius}
            onPress={() => navigation.navigate('Profile')}
            style={styles.identity}
          >
            <View style={styles.avatarWrap}>
              {avatar !== null ? (
                <ExpoImage
                  source={{ uri: avatar }}
                  style={styles.avatar}
                  contentFit="cover"
                  // Decorative: the name sits beside it, so announcing the
                  // photo as well would read the viewer their own name twice.
                  accessibilityLabel=""
                />
              ) : (
                /*
                 * The website's own fallback, not a compromise invented here:
                 * `_sidebar.blade.php` draws a single letter in its avatar
                 * circle. Most accounts have never uploaded a photo, and a
                 * stock face for a real person is the one thing this circle
                 * must never show.
                 */
                <View style={[styles.avatar, styles.avatarLetter]}>
                  <Text style={styles.avatarLetterText}>{initial}</Text>
                </View>
              )}

              {/*
                The pencil. Decoration for now, and marked as such rather than
                drawn as a button that does nothing: the whole block is the
                press target and it goes where the pencil would.
              */}
              <View style={styles.editBadge} pointerEvents="none">
                <PencilSimple size={12} color={colors.onPrimary} weight="bold" />
              </View>
            </View>

            <View style={styles.identityText}>
              {displayName !== '' ? (
                <Text style={styles.name} numberOfLines={1}>
                  {displayName}
                </Text>
              ) : null}

              {username !== undefined && username !== '' ? (
                <Text style={styles.handle} numberOfLines={1}>
                  @{username}
                </Text>
              ) : null}

              {/* Only with a real plan name. No plan means no pill, not "Free". */}
              {tier !== undefined && tier !== '' ? (
                <View style={styles.tier}>
                  <Crown size={13} color={colors.warning} weight="fill" />
                  <Text style={styles.tierText} numberOfLines={1}>
                    {tier}
                  </Text>
                </View>
              ) : null}
            </View>

            <CaretRight size={profileMenu.chevronSize} color={profileMenu.chevronColor} weight="bold" />
          </Focusable>

          <View style={styles.rule} />

          <View style={styles.rows}>
            {rows.map((row) => (
              <Row
                key={row.key}
                row={row}
                active={active === row.key}
                onPress={row.to === undefined && row.tab === undefined ? undefined : () => go(row)}
              />
            ))}
          </View>

          <View style={styles.rule} />

          <Text style={styles.section}>Account</Text>

          <Focusable
            accessibilityLabel="Sign out"
            ringRadius={profileMenu.rowRadius}
            onPress={() => void signOut()}
            style={styles.row}
          >
            <View style={styles.rowInner}>
              <SignOut size={profileMenu.iconSize} color={colors.errorText} weight="regular" />
              <Text style={[styles.rowLabel, styles.signOut]} numberOfLines={1}>
                Sign out
              </Text>
              <CaretRight
                size={profileMenu.chevronSize}
                color={profileMenu.chevronColor}
                weight="bold"
              />
            </View>
          </Focusable>
        </ScrollView>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: profileMenu.background },
  /* The reading column, centred once the display is wider than it. */
  column: { flex: 1, width: '100%', maxWidth: profileMenu.maxWidth, alignSelf: 'center' },

  top: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.lg,
  },
  logo: { width: 108, height: 32 },
  close: { padding: spacing.sm },

  scroll: { paddingHorizontal: spacing.md },

  identity: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.sm,
    borderRadius: profileMenu.rowRadius,
  },
  avatarWrap: { width: profileMenu.avatarSize, height: profileMenu.avatarSize },
  avatar: {
    width: profileMenu.avatarSize,
    height: profileMenu.avatarSize,
    borderRadius: profileMenu.avatarSize / 2,
    borderWidth: 2,
    borderColor: profileMenu.avatarRing,
    backgroundColor: colors.inputBg,
  },
  avatarLetter: { alignItems: 'center', justifyContent: 'center' },
  avatarLetterText: { fontFamily: fonts.bold, fontSize: 32, color: colors.fieldText },
  editBadge: {
    position: 'absolute',
    right: -2,
    bottom: -2,
    width: profileMenu.editBadgeSize,
    height: profileMenu.editBadgeSize,
    borderRadius: profileMenu.editBadgeSize / 2,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: profileMenu.background,
  },

  identityText: { flex: 1, gap: 2 },
  name: { fontFamily: fonts.bold, fontSize: 19, lineHeight: 24, color: colors.fieldText },
  handle: { ...typography.caption, fontSize: 14, color: colors.textMuted },

  tier: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 5,
    marginTop: 5,
    paddingHorizontal: 9,
    paddingVertical: 3,
    borderRadius: radius.pill,
    backgroundColor: profileMenu.tierBg,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: profileMenu.tierBorder,
  },
  tierText: { fontFamily: fonts.medium, fontSize: 11, lineHeight: 15, color: profileMenu.tierText },

  rule: {
    height: StyleSheet.hairlineWidth,
    backgroundColor: colors.divider,
    marginVertical: spacing.md,
    marginHorizontal: spacing.sm,
  },

  rows: { gap: profileMenu.rowGap },
  row: {
    height: profileMenu.rowHeight,
    borderRadius: profileMenu.rowRadius,
    justifyContent: 'center',
    // Clips the gradient to the pill. Without it the fill is a rectangle.
    overflow: 'hidden',
  },
  rowInner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.md,
  },
  rowLabel: {
    ...typography.body,
    fontFamily: fonts.regular,
    fontSize: 15,
    flex: 1,
    color: colors.text,
  },
  rowLabelActive: { fontFamily: fonts.medium, color: colors.onPrimary },
  rowLabelPending: { color: colors.placeholder },

  signOut: { color: colors.errorText },

  soon: {
    fontFamily: fonts.medium,
    fontSize: 10,
    letterSpacing: 0.6,
    textTransform: 'uppercase',
    color: colors.placeholder,
  },

  badge: {
    minWidth: profileMenu.badgeSize + 8,
    height: profileMenu.badgeSize,
    borderRadius: profileMenu.badgeSize / 2,
    paddingHorizontal: 6,
    backgroundColor: profileMenu.badgeBg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeOnActive: { backgroundColor: colors.onPrimary },
  badgeText: { fontFamily: fonts.bold, fontSize: 11, lineHeight: 13, color: profileMenu.badgeText },
  badgeTextOnActive: { color: profileMenu.activeTo },

  section: {
    fontFamily: fonts.medium,
    fontSize: profileMenu.sectionSize,
    letterSpacing: profileMenu.sectionTracking,
    textTransform: 'uppercase',
    color: colors.textMuted,
    paddingHorizontal: spacing.md,
    paddingBottom: spacing.sm,
  },
});
