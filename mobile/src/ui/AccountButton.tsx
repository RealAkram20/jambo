import { Image as ExpoImage } from 'expo-image';
import { useQuery } from '@tanstack/react-query';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { StyleSheet, Text, View } from 'react-native';

import { api } from '../api/jambo';
import { useAuth } from '../auth/AuthProvider';
import { imageUrl } from './media';
import { avatarInitial } from './profileMenu';
import { Focusable } from './rails/Focusable';
import { colors, fonts, header as t } from './theme';
import type { AppStackParams } from '../navigation/types';

/**
 * The viewer's own avatar, and the one control that opens the profile menu.
 *
 * **It is a component rather than a block inside `AppHeader` because it is now
 * drawn in two places.** `docs/plans/account-area-audit.md` §2.2: every account
 * screen was a dead end but for the back arrow, so once inside Wallet the only
 * ways out were back and the Android gesture — while the website keeps its
 * sidebar visible on every hub page and makes Wallet to Security one click.
 * Rio's call, 2026-09-10: build it.
 *
 * So this rides in the stack's `headerRight` on the account screens as well as
 * in the tab header, and there is **one implementation of the avatar** rather
 * than a second one that happens to match today. That matters more here than
 * usual: this circle shows a real person's face, its fallback rule is the
 * website's own, and two copies would eventually disagree about whose account
 * this is.
 *
 * **It navigates itself**, for the reason `AppHeader` already records: a
 * control that took an `onPress` from every call site would be one decision
 * copied into every screen that draws it.
 */
export function AccountButton() {
  const navigation = useNavigation<NativeStackNavigationProp<AppStackParams>>();
  const { user } = useAuth();

  /*
   * The same query key the menu and the header already use, so this is one
   * fetch shared with both rather than a third — and opening any account
   * screen leaves the menu behind this button already warm.
   */
  const profile = useQuery({ queryKey: ['profile'], queryFn: () => api.profile() });

  /*
   * The picture and the letter both come from `/profile`. The session `user`
   * carries an id, a username and an email and no name or picture at all —
   * reaching for `user.avatar_url` typechecks as `never` and renders the
   * letter for everybody, which is the trap `AppHeader` already recorded.
   */
  const avatar = imageUrl(profile.data?.avatar_url, t.avatarSize * 2);
  const initial = avatarInitial(profile.data?.first_name, user?.username);

  return (
    <Focusable
      accessibilityRole="button"
      /* The account's own name when there is one. "Account" alone makes a
         screen-reader user open a screen to find out whose it is. */
      accessibilityLabel={user?.username === undefined ? 'Account' : `Account, ${user.username}`}
      ringRadius={t.avatarSize / 2}
      onPress={() => navigation.navigate('ProfileMenu')}
      style={styles.action}
    >
      {avatar === null ? (
        /*
         * The website's own fallback — `_sidebar.blade.php` draws a single
         * letter. Most accounts have never uploaded a photo, and a stock face
         * for a real person is the one thing this circle must never show.
         */
        <View style={[styles.avatar, styles.avatarLetter]}>
          <Text style={styles.avatarLetterText}>{initial}</Text>
        </View>
      ) : (
        <ExpoImage source={{ uri: avatar }} style={styles.avatar} contentFit="cover" />
      )}
    </Focusable>
  );
}

const styles = StyleSheet.create({
  action: {
    width: t.iconBox,
    height: t.iconBox,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatar: { width: t.avatarSize, height: t.avatarSize, borderRadius: t.avatarSize / 2 },
  avatarLetter: { backgroundColor: colors.surface, alignItems: 'center', justifyContent: 'center' },
  avatarLetterText: { fontFamily: fonts.bold, fontSize: 13, color: colors.text },
});
