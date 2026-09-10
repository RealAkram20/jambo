import { createElement, useState } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { LinearGradient } from 'expo-linear-gradient';
import {
  DeviceMobile,
  Desktop,
  Devices as DevicesMark,
  Television,
  type Icon,
} from 'phosphor-react-native';

import { api } from '../../api/jambo';
import { ApiError, NetworkError } from '../../api/errors';
import type { Device } from '../../api/endpoints';
import { Alert, EmptyState, ErrorState, Loading } from '../../ui/components';
import { useConfirm } from '../../ui/overlay';
import { Focusable } from '../../ui/rails/Focusable';
import { colors, devices as t, fonts, spacing } from '../../ui/theme';
import { hasIcon, iconFor } from '../notifications/icons';
import { lastActive, splitByActivity, streamMeter, whereFrom } from './list';

/**
 * Everything signed into this account, from Rio's mockup of 2026-09-09.
 *
 * **Three of the mockup's elements were checked against the product and all
 * three needed his call**, recorded in the worklog with what was checked:
 *
 * - **A city per row.** Nothing stored one. The website shows the raw IP, and
 *   app installs had no address column at all. His call was to add
 *   geolocation, so there is now one — resolved locally, never stored, and
 *   falling back to the address when it cannot be known.
 * - **"4 of 6 devices used".** Jambo has no device-registration cap; nothing
 *   stops an account signing in a hundred installs. What the tier enforces is
 *   how many can watch at once, so that is what the meter counts and says.
 * - **The `+` button.** A device registers by signing in. There is nothing to
 *   add until TV sign-in exists, which is Phase 4, so it is gone.
 *
 * **The Active/Inactive split was not put to him**, because unlike the rest of
 * the drawing it had a real rule waiting for it: the server already ignores
 * anything not seen inside the session lifetime when it counts against the
 * cap. See `splitByActivity`.
 *
 * **One row action, not a menu.** The mockup draws an overflow on each active
 * row and a Remove button on each inactive one. Both would open a menu of one
 * item: sign out is the only thing this screen can do to a device. The
 * website's own row has a direct Sign out button, so that is what both get.
 */
