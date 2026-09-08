import { useQuery } from '@tanstack/react-query';
import { StyleSheet, Text, View } from 'react-native';

import { api } from '../api/jambo';
import { NetworkError } from '../api/errors';
import { useAuth } from '../auth/AuthProvider';
import { appVersion } from '../config/version';
import {
  Alert,
  Body,
  Button,
  Caption,
  Divider,
  ErrorState,
  Heading,
  Loading,
  Screen,
  Surface,
} from '../ui/components';
import { preloaderSource } from '../ui/branding';
import { colors, spacing, typography } from '../ui/theme';
import type { AppScreenProps } from '../navigation/types';

const MONTHS = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];

/**
 * Formats a server timestamp, or gives back null.
 *
 * Built by hand rather than through `toLocaleDateString` because Hermes' ICU
 * data varies by build, and a date that silently renders as "Invalid Date" on
 * one handset is exactly the class of bug that never appears on a desk.
 *
 * Null in, null out — and the caller must render nothing rather than a
 * placeholder. A renewal date is a promise about somebody's money.
 */
function formatDate(iso: string | null | undefined): string | null {
  if (iso === null || iso === undefined || iso === '') return null;

  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return null;

  const month = MONTHS[date.getMonth()];
  if (month === undefined) return null;

  return `${date.getDate()} ${month} ${date.getFullYear()}`;
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

export function AccountScreen({ navigation }: AppScreenProps<'Account'>) {
  const { user, signOut, launchOffline, branding } = useAuth();

  const me = useQuery({
    queryKey: ['me'],
    queryFn: () => api.me(),
  });

  if (me.isPending) {
    return (
      <Screen>
        <Loading label="Loading your account" source={preloaderSource(branding)} />
      </Screen>
    );
  }

  if (me.isError) {
    return (
      <Screen>
        <ErrorState
          message={
            me.error instanceof NetworkError
              ? 'Could not reach Jambo. Check your connection.'
              : 'Your account could not be loaded.'
          }
          onRetry={() => void me.refetch()}
        />
      </Screen>
    );
  }

  const { user: profile, subscription, limits } = me.data;
  const name = [profile?.first_name, profile?.last_name].filter(Boolean).join(' ');
  const endsAt = formatDate(subscription?.ends_at);

  /*
   * `null` means the plan sets no cap, and the spec says in as many words to
   * render "unlimited" rather than "none". They are opposite claims, and the
   * wrong one tells a paying viewer they cannot watch on a second device.
   */
  const streams =
    limits?.max_concurrent_streams === null || limits?.max_concurrent_streams === undefined
      ? 'Unlimited'
      : String(limits.max_concurrent_streams);

  return (
    <Screen scroll>
      {launchOffline ? (
        <Alert tone="error">
          Jambo could not be reached when the app started. Some details may be out of date.
        </Alert>
      ) : null}

      <Surface>
        <Heading>{name !== '' ? name : (profile?.username ?? 'Your account')}</Heading>
        <Caption>{profile?.email ?? user?.email ?? ''}</Caption>
      </Surface>

      <View style={styles.section}>
        <Heading>Subscription</Heading>
        <Surface>
          {subscription?.tier?.name !== undefined ? (
            <>
              <Row label="Plan" value={subscription.tier.name} />
              <Divider />
              {/* No renewal row at all when the server did not give a date.
                  An em dash next to "Renews" still reads as a promise. */}
              {endsAt !== null ? (
                <>
                  <Row label="Renews" value={endsAt} />
                  <Divider />
                </>
              ) : null}
              <Row label="Devices at once" value={streams} />
            </>
          ) : (
            <Body>You do not have an active plan.</Body>
          )}
        </Surface>

        {/*
          ADR-0004: the Play build is consumption-only — no purchase, no
          checkout, and no call to action leading to one. This sentence carries
          no URL and no button on either variant for now; the `direct` build's
          PesaPal flow arrives with the slice that implements it, and until
          then a link here would lead nowhere on both.
        */}
        <Caption>Plans and payments are managed on the Jambo website.</Caption>
      </View>

      <View style={styles.section}>
        <Button label="Devices" tone="quiet" onPress={() => navigation.navigate('Devices')} />
      </View>

      <View style={styles.section}>
        <Button label="Sign out" tone="danger" onPress={() => void signOut()} />
      </View>

      <View style={styles.footer}>
        <Caption>{appVersion() !== null ? `Jambo ${appVersion()}` : 'Jambo'}</Caption>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  section: { marginTop: spacing.xl },
  footer: { marginTop: spacing.xxl, alignItems: 'center' },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: spacing.md,
  },
  rowLabel: { ...typography.caption, color: colors.textMuted },
  rowValue: { ...typography.body, color: colors.text },
});
