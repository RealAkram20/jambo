import { useCallback, useLayoutEffect, useMemo, useState } from 'react';
import { SectionList, StyleSheet, Text, View } from 'react-native';
import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { LinearGradient } from 'expo-linear-gradient';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Checks, DotsThreeVertical, GearSix, Trash } from 'phosphor-react-native';

import { api } from '../../api/jambo';
import type { Notification } from '../../api/catalogue';
import { EmptyState, ErrorState, Loading, Spinner } from '../../ui/components';
import { ListCard, ListRow } from '../../ui/list';
import { Sheet, useConfirm } from '../../ui/overlay';
import { Focusable } from '../../ui/rails/Focusable';
import { SelectionBar } from '../../ui/SelectionBar';
import { colors, fonts, notifications as t, spacing, touchPadding } from '../../ui/theme';
import type { AppScreenProps } from '../../navigation/types';
import { NotificationRow } from './NotificationRow';
import {
  categoryForChip,
  emptyTitleFor,
  groupByDay,
  idsOf,
  NOTIFICATION_CHIPS,
  toggle,
  unreadAmong,
  type NotificationChip,
} from './list';
import { useNow } from './useNow';

/**
 * The inbox, built from Rio's mockup of 2026-09-09.
 *
 * Its row is the website's own — see `NotificationRow` — and its three
 * additions to that row are the chips, the day headings and the unread dot.
 *
 * **Push delivery still does not exist.** `docs/api/coverage.md` records that
 * the FCM token registry is built and no sender is, and the app registers no
 * token at all. So this screen shows what the server holds; it is not evidence
 * that anything arrives while the app is closed. That is exactly the split the
 * push standard asks for — the polled list is the transport, push is the
 * accelerator — and it is why this screen is worth having before push works.
 */
const HEADER_ICON = 22;

