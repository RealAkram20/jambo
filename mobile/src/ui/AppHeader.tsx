import { useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { useQuery } from '@tanstack/react-query';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Bell, MagnifyingGlass } from 'phosphor-react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { api } from '../api/jambo';
import { useAuth } from '../auth/AuthProvider';
import { AccountButton } from './AccountButton';
import { logoSource } from './branding';
import { unreadBadge } from './profileMenu';
import { colors, fonts, header as t, spacing } from './theme';
import { Focusable } from './rails/Focusable';
import type { AppStackParams } from '../navigation/types';

/**
 * The website's header, both rows, as it actually is.
 *
 * Rio, 2026-09-10: *"let us use the exact header as it is on our web app."*
 * `partials/header-default.blade.php` renders, below 992px: the brand, a
 * search toggle, **a notification bell carrying an unread badge**, **the
 * viewer's own avatar**, and **a second row of genre chips**. The app had the
 * first two and a generic account circle.
 *
 * **The bell was deliberately absent and its reason has expired.** The note
 * that used to be here said a bell would open a list that is always empty for
 * reasons a viewer cannot see, because the notifications screen did not exist.
 * It exists now, with real rows, so the bell arrives with it — which is what
 * that note said would happen.
 *
 * **The avatar is the viewer's, with the website's own fallback.** The site
 * draws a 28px round image; when an account has never uploaded one it draws a
 * letter. `avatarInitial` is that rule, already shared with the profile menu,
 * so the header and the menu cannot disagree about whose account this is. A
 * stock face for a real person is the one thing this circle must never show.
 *
 * **It navigates itself.** Every one of the four tabs used to pass the same
 * two callbacks — `onSearch` and `onAccount` — which was four copies of one
 * decision, and adding the bell would have made it eight. The header owns
 * where its own controls go, so a fifth control never touches a call site.
 */
export function AppHeader() {
  const insets = useSafeAreaInsets();

  /*
   * The logo's width-to-height ratio, learned from the image.
   *
   * Seeded with the ratio captured off the site so the first frame is close
   * rather than empty, then corrected the moment the real file reports its
   * own size. See the note beside the image.
   */
  const [aspect, setAspect] = useState(t.logoWidth / t.logoHeight);
  const navigation = useNavigation<NativeStackNavigationProp<AppStackParams>>();
  const { branding } = useAuth();

  /*
   * The unread count, on a key of its own.
   *
   * 🔴 **It shared `['notifications', null]` with the inbox and that crashed
   * the app.** The inbox reads that key with `useInfiniteQuery`, which stores
   * `{ pages, pageParams }`; this read it with `useQuery`, which stores the
   * response object. Whichever mounted first won the cache entry, and when the
   * header won, the inbox's `getNextPageParam` found no `pages` and threw
   * "Cannot read property 'length' of undefined" — a crash **on the
   * Notifications screen, caused by a change to the header**, which is not
   * where anybody would look for it.
   *
   * **Two queries may share a key only if they share a shape.**
   *
   * `'unread'` rather than a key outside the prefix, and that is deliberate
   * too: marking something read invalidates `['notifications']`, which matches
   * by prefix, so this badge refreshes with the list. A key like
   * `['notifications-unread']` would be safe from the collision and would also
   * stop updating — the badge would keep a stale count after every read.
   *
   * It cannot collide, because the second element here is a *category* and the
   * server's categories are a closed set — movies, series, account, offers —
   * enumerated in `NotificationCategories` with a test that fails when one is
   * added without a decision. `'unread'` is not among them and cannot become
   * one without that test being seen.
   */
  const notifications = useQuery({
    queryKey: ['notifications', 'unread'],
    queryFn: () => api.notifications(),
  });

  /*
   * The genre chips. `staleTime` is long because a genre list changes when an
   * admin adds one, not while somebody is browsing, and this renders on all
   * four tabs — refetching it per tab switch would be four requests for an
   * answer that has not moved.
   */
  const genres = useQuery({
    queryKey: ['genres'],
    queryFn: () => api.genres(),
    staleTime: 30 * 60 * 1000,
  });

  /*
   * The `/profile` query moved into `AccountButton` with the avatar it fed.
   * It is the same query key, so the header, the menu and the account
   * screens' button are still one fetch between them rather than three.
   */
  const badge = unreadBadge(notifications.data?.unread);

  return (
    <View style={[styles.wrap, { paddingTop: insets.top + spacing.sm }]}>
      <View style={styles.row}>
        {/*
          **Height drives the size; width follows the logo's own shape.**

          The site is `height: Npx; width: auto`, and the app had a hardcoded
          108x32 box with `contain`. Rio spotted the result: the brand is a
          wide wordmark, so it fitted the box by WIDTH and landed about 16dp
          tall — noticeably shorter than the website's. A fixed box can only
          be right for one logo's aspect, and the logo is admin-uploadable.

          The aspect is learned from the image itself on load, so any logo an
          admin uploads fills the same height. Until it loads, the captured
          site aspect is the placeholder — so the header does not reflow from
          nothing to something on every launch.
        */}
        <ExpoImage
          source={logoSource(branding)}
          style={[styles.logo, { width: t.logoHeight * aspect }]}
          contentFit="contain"
          contentPosition="left center"
          onLoad={(event) => {
            const { width, height } = event.source;
            if (width > 0 && height > 0) setAspect(width / height);
          }}
          accessibilityLabel="Jambo Films"
        />

        <View style={styles.actions}>
          <Focusable
            accessibilityRole="button"
            accessibilityLabel="Search"
            ringRadius={t.iconBox / 2}
            onPress={() => navigation.navigate('Search')}
            style={styles.action}
          >
            <MagnifyingGlass size={t.iconSize} color={t.iconColor} weight="regular" />
          </Focusable>

          <Focusable
            accessibilityRole="button"
            // The count is in the label, not only in the pill: a badge nobody's
            // screen reader mentions is a notification nobody hears about.
            accessibilityLabel={
              badge === null ? 'Notifications' : `Notifications, ${badge} unread`
            }
            ringRadius={t.iconBox / 2}
            onPress={() => navigation.navigate('Notifications')}
            style={styles.action}
          >
            {/*
              Filled when there is something to read, outlined when there is
              not — which is exactly what the site does with
              `ph-fill ph-bell` against `ph-bell`. Shape as well as a pill, so
              the state does not rest on one small dot of colour.
            */}
            <Bell size={t.iconSize} color={t.iconColor} weight={badge === null ? 'regular' : 'fill'} />

            {badge === null ? null : (
              <View style={styles.badge}>
                <Text style={styles.badgeText} numberOfLines={1}>
                  {badge}
                </Text>
              </View>
            )}
          </Focusable>

          {/*
            The avatar moved into `AccountButton` on 2026-09-10, unchanged, so
            that the account screens could draw the same one — §2.2 of the
            audit. Two copies of a circle showing a real person's face would
            eventually disagree about whose account it is.
          */}
          <AccountButton />
        </View>
      </View>

      <GenreBar genres={genres.data ?? []} />
    </View>
  );
}