export function DevicesScreen() {
  const queryClient = useQueryClient();
  const [banner, setBanner] = useState<string | null>(null);
  const [confirm, confirmDialog] = useConfirm();

  const devices = useQuery({
    queryKey: ['devices'],
    queryFn: () => api.devices(),
  });

  const revoke = useMutation({
    mutationFn: (id: string) => api.revokeDevice(id),
    onSuccess: async () => {
      setBanner(null);
      await queryClient.invalidateQueries({ queryKey: ['devices'] });
    },
    onError: (error: unknown) => {
      if (error instanceof NetworkError) {
        setBanner('Could not reach Jambo. The device was not signed out.');
      } else if (error instanceof ApiError && error.code === 'DEVICE_NOT_FOUND') {
        // Already gone — signed out from somewhere else in the meantime. The
        // list is refreshed rather than an error shown for something that has
        // already happened.
        void queryClient.invalidateQueries({ queryKey: ['devices'] });
      } else {
        setBanner(error instanceof ApiError ? error.message : 'The device was not signed out.');
      }
    },
  });

  const confirmRevoke = async (device: Device) => {
    const id = device.id;
    if (typeof id !== 'string') return;

    const yes = await confirm({
      title: 'Sign this device out?',
      message: `${device.name ?? 'That device'} will have to sign in again.`,
      confirmLabel: 'Sign out',
      destructive: true,
    });

    if (yes) revoke.mutate(id);
  };

  /**
   * Signing out everything else.
   *
   * One request per device rather than a bulk endpoint, because there is no
   * bulk endpoint and inventing one for a screen is the wrong order. The
   * current device is excluded — signing yourself out from your own device
   * list is a control whose result is being thrown back to the sign-in screen.
   */
  const revokeAll = useMutation({
    mutationFn: async (targets: readonly Device[]) => {
      for (const device of targets) {
        if (typeof device.id === 'string') await api.revokeDevice(device.id);
      }
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['devices'] }),
    onError: () => setBanner('Some devices were not signed out. Pull to refresh and check.'),
  });

  if (devices.isPending) return <Loading label="Loading your devices" />;

  if (devices.isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            devices.error instanceof NetworkError
              ? 'Could not reach Jambo. Check your connection.'
              : 'Your devices could not be loaded.'
          }
          onRetry={() => void devices.refetch()}
        />
      </View>
    );
  }

  const all = devices.data.devices;
  const { active, inactive } = splitByActivity(all);
  const meter = streamMeter(devices.data.streams);
  const others = all.filter((d) => d.is_current !== true);

  return (
    <View style={styles.screen}>
      <FlatList
        data={inactive}
        keyExtractor={(device, index) => String(device.id ?? index)}
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshing={devices.isFetching}
        onRefresh={() => void devices.refetch()}
        ItemSeparatorComponent={Gap}
        ListHeaderComponent={
          <View>
            {banner !== null ? <Alert tone="error">{banner}</Alert> : null}

            <Hero />

            {meter === null ? null : (
              <View style={styles.meter}>
                <Text style={styles.meterLabel}>{meter.label}</Text>
                <View style={styles.track}>
                  <View
                    style={[
                      styles.fill,
                      { width: `${Math.round(meter.fraction * 100)}%` },
                      meter.full ? styles.fillFull : null,
                    ]}
                  />
                </View>
                {/*
                  A real constraint, and only when it bites. Below the cap the
                  bar says everything there is to say.
                */}
                {meter.full ? (
                  <Text style={styles.meterNote}>
                    Sign a device out to watch on another one.
                  </Text>
                ) : null}
              </View>
            )}

            <View style={styles.headingRow}>
              <Text accessibilityRole="header" style={styles.heading}>
                Active Devices
              </Text>
              {others.length > 0 ? (
                <Focusable
                  accessibilityRole="button"
                  accessibilityLabel={`Sign out all other devices, ${String(others.length)}`}
                  ringRadius={8}
                  onPress={() => {
                    void confirm({
                      title: 'Sign out everywhere else?',
                      message: `${others.length} other ${others.length === 1 ? 'device' : 'devices'} will have to sign in again.`,
                      confirmLabel: 'Sign out all',
                      destructive: true,
                    }).then((yes) => {
                      if (yes) revokeAll.mutate(others);
                    });
                  }}
                  style={styles.signOutAll}
                >
                  <Text style={styles.signOutAllText}>
                    {revokeAll.isPending ? 'Signing out…' : 'Sign out all'}
                  </Text>
                </Focusable>
              ) : null}
            </View>

            {active.map((device, index) => (
              <View key={String(device.id ?? index)} style={styles.activeWrap}>
                <DeviceRow device={device} onSignOut={() => void confirmRevoke(device)} />
              </View>
            ))}

            {inactive.length > 0 ? (
              <Text accessibilityRole="header" style={[styles.heading, styles.headingAlone]}>
                Inactive Devices
              </Text>
            ) : null}
          </View>
        }
        ListEmptyComponent={
          active.length === 0 ? (
            <EmptyState title="Nothing else is signed in" detail="Devices you sign in on appear here." />
          ) : null
        }
        renderItem={({ item }) => (
          <DeviceRow device={item} onSignOut={() => void confirmRevoke(item)} />
        )}
      />

      {confirmDialog}
    </View>
  );
}

function Gap() {
  return <View style={styles.gap} />;
}

/**
 * The hero.
 *
 * The mockup puts an illustration of a television, a laptop and a phone beside
 * the headline. The app has no such artwork and inventing one is not this
 * screen's job, so the mark is the icon set's own devices glyph — the same one
 * the profile menu's Devices row uses.
 *
 * **No sentence under the headline.** The mockup has "Manage your devices and
 * enjoy Jambo Films anywhere, anytime", which is the exact shape Rio's copy
 * rule names: a heading, then a line explaining the heading. Deleting it loses
 * nothing.
 */
function Hero() {
  return (
    <LinearGradient
      colors={[t.heroFrom, t.heroTo]}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={styles.hero}
    >
      <Text accessibilityRole="header" style={styles.heroTitle}>
        Watch on your{'\n'}favourite devices
      </Text>
      <DevicesMark size={t.heroMarkSize} color={colors.onPrimary} weight="fill" />
    </LinearGradient>
  );
}

/**
 * Which glyph a device wears.
 *
 * **The server's answer, not this screen's.** `icon` is what
 * `UserAgent::parse` decides for a browser row — the same call the website's
 * own device list draws from — and the platform's own glyph for an app
 * install. Deriving it here from `kind` would be a second opinion about the
 * same user agent, and the two lists would then disagree about the same
 * browser.
 *
 * `iconFor` is the notification inbox's map, reused rather than copied: both
 * screens are turning the site's Phosphor class names into the same glyphs,
 * and two maps would drift the first time one gained an entry.
 *
 * The fallback is by `kind`, for a row from a server too old to send an icon.
 * It is never taken from the name: a session called "Smart TV (Samsung)" is a
 * browser and one called "Windows PC" is a desktop, and parsing that out of
 * free text is how a row ends up with the wrong picture.
 */
function markFor(device: Device): Icon {
  if (hasIcon(device.icon)) return iconFor(device.icon) as Icon;

  if (device.platform === 'android_tv') return Television;
  if (device.kind === 'app') return DeviceMobile;

  return Desktop;
}