export function NotificationsScreen({ navigation }: AppScreenProps<'Notifications'>) {
  const queryClient = useQueryClient();
  const insets = useSafeAreaInsets();
  const [chip, setChip] = useState<NotificationChip>('all');
  const [confirm, confirmDialog] = useConfirm();

  /**
   * The rows a long press has picked out, by id.
   *
   * **Selection mode is the set being non-empty**, rather than a flag beside
   * it. Two pieces of state for one fact is how a bar ends up on screen with
   * nothing selected, and unpicking the last row is the same gesture as
   * cancelling — so it does the same thing.
   */
  const [selected, setSelected] = useState<ReadonlySet<string>>(new Set());
  const selecting = selected.size > 0;

  /** The overflow sheet. See `InboxMenu`. */
  const [menuOpen, setMenuOpen] = useState(false);

  const category = categoryForChip(chip);

  const {
    data,
    isPending,
    isError,
    error,
    refetch,
    isFetching,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
  } = useInfiniteQuery({
    // The chip is part of the key, so each filter keeps its own pages and its
    // own cursor. Sharing one key would append a filtered page onto an
    // unfiltered one and produce a list that is neither.
    queryKey: ['notifications', category],
    queryFn: ({ pageParam }: { pageParam: string | undefined }) =>
      api.notifications({ category, cursor: pageParam ?? null }),
    initialPageParam: undefined as string | undefined,
    getNextPageParam: (last) => last.nextCursor ?? undefined,
  });

  /**
   * Marking one read.
   *
   * Optimistic on this row only, because the server's answer changes nothing a
   * viewer can see: the endpoint is idempotent and returns the new unread
   * count, and the dot has already gone. Invalidating every chip on settle
   * keeps the other four honest without four extra requests up front.
   */
  const markRead = useMutation({
    mutationFn: (id: string) => api.markNotificationRead(id),
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });

  /**
   * Mark everything read, and empty the inbox.
   *
   * Rio asked for both on this screen, 2026-09-10. "Mark all read" had been
   * put behind the gear when the mockup's header had no room for it; his
   * instruction supersedes that, and it is also where the website keeps them —
   * `profile-hub/notifications.blade.php` puts both in the inbox's own header,
   * each shown only when it has something to do.
   *
   * They are one request each rather than one per row. The endpoints are bulk
   * and idempotent, so a retry over a dropped connection cannot half-finish.
   */
  const markAll = useMutation({
    mutationFn: () => api.markNotificationsRead(),
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });

  const clearAll = useMutation({
    mutationFn: () => api.clearNotifications(),
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });

  const items = useMemo(
    () => data?.pages.flatMap((page) => page.items) ?? [],
    [data],
  );

  const leaveSelection = useCallback(() => setSelected(new Set()), []);

  /**
   * The selected rows, in bulk.
   *
   * One request per row rather than a bulk endpoint, because the API has none
   * for a subset — and both per-row endpoints are documented idempotent, so a
   * retry over a dropped connection cannot act on the wrong row and a failure
   * part-way through leaves a state the refetch below simply reports.
   *
   * The selection is cleared on success only. A failure that emptied it would
   * make the viewer pick the same eleven rows again to retry.
   */
  const removeSelected = useMutation({
    mutationFn: async (ids: readonly string[]) => {
      for (const id of ids) await api.deleteNotification(id);
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
    onSuccess: leaveSelection,
  });

  const readSelected = useMutation({
    mutationFn: async (ids: readonly string[]) => {
      for (const id of ids) await api.markNotificationRead(id);
    },
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
    onSuccess: leaveSelection,
  });

  /**
   * A press means "select" while anything is selected, and "mark read"
   * otherwise.
   *
   * The mode is what makes a tap on an already-read row do something: outside
   * selection it correctly does nothing, and inside it must pick the row like
   * any other. Toggling to empty leaves selection, which is why cancelling and
   * unpicking the last row need not be told apart.
   */
  const onPressRow = useCallback(
    (item: Notification) => {
      if (typeof item.id !== 'string') return;

      if (selecting) {
        setSelected((current) => toggle(current, item.id as string));
        return;
      }

      if (item.read === true) return;
      markRead.mutate(item.id);
    },
    [markRead, selecting],
  );

  const onLongPressRow = useCallback((item: Notification) => {
    if (typeof item.id !== 'string') return;
    setSelected((current) => toggle(current, item.id as string));
  }, []);

  /** Rio's words on the website's own button, with its consequence beneath. */
  const onClearAll = useCallback(async () => {
    const yes = await confirm({
      title: 'Delete all notifications?',
      message: 'This cannot be undone.',
      confirmLabel: 'Delete all',
      destructive: true,
    });

    if (yes) clearAll.mutate();
  }, [clearAll, confirm]);

  const onDeleteSelected = useCallback(async () => {
    const ids = idsOf(items, selected);
    if (ids.length === 0) return;

    const yes = await confirm({
      title: ids.length === 1 ? 'Delete this notification?' : `Delete ${ids.length} notifications?`,
      message: 'This cannot be undone.',
      confirmLabel: 'Delete',
      destructive: true,
    });

    if (yes) removeSelected.mutate(ids);
  }, [confirm, items, removeSelected, selected]);

  /**
   * One control in the header, and it opens everything occasional.
   *
   * **Rio, 2026-09-10, on the first cut:** *"i think we can hide these
   * initially or we can upgrade this in the way the feels clean and modern
   * simple creative."* That cut put "Mark all read" and "Delete all" above the
   * chips as a filled blue pill and an outlined red one — two loud buttons
   * shouting at a viewer who came to read a list, and the red one shouting
   * loudest of the two.
   *
   * They are housekeeping. Housekeeping belongs behind one quiet control, so
   * the overflow replaces the gear rather than joining it: three occasional
   * actions in one place instead of two pills and a separate icon, and the
   * inbox above the fold is nothing but the inbox.
   */
  useLayoutEffect(() => {
    navigation.setOptions({
      headerRight: () => (
        <Focusable
          accessibilityRole="button"
          accessibilityLabel="More"
          accessibilityHint="Mark all read, delete all, and settings"
          ringRadius={HEADER_ICON}
          onPress={() => setMenuOpen(true)}
          style={styles.headerAction}
        >
          <DotsThreeVertical size={HEADER_ICON} color={colors.text} weight="bold" />
        </Focusable>
      ),
    });
  }, [navigation]);

  /* One clock for the whole render, ticking once a minute. See `useNow`. */
  const now = useNow();
  const sections = useMemo(() => groupByDay(items, now), [items, now]);

  /*
   * The whole inbox's unread count, not this chip's. The endpoint sends it
   * that way on purpose, and "Mark all read" is a whole-inbox action — a
   * button that vanished because the Offers chip happened to be all read
   * would be lying about the other four.
   */
  const unread = data?.pages[0]?.unread ?? 0;
  const readable = unreadAmong(items, selected);
  const busy = removeSelected.isPending || readSelected.isPending;

  if (isPending) return <Loading label="Loading notifications" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <Chips chip={chip} onChange={setChip} />
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
      <SectionList
        sections={sections}
        keyExtractor={(item, index) => item.id ?? String(index)}
        contentContainerStyle={styles.content}
        stickySectionHeadersEnabled={false}
        showsVerticalScrollIndicator={false}
        refreshing={isFetching && !isFetchingNextPage}
        onRefresh={() => void refetch()}
        ListHeaderComponent={<Chips chip={chip} onChange={setChip} />}
        ListEmptyComponent={<EmptyState title={emptyTitleFor(chip)} />}
        ListFooterComponent={
          isFetchingNextPage ? (
            <View style={styles.footer}>
              <Spinner />
            </View>
          ) : null
        }
        onEndReached={() => {
          if (hasNextPage && !isFetchingNextPage) void fetchNextPage();
        }}
        onEndReachedThreshold={0.5}
        renderSectionHeader={({ section }) => (
          <Text accessibilityRole="header" style={styles.section}>
            {section.title}
          </Text>
        )}
        renderItem={({ item }) => (
          <NotificationRow
            item={item}
            now={now}
            selecting={selecting}
            selected={typeof item.id === 'string' && selected.has(item.id)}
            onPress={onPressRow}
            onLongPress={onLongPressRow}
          />
        )}
        ItemSeparatorComponent={Separator}
      />

      {selecting ? (
        <SelectionBar
          count={selected.size}
          busy={busy}
          bottomInset={insets.bottom}
          onCancel={leaveSelection}
          actions={[
            // Absent rather than disabled when every picked row is already
            // read: see `SelectionBar`.
            ...(readable === 0
              ? []
              : [
                  {
                    key: 'read',
                    label: 'Mark read',
                    accessibilityLabel:
                      readable === 1 ? 'Mark 1 as read' : `Mark ${readable} as read`,
                    icon: Checks,
                    onPress: () => readSelected.mutate(idsOf(items, selected)),
                  },
                ]),
            {
              key: 'delete',
              label: 'Delete',
              accessibilityLabel:
                selected.size === 1
                  ? 'Delete 1 notification'
                  : `Delete ${selected.size} notifications`,
              icon: Trash,
              destructive: true,
              onPress: () => void onDeleteSelected(),
            },
          ]}
        />
      ) : null}

      <InboxMenu
        open={menuOpen}
        unread={unread}
        total={items.length}
        onClose={() => setMenuOpen(false)}
        onMarkAll={() => markAll.mutate()}
        onClearAll={() => void onClearAll()}
        onSettings={() => navigation.navigate('NotificationSettings')}
      />

      {confirmDialog}
    </View>
  );
}