/**
 * Row two: All, then every genre by name.
 *
 * The same list the site's `HeaderComposer` builds — `Genre::orderBy('name')`,
 * all of them — so the two headers offer the same chips in the same order.
 *
 * **Renders nothing until the list arrives.** A bar holding only "All" would
 * appear, then jump taller and reflow the screen under a viewer's thumb a
 * moment later. Nothing, then the whole bar, moves the layout once.
 */
function GenreBar({ genres }: { genres: readonly { slug?: string; name?: string }[] }) {
  const navigation = useNavigation<NativeStackNavigationProp<AppStackParams>>();

  if (genres.length === 0) return null;

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={styles.bar}
      // The bar is wider than the screen by design — the site scrolls it too —
      // so the row must not fight the vertical list beneath it.
      keyboardShouldPersistTaps="handled"
    >
      {/*
        "All" is the home screen, and it is the active chip there. Active is
        drawn as the site draws it: a white pill with black text, 21:1.
      */}
      <Chip label="All" active onPress={() => navigation.navigate('Tabs', { screen: 'Home' })} />

      {genres.map((genre) =>
        typeof genre.slug !== 'string' || typeof genre.name !== 'string' ? null : (
          <Chip
            key={genre.slug}
            label={genre.name}
            active={false}
            onPress={() =>
              navigation.navigate('Taxonomy', {
                kind: 'genre',
                slug: genre.slug as string,
                name: genre.name as string,
              })
            }
          />
        ),
      )}
    </ScrollView>
  );
}

function Chip({
  label,
  active,
  onPress,
}: {
  label: string;
  active: boolean;
  onPress: () => void;
}) {
  return (
    <Focusable
      accessibilityRole="tab"
      accessibilityLabel={label}
      accessibilityState={{ selected: active }}
      ringRadius={t.chipRadius}
      onPress={onPress}
      style={[styles.chip, active ? styles.chipActive : null]}
    >
      <Text style={[styles.chipText, active ? styles.chipTextActive : null]} numberOfLines={1}>
        {label}
      </Text>
    </Focusable>
  );
}

const styles = StyleSheet.create({
  wrap: { backgroundColor: colors.header },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.sm,
  },
  logo: { height: t.logoHeight },
  actions: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  action: {
    width: t.iconBox,
    height: t.iconBox,
    alignItems: 'center',
    justifyContent: 'center',
  },

  badge: {
    position: 'absolute',
    top: 0,
    right: 0,
    minWidth: t.badgeHeight,
    height: t.badgeHeight,
    paddingHorizontal: 4,
    borderRadius: t.badgeRadius,
    backgroundColor: t.badgeBg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: { fontFamily: fonts.medium, fontSize: t.badgeSize, color: t.badgeFg },

  avatar: { width: t.avatarSize, height: t.avatarSize, borderRadius: t.avatarSize / 2 },
  avatarLetter: { backgroundColor: colors.surface, alignItems: 'center', justifyContent: 'center' },
  avatarLetterText: { fontFamily: fonts.bold, fontSize: 13, color: colors.text },

  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: t.chipGap,
    paddingHorizontal: spacing.lg,
    paddingVertical: t.barPadY,
  },
  chip: {
    paddingVertical: t.chipPadY,
    paddingHorizontal: t.chipPadX,
    borderRadius: t.chipRadius,
    backgroundColor: t.chipBg,
  },
  chipActive: { backgroundColor: t.chipActiveBg },
  chipText: { fontFamily: fonts.medium, fontSize: t.chipTextSize, color: t.chipFg },
  chipTextActive: { color: t.chipActiveFg },
});
