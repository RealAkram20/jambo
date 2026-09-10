import { useState } from 'react';
import { ScrollView, StyleSheet, Switch, View } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Bell, EnvelopeSimple } from 'phosphor-react-native';

import { api } from '../../api/jambo';
import { ApiError, NetworkError } from '../../api/errors';
import { Alert, Caption, ErrorState, Heading, Loading } from '../../ui/components';
import { ListCard, ListRow } from '../../ui/list';
import { colors, spacing } from '../../ui/theme';
import type { AppScreenProps } from '../../navigation/types';

type Channels = { in_app: boolean; email: boolean; push: boolean };

/**
 * The screen behind the inbox's gear.
 *
 * A port of the website's "Delivery preferences" card on
 * `/{username}/notifications` — the same switches, over the same three columns
 * on `users`, which are layer 4 of the gate in `ChannelGatedNotification`.
 *
 * **Push is deliberately absent, and it is the interesting omission.** The
 * site has a third switch and this screen has two. On the website that switch
 * drives a browser Web Push subscription, so in the app it would do one of two
 * wrong things: silence the viewer's *browser* notifications from their phone,
 * or toggle a flag for an app channel that cannot deliver. The app registers
 * no FCM token and `docs/api/coverage.md` records that the registry has no
 * sender. The endpoint carries `push` either way, so the row is a few lines
 * away on the day push actually delivers.
 *
 * **"Mark all read" is not here, and used to be.** It was parked on this
 * screen when the inbox's own header had no room for it, and Rio moved it back
 * on 2026-09-10: *"we need the abillity to make all read and clear them."* The
 * inbox now carries it beside "Delete all", which is where the website keeps
 * both. Two ways to mark an inbox read is one too many.
 */
export function NotificationSettingsScreen(_: AppScreenProps<'NotificationSettings'>) {
  const queryClient = useQueryClient();
  const [failed, setFailed] = useState<string | null>(null);

  const prefs = useQuery({
    queryKey: ['notification-channels'],
    queryFn: () => api.notificationChannels(),
  });

  /*
   * `onMutate` moves the switch immediately and `onError` puts it back. A
   * toggle that waits for a round trip on a Ugandan mobile connection feels
   * broken, people press it again, and the second press undoes the first —
   * the same reasoning, and the same shape, as the streaming preferences.
   */
  const save = useMutation({
    mutationFn: (changes: Partial<Channels>) => api.setNotificationChannels(changes),
    onMutate: async (changes) => {
      setFailed(null);
      await queryClient.cancelQueries({ queryKey: ['notification-channels'] });

      const previous = queryClient.getQueryData<{ channels: Channels; emailVerified: boolean }>([
        'notification-channels',
      ]);

      if (previous !== undefined) {
        queryClient.setQueryData(['notification-channels'], {
          ...previous,
          channels: { ...previous.channels, ...changes },
        });
      }

      return { previous };
    },
    onError: (error, _changes, context) => {
      if (context?.previous !== undefined) {
        queryClient.setQueryData(['notification-channels'], context.previous);
      }

      setFailed(
        error instanceof NetworkError
          ? 'Could not reach Jambo, so that change was not saved.'
          : error instanceof ApiError
            ? error.message
            : 'That change was not saved.',
      );
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['notification-channels'] }),
  });

  if (prefs.isPending) return <Loading label="Loading settings" />;

  if (prefs.isError) {
    return (
      <View style={styles.state}>
        <ErrorState
          message={
            prefs.error instanceof Error && prefs.error.message !== ''
              ? prefs.error.message
              : 'We could not load your notification settings.'
          }
          onRetry={() => {
            void prefs.refetch();
          }}
        />
      </View>
    );
  }

  const { channels, emailVerified } = prefs.data;

  return (
    <ScrollView contentContainerStyle={styles.screen}>
      {failed !== null ? <Alert tone="error">{failed}</Alert> : null}

      <Heading>Delivery</Heading>

      <ListCard style={styles.card}>
        <ListRow
          icon={Bell}
          label="In-app"
          accessory={
            <Switch
              value={channels.in_app}
              onValueChange={(next) => save.mutate({ in_app: next })}
              disabled={save.isPending}
              accessibilityLabel="In-app notifications"
              trackColor={{ false: colors.inputBg, true: colors.primary }}
              thumbColor={colors.fieldText}
            />
          }
        />

        <ListRow
          icon={EnvelopeSimple}
          label="Email"
          /*
           * The one sentence on this screen, and it earns its place: an
           * unverified address is why the emails a viewer switched on will
           * not arrive, and the switch cannot say that itself.
           */
          {...(emailVerified ? {} : { detail: 'Your email address is not verified yet.' })}
          accessory={
            <Switch
              value={channels.email}
              onValueChange={(next) => save.mutate({ email: next })}
              disabled={save.isPending}
              accessibilityLabel="Email notifications"
              trackColor={{ false: colors.inputBg, true: colors.primary }}
              thumbColor={colors.fieldText}
            />
          }
          last
        />
      </ListCard>

      {/*
        Security-critical mail goes out whatever these say. A viewer who
        switches email off and then cannot find their password reset has been
        misled by a switch that told them the truth about the wrong thing.

        Wrapped rather than styled: `Caption` takes no style prop, and rendered
        bare it sat against the card's bottom edge, reading as a clipped third
        row instead of a footnote to the two above it.
      */}
      <View style={styles.caption}>
        <Caption>Security emails are always sent.</Caption>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { padding: spacing.lg, paddingBottom: spacing.xxl },
  state: { flex: 1, backgroundColor: colors.background },
  card: { marginTop: spacing.md },
  caption: { marginTop: spacing.md },
});