/**
 * Everything the inbox can be told to do, in one sheet.
 *
 * **Each row appears only when it has something to do**, which is what
 * `Modules/Notifications/resources/views/index.blade.php` does with the same
 * two buttons: "Mark all as read" behind `$unreadCount > 0`, "Delete all"
 * behind `$notifications->total() > 0`. Settings is always there, because it
 * is a destination rather than an action.
 *
 * **"Delete all" means the whole inbox, not the chip.** The endpoint is
 * `DELETE /notifications` and the website has no filter to disagree with it.
 * It closes the sheet and asks the question in the app's own dialog, so the
 * one irreversible thing on this screen takes two deliberate presses; a
 * viewer who wants a subset has long-press selection for exactly that.
 */
function InboxMenu({
  open,
  unread,
  total,
  onClose,
  onMarkAll,
  onClearAll,
  onSettings,
}: {
  open: boolean;
  unread: number;
  total: number;
  onClose: () => void;
  onMarkAll: () => void;
  onClearAll: () => void;
  onSettings: () => void;
}) {
  /* Close first, act second. A sheet still on screen while a confirmation
     opens over it stacks two overlays for one decision. */
  const run = (action: () => void) => () => {
    onClose();
    action();
  };

  return (
    <Sheet visible={open} onClose={onClose}>
      <ListCard>
        {unread === 0 ? null : (
          <ListRow
            icon={Checks}
            label="Mark all read"
            accessibilityLabel={unread === 1 ? 'Mark 1 as read' : `Mark all ${unread} as read`}
            chevron={false}
            onPress={run(onMarkAll)}
          />
        )}

        <ListRow icon={GearSix} label="Notification settings" onPress={run(onSettings)} />

        {total === 0 ? null : (
          <ListRow
            icon={Trash}
            label="Delete all"
            tone="danger"
            accessibilityLabel="Delete all notifications"
            chevron={false}
            onPress={run(onClearAll)}
            last
          />
        )}
      </ListCard>
    </Sheet>
  );
}