function DeviceRow({ device, onSignOut }: { device: Device; onSignOut: () => void }) {
  const current = device.is_current === true;
  const where = whereFrom(device);
  const when = lastActive(device.last_seen_at);
  const meta = [where, when].filter((part): part is string => part !== null && part !== '');

  return (
    <View
      accessible
      accessibilityLabel={[device.name, current ? 'This device' : null, ...meta]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join('. ')}
      style={styles.row}
    >
      <View style={styles.mark}>
        {createElement(markFor(device), {
          size: t.markIconSize,
          color: colors.primary,
          weight: 'regular',
        })}
      </View>

      <View style={styles.rowText}>
        <View style={styles.nameRow}>
          <Text style={styles.name} numberOfLines={1}>
            {device.name ?? 'Unknown device'}
          </Text>
          {/*
            A word, not only a colour. It is also the reason this row has no
            sign-out control: signing yourself out from your own device list
            throws you back to the sign-in screen.
          */}
          {current ? (
            <View style={styles.current}>
              <Text style={styles.currentText}>This device</Text>
            </View>
          ) : null}
        </View>

        {meta.length > 0 ? (
          <Text style={styles.meta} numberOfLines={1}>
            {meta.join(' · ')}
          </Text>
        ) : null}
      </View>

      {current ? null : (
        <Focusable
          accessibilityRole="button"
          accessibilityLabel={`Sign out ${device.name ?? 'this device'}`}
          ringRadius={8}
          onPress={onSignOut}
          style={styles.signOut}
        >
          <Text style={styles.signOutText}>Sign out</Text>
        </Focusable>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: spacing.xxl, flexGrow: 1 },

  hero: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.lg,
    borderRadius: t.heroRadius,
    padding: t.heroPad,
    marginTop: spacing.md,
  },
  heroTitle: {
    flex: 1,
    fontFamily: fonts.bold,
    fontSize: t.heroTitleSize,
    lineHeight: t.heroTitleSize + 8,
    color: colors.onPrimary,
  },

  meter: {
    marginTop: spacing.lg,
    padding: t.meterPad,
    borderRadius: t.meterRadius,
    borderWidth: 1,
    borderColor: t.meterBorder,
    gap: spacing.sm,
  },
  meterLabel: { fontFamily: fonts.medium, fontSize: t.titleSize, color: t.titleColor },
  track: {
    height: t.meterHeight,
    borderRadius: t.meterHeight / 2,
    backgroundColor: t.meterTrack,
    overflow: 'hidden',
  },
  fill: { height: '100%', borderRadius: t.meterHeight / 2, backgroundColor: t.meterFill },
  fillFull: { backgroundColor: t.meterFull },
  meterNote: { fontFamily: fonts.regular, fontSize: t.metaSize, color: t.metaColor },

  /*
   * The row owns the spacing, the text owns none.
   *
   * They both carried `marginTop: spacing.xl` in the first cut, which stacked
   * into a gap twice the intended size above "Active Devices" — visible on the
   * emulator as a hole between the meter and the list. A shared text style used
   * both inside a laid-out row and on its own must not bring its own margins.
   */
  headingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.xl,
    marginBottom: spacing.md,
  },
  heading: { fontFamily: fonts.bold, fontSize: 17, color: colors.text },
  headingAlone: { marginTop: spacing.xl, marginBottom: spacing.md },
  signOutAll: { paddingVertical: spacing.xs, paddingHorizontal: spacing.xs },
  signOutAllText: { fontFamily: fonts.medium, fontSize: t.metaSize, color: colors.errorText },

  activeWrap: { marginBottom: spacing.sm },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: t.rowGap,
    padding: t.rowPad,
    borderRadius: t.rowRadius,
    borderWidth: t.rowBorderWidth,
    borderColor: t.rowBorder,
  },
  mark: {
    width: t.markSize,
    height: t.markSize,
    borderRadius: t.markRadius,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: t.rowBorder,
  },
  rowText: { flex: 1, gap: 2 },
  nameRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  name: { flexShrink: 1, fontFamily: fonts.medium, fontSize: t.titleSize, color: t.titleColor },
  current: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
    backgroundColor: t.currentBg,
  },
  currentText: { fontFamily: fonts.medium, fontSize: t.currentSize, color: t.currentFg },
  meta: { fontFamily: fonts.regular, fontSize: t.metaSize, color: t.metaColor },
  signOut: { paddingVertical: spacing.xs, paddingHorizontal: spacing.sm },
  signOutText: { fontFamily: fonts.medium, fontSize: t.metaSize, color: colors.errorText },

  gap: { height: spacing.sm },
});
