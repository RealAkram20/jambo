import { useCallback } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from '../api/jambo';
import type { Notification } from '../api/catalogue';
import { colors, fonts, radius, spacing, typography } from '../ui/theme';
import { Button, EmptyState, ErrorState, Loading } from '../ui/components';
import type { AppScreenProps } from '../navigation/types';

/**
 * The viewer's notifications.
 *
 * **`action_url` is deliberately not turned into a link.** It is a website URL,
 * and the app has no route table that maps site URLs onto its own screens — a
 * deep-link resolver is its own piece of work, and guessing at it would send
 * viewers to the wrong screen or to a browser they did not ask for. Rows are
 * therefore readable and not pressable, which is honest, rather than pressable
 * and wrong.
 *
 * The list is what `GET /notifications` holds. **Push delivery does not
 * exist** — `docs/api/coverage.md` records that the token registry is built
 * but no sender is, and nothing has been tested on a handset. So this screen
 * shows what the server has; it is not evidence that anything arrives while
 * the app is closed.
 */
export function NotificationsScreen(_: AppScreenProps<'Notifications'>) {
  const queryClient = useQueryClient();

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['notifications'],
    queryFn: () => api.notifications(),
  });

  const markRead = useMutation({
    mutationFn: () => api.markNotificationsRead(),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['notifications'] });
    },
  });

  const onMarkRead = useCallback(() => markRead.mutate(), [markRead]);

  if (isPending) return <Loading label="Loading notifications" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load your notifications.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  return (
    <View style={styles.screen}>
      <FlatList
        data={data.items}
        keyExtractor={(item: Notification, index) => item.id ?? String(index)}
        contentContainerStyle={styles.content}
        ListHeaderComponent={
          // Offered only when there is something to do. A "Mark all read"
          // button over an empty list, or over a list already read, is a
          // control whose only outcome is nothing happening.
          data.unread > 0 ? (
            <View style={styles.headerAction}>
              <Button
                label={markRead.isPending ? 'Marking…' : `Mark ${data.unread} as read`}
                tone="quiet"
                busy={markRead.isPending}
                onPress={onMarkRead}
              />
            </View>
          ) : null
        }
        ListEmptyComponent={
          <EmptyState
            title="No notifications"
            detail="Updates about your account and new titles will appear here."
          />
        }
        renderItem={({ item }) => <NotificationRow item={item} />}
      />
    </View>
  );
}

function NotificationRow({ item }: { item: Notification }) {
  const unread = item.read !== true;

  return (
    <View
      accessible
      // Unread is carried by the word, not only by the dot and the weight.
      // Status must never be colour alone, and "unread" is the whole point of
      // the row.
      accessibilityLabel={[unread ? 'Unread' : null, item.title, item.message]
        .filter((part): part is string => typeof part === 'string' && part !== '')
        .join('. ')}
      style={[styles.row, unread && styles.rowUnread]}
    >
      {unread ? <View style={styles.dot} /> : <View style={styles.dotSpacer} />}

      <View style={styles.rowText}>
        <Text style={[styles.title, unread && styles.titleUnread]}>{item.title}</Text>
        {typeof item.message === 'string' && item.message !== '' ? (
          <Text style={styles.message}>{item.message}</Text>
        ) : null}
        {typeof item.created_at === 'string' && item.created_at !== '' ? (
          <Text style={styles.when}>{formatWhen(item.created_at)}</Text>
        ) : null}
      </View>
    </View>
  );
}

function formatWhen(iso: string): string {
  const when = new Date(iso);
  if (Number.isNaN(when.getTime())) return '';

  return when.toLocaleDateString(undefined, {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, flexGrow: 1 },
  headerAction: { marginBottom: spacing.lg },

  row: {
    flexDirection: 'row',
    gap: spacing.md,
    padding: spacing.md,
    borderRadius: radius.card,
    marginBottom: spacing.sm,
    backgroundColor: colors.surface,
  },
  rowUnread: { borderWidth: 1, borderColor: colors.border },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginTop: 6,
    backgroundColor: colors.primary,
  },
  dotSpacer: { width: 8 },
  rowText: { flex: 1, gap: 2 },
  title: { ...typography.body, color: colors.text },
  titleUnread: { fontFamily: fonts.medium, color: colors.fieldText },
  message: { ...typography.caption, color: colors.textMuted },
  when: { ...typography.caption, color: colors.placeholder, marginTop: 2 },
});
