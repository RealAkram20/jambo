import { StyleSheet, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { MagnifyingGlass, UserCircle } from 'phosphor-react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useAuth } from '../auth/AuthProvider';
import { logoSource } from './branding';
import { colors, spacing, tabBar } from './theme';
import { Focusable } from './rails/Focusable';

/**
 * The header the website puts on a phone: the brand on the left, search and
 * the account on the right.
 *
 * `partials/header-default.blade.php` renders exactly this below 992px — a
 * search toggle, the notification bell and the avatar — while Home, Movies,
 * Series and Watchlist live in the bottom bar. The app follows that split
 * rather than promoting search to a fifth tab, which is a navigation the
 * website does not have.
 *
 * **The bell is not here yet, and is not drawn as a dead icon.** The
 * notification endpoints exist, but `docs/api/coverage.md` records that push
 * *delivery* has no sender and nothing has been tested on a handset, so the
 * screen behind a bell would be a list that is always empty for reasons a
 * viewer cannot see. It arrives with the notifications screen.
 *
 * The logo comes from `branding`, which is downloaded and owned on disk — so
 * the header is branded on an offline launch too, which is the whole point of
 * how slice 2a built it.
 */
export function AppHeader({
  onSearch,
  onAccount,
}: {
  onSearch: () => void;
  onAccount: () => void;
}) {
  const insets = useSafeAreaInsets();
  const { branding, user } = useAuth();

  return (
    <View style={[styles.header, { paddingTop: insets.top + spacing.sm }]}>
      {/*
        `logoSource` resolves to the downloaded file first and the bundled copy
        second, and never touches the network — so the header is branded on an
        offline launch, which is the case this whole branding path exists for.
      */}
      <ExpoImage
        source={logoSource(branding)}
        style={styles.logo}
        contentFit="contain"
        contentPosition="left center"
        accessibilityLabel="Jambo Films"
      />

      <View style={styles.actions}>
        <Focusable accessibilityLabel="Search" ringRadius={22} onPress={onSearch} style={styles.action}>
          <MagnifyingGlass size={tabBar.iconSize} color={colors.text} weight="regular" />
        </Focusable>

        <Focusable
          // The account's own name, when there is one. "Account" alone makes a
          // screen reader user check whose account they are about to open.
          // The session carries a username, not a first name — the account
          // resource does, but the app should not fetch a whole profile to
          // label one icon. "Account" alone would make a screen-reader user
          // check whose account they are about to open.
          accessibilityLabel={user?.username === undefined ? 'Account' : `Account, ${user.username}`}
          ringRadius={22}
          onPress={onAccount}
          style={styles.action}
        >
          <UserCircle size={tabBar.iconSize} color={colors.text} weight="regular" />
        </Focusable>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.sm,
    backgroundColor: colors.header,
  },
  logo: { width: 108, height: 32 },
  actions: { flexDirection: 'row', alignItems: 'center', gap: spacing.lg },
  action: { padding: spacing.xs },
});