function Separator() {
  return <View style={styles.separator} />;
}

/**
 * The five filter chips.
 *
 * The website's inbox is a flat list with no filter, so this is the one part
 * of the screen with no asset behind it. It therefore wears the app's own
 * existing filter language — the watchlist's gradient for the active state,
 * the site's hairline for the resting one — rather than a third opinion about
 * what a selected filter looks like.
 *
 * Not scrollable. Five short labels fit a 360dp handset, and a scrolling strip
 * hides chips behind an edge nobody knows to drag.
 */
function Chips({
  chip,
  onChange,
}: {
  chip: NotificationChip;
  onChange: (chip: NotificationChip) => void;
}) {
  return (
    <View style={styles.chips} accessibilityRole="tablist">
      {NOTIFICATION_CHIPS.map(({ key, label }) => {
        const active = key === chip;

        return (
          <Focusable
            key={key}
            accessibilityRole="tab"
            // The label alone collides with the header's own "Account" control
            // when a script or a screen reader searches by name, and it cost a
            // parallel session three misnavigations. "Account filter" says
            // which of the two this is.
            accessibilityLabel={`${label} filter`}
            accessibilityState={{ selected: active }}
            ringRadius={t.chipRadius}
            onPress={() => onChange(key)}
            style={[styles.chip, active ? null : styles.chipIdle]}
          >
            {active ? (
              <LinearGradient
                colors={[t.chipActiveFrom, t.chipActiveTo]}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={StyleSheet.absoluteFill}
              />
            ) : null}
            <Text style={[styles.chipText, active ? styles.chipTextOn : null]}>{label}</Text>
          </Focusable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: spacing.xxl, flexGrow: 1 },

  /**
   * The touch target around the header icon, carried over from
   * `RootNavigator` with the gear this replaced.
   *
   * `touchPadding` rather than a spacing step, because the step that looked
   * right was `spacing.xs` and it gave a 30dp target — measured on the
   * emulator with `uiautomator`, which reported the gear at 90x90 physical
   * pixels at density 3. The app's own floor is 48.
   */
  headerAction: { padding: touchPadding(HEADER_ICON) },

  chips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: t.chipGap,
    paddingTop: spacing.sm,
    paddingBottom: spacing.lg,
  },
  chip: {
    height: t.chipHeight,
    paddingHorizontal: t.chipPadX,
    borderRadius: t.chipRadius,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  chipIdle: { borderWidth: 1, borderColor: t.chipBorder },
  chipText: { fontFamily: fonts.medium, fontSize: 13, color: t.chipIdleText },
  chipTextOn: { color: t.chipActiveText },

  section: {
    fontFamily: fonts.bold,
    fontSize: t.sectionSize,
    color: t.sectionColor,
    marginTop: spacing.lg,
    marginBottom: spacing.md,
  },
  separator: { height: spacing.sm },
  footer: { paddingVertical: spacing.lg, alignItems: 'center' },
});
