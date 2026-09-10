import { useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { useQuery } from '@tanstack/react-query';
import {
  CalendarBlank,
  Crown,
  Envelope,
  MapPin,
  PencilSimple,
  Phone,
} from 'phosphor-react-native';

import { api } from '../api/jambo';
import { NetworkError } from '../api/errors';
import { imageUrl } from '../ui/media';
import { useAvatarUpload } from '../ui/useAvatarUpload';
import { Focusable } from '../ui/rails/Focusable';
import { avatarInitial } from '../ui/profileMenu';
import {
  detail,
  displayName,
  memberSince,
  NOT_SET,
} from '../ui/profileFields';
import { Alert, ErrorState, Loading } from '../ui/components';
import { ListCard, ListRow } from '../ui/list';
import { colors, fonts, profileBanner, profileMenu, radius, spacing, typography } from '../ui/theme';
import type { AppScreenProps } from '../navigation/types';

/**
 * The viewer's own profile, as Rio designed it on 2026-09-09.
 *
 * A banner, the avatar overlapping it, the name, the handle and the plan, then
 * a card of details and a way to edit them. It reads rather than edits: the
 * form is `ProfileEditScreen`, one row down.
 *
 * **The banner is a gradient of the brand blue, not a cover photo, and that
 * was Rio's own instruction.** It is also the only honest option: there is no
 * cover image anywhere in this system — no column, no upload endpoint, no
 * field in the API — so a photographic banner would have had to be a stock
 * image pretending to be the viewer's. That is why the mockup's "Change Cover"
 * button is absent rather than drawn and dead: with a brand gradient there is
 * nothing to change.
 *
 * **The Country row is real.** The mockup showed "Uganda" against a field that
 * existed nowhere, and rather than fake it Rio chose to add it — a `country`
 * column, an ISO code, a name derived server-side, and a picker on the edit
 * form. An account that has not set one shows an em dash. The rejected
 * alternative is worth remembering: deriving the country from the phone's
 * dialling code, which is wrong for anybody on a SIM from a country they do
 * not live in and gives them no way to correct it.
 */

/**
 * A detail row, with the one thing the shared row cannot know: that this
 * screen's em dash means "not set" rather than a value that happens to be a
 * dash. It is stated for a screen reader and dimmed for everyone else.
 */
function Row(props: {
  label: string;
  icon: typeof Envelope;
  value: string;
  last?: boolean;
}) {
  const missing = props.value === NOT_SET;

  return (
    <ListRow
      icon={props.icon}
      label={props.label}
      value={props.value}
      muted={missing}
      last={props.last === true}
      accessibilityLabel={missing ? `${props.label}, not set` : `${props.label}, ${props.value}`}
    />
  );
}

export function ProfileScreen({ navigation }: AppScreenProps<'Profile'>) {

  /*
   * Whether the stored photo actually loaded.
   *
   * `avatar_url` being present does not mean the image is reachable — a media
   * URL is built from the server's own `APP_URL`, which on a dev box is not
   * the host the handset talks to, and in the field a CDN object can simply be
   * gone. Without this the circle renders empty: no photo, no initial, just a
   * hole where a face should be, which reads as the app failing rather than as
   * an account with no picture. Keyed by URL so a newly uploaded photo gets a
   * fresh attempt rather than inheriting the last one's failure.
   */
  const [brokenAvatar, setBrokenAvatar] = useState<string | null>(null);

  const profile = useQuery({ queryKey: ['profile'], queryFn: () => api.profile() });
  const me = useQuery({ queryKey: ['me'], queryFn: () => api.me() });

  /*
   * Replacing the photo, from `useAvatarUpload`.
   *
   * 🔴 **This used to be sixty lines here, and the editor one tap away said
   * pictures were "changed on the Jambo website".** Rio called that out as
   * fake information on 2026-09-10 and he was right — the capability existed,
   * on the other screen, and the copy denied it. Both screens now call one
   * hook rather than the editor growing a second copy of a permission prompt,
   * a MIME sniff and four failure sentences.
   *
   * The behaviour is unchanged, including the deliberate absence of an
   * optimistic update: an upload takes seconds on a Ugandan uplink and showing
   * the new face before the server has it means silently swapping it back.
   */
  const { pick: pickPhoto, busy: uploading, error: photoError } = useAvatarUpload();

  if (profile.isPending) return <Loading label="Loading your profile" />;

  if (profile.isError) {
    return (
      <View style={styles.state}>
        <ErrorState
          message={
            profile.error instanceof NetworkError
              ? 'Could not reach Jambo. Check your connection.'
              : 'We could not load your profile.'
          }
          onRetry={() => void profile.refetch()}
        />
      </View>
    );
  }

  const card = profile.data;
  const name = displayName(card.first_name, card.last_name, card.username);
  const resolved = imageUrl(card.avatar_url, profileBanner.avatarSize * 2);
  const avatar = resolved !== null && resolved === brokenAvatar ? null : resolved;
  const initial = avatarInitial(card.first_name, card.username);

  /* The real plan name, or no pill. "Premium Member" is not a claim this
     screen may make on its own — the plan a viewer pays for has a name. */
  const tier = me.data?.subscription?.tier?.name;

  return (
    <ScrollView contentContainerStyle={styles.screen} showsVerticalScrollIndicator={false}>
      <View style={styles.banner}>
        <LinearGradient
          colors={[profileBanner.from, profileBanner.via, profileBanner.to]}
          start={profileBanner.gradientStart}
          end={profileBanner.gradientEnd}
          style={StyleSheet.absoluteFill}
        />
        {/* Darkens the bottom third so the avatar's ring reads against the
            banner above it and the page below it. */}
        <LinearGradient colors={profileBanner.scrim} style={StyleSheet.absoluteFill} />

        <Text style={styles.tagline}>GOOD STORIES{'\n'}LIVE FOREVER</Text>
      </View>

      <View style={styles.identity}>
        <View style={styles.avatarWrap}>
          {avatar !== null ? (
            <ExpoImage
              source={{ uri: avatar }}
              style={styles.avatar}
              contentFit="cover"
              // Falls back to the initial rather than leaving the circle empty.
              onError={() => setBrokenAvatar(avatar)}
              // Decorative: the name is right below it, so announcing the photo
              // would read the viewer their own name twice.
              accessibilityLabel=""
            />
          ) : (
            /* The website's own fallback — `_sidebar.blade.php` draws a single
               letter. Never a stock face for a real person. */
            <View style={[styles.avatar, styles.avatarLetter]}>
              <Text style={styles.avatarLetterText}>{initial}</Text>
            </View>
          )}

          <Focusable
            accessibilityLabel="Change your profile picture"
            ringRadius={profileBanner.editSize / 2}
            onPress={() => void pickPhoto()}
            disabled={uploading}
            style={styles.editBadge}
          >
            {uploading ? (
              <ActivityIndicator size="small" color={colors.onPrimary} />
            ) : (
              <PencilSimple size={16} color={colors.onPrimary} weight="bold" />
            )}
          </Focusable>
        </View>

        {name !== '' ? (
          <Text style={styles.name} numberOfLines={1}>
            {name}
          </Text>
        ) : null}

        {card.username !== undefined && card.username !== '' ? (
          <Text style={styles.handle} numberOfLines={1}>
            @{card.username}
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

      {photoError !== null ? (
        <View style={styles.alert}>
          <Alert tone="error">{photoError}</Alert>
        </View>
      ) : null}

      <View style={styles.cardWrap}>
        <ListCard>
          <Row label="Email" icon={Envelope} value={detail(card.email)} />
          <Row label="Phone" icon={Phone} value={detail(card.phone)} />
          {/* `country_name` is derived server-side and is null exactly when no
              country is set, so this row shows a dash rather than a guess. */}
          <Row label="Country" icon={MapPin} value={detail(card.country_name)} />
          <Row label="Member Since" icon={CalendarBlank} value={memberSince(card.joined_at)} last />
        </ListCard>
      </View>

      <View style={styles.cardWrap}>
        <ListCard>
          <ListRow
            icon={PencilSimple}
            label="Edit Profile"
            onPress={() => navigation.navigate('ProfileEdit')}
            last
          />
        </ListCard>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { paddingBottom: spacing.xxl },
  state: { flex: 1, padding: spacing.lg },

  banner: {
    height: profileBanner.height,
    justifyContent: 'flex-start',
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.lg,
    overflow: 'hidden',
  },
  tagline: {
    fontFamily: fonts.medium,
    fontSize: profileBanner.taglineSize,
    lineHeight: profileBanner.taglineLineHeight,
    letterSpacing: profileBanner.taglineTracking,
    color: profileBanner.taglineColor,
  },

  identity: {
    alignItems: 'center',
    // Pulls the avatar up so it sits half on the banner, as the mockup draws
    // it. Negative margin rather than absolute positioning, so everything
    // below still flows and the card cannot end up underneath it.
    marginTop: -(profileBanner.avatarSize / 2),
    paddingHorizontal: spacing.lg,
  },
  avatarWrap: { width: profileBanner.avatarSize, height: profileBanner.avatarSize },
  avatar: {
    width: profileBanner.avatarSize,
    height: profileBanner.avatarSize,
    borderRadius: profileBanner.avatarSize / 2,
    borderWidth: profileBanner.avatarRing,
    borderColor: colors.background,
    backgroundColor: profileBanner.avatarFallback,
  },
  avatarLetter: { alignItems: 'center', justifyContent: 'center' },
  avatarLetterText: { fontFamily: fonts.bold, fontSize: 38, color: colors.fieldText },
  editBadge: {
    position: 'absolute',
    right: -2,
    bottom: 2,
    width: profileBanner.editSize,
    height: profileBanner.editSize,
    borderRadius: profileBanner.editSize / 2,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 3,
    borderColor: colors.background,
  },

  name: {
    fontFamily: fonts.bold,
    fontSize: 22,
    lineHeight: 28,
    color: colors.fieldText,
    marginTop: spacing.md,
    textAlign: 'center',
  },
  handle: { ...typography.caption, fontSize: 14, color: colors.textMuted, marginTop: 2 },

  tier: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginTop: spacing.sm,
    paddingHorizontal: 11,
    paddingVertical: 4,
    borderRadius: radius.pill,
    backgroundColor: profileMenu.tierBg,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: profileMenu.tierBorder,
  },
  tierText: { fontFamily: fonts.medium, fontSize: 12, lineHeight: 16, color: profileMenu.tierText },

  alert: { paddingHorizontal: spacing.lg, marginTop: spacing.lg },

  cardWrap: { marginTop: spacing.lg, marginHorizontal: spacing.lg },
});
