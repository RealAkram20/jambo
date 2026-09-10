import { useCallback, useMemo, useState } from 'react';
import { FlatList, Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigation } from '@react-navigation/native';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { LinearGradient } from 'expo-linear-gradient';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { ArrowsDownUp, CaretDown, Check, Trash, X } from 'phosphor-react-native';

import { api } from '../../api/jambo';
import type { WatchlistCard as WatchlistItemCard } from '../../api/catalogue';
import { cardKey } from '../../api/catalogue';
import type { AppStackParams } from '../../navigation/types';
import { AppHeader } from '../../ui/AppHeader';
import { EmptyState, ErrorState, Loading } from '../../ui/components';
import { Focusable } from '../../ui/rails/Focusable';
import { card, colors, fonts, rail, spacing, watchlist as t } from '../../ui/theme';
import { useRailMetrics } from '../../ui/metrics';
import { WatchlistCard } from './WatchlistCard';
import {
  columnsFor,
  filterByTab,
  itemCountLabel,
  kindOf,
  playTargetFor,
  removalTarget,
  sortItems,
  titleOf,
  WATCHLIST_SORTS,
  WATCHLIST_TABS,
  type WatchlistSort,
  type WatchlistTab,
} from './list';

/**
 * My Watchlist — the viewer's saved titles, and the screen for managing them.
 *
 * Built from Rio's mockup of 2026-09-09. Three of its elements had no data
 * behind them and all three were put to him rather than faked; his answers are
 * recorded in the worklog and are why this screen draws portrait posters, a
 * meta line of only year and runtime, and the app's existing tab bar.
 *
 * **There is no back arrow.** The mockup has one, but Watchlist is a tab root
 * and the profile menu's Watchlist row navigates to the *tab*, so nothing ever
 * pushes this screen. An arrow with nothing behind it is a dead control.
 *
 * Not paginated, because `GET /watchlist` is not — the endpoint returns the
 * whole list. Sorting and filtering therefore happen here, over data that is
 * all present, rather than as query parameters the server would ignore.
 */
export function WatchlistScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<AppStackParams>>();

  /*
   * The site's header, above this screen's own title bar.
   *
   * Rio, 2026-09-10: from the Watchlist there was no way to reach a profile —
   * the account icon lived on `HomeScreen` alone, so three of the four tabs
   * were dead ends. Two bars stacked is what the website does too: its header
   * is on every page and the page keeps its own heading underneath.
   */
  const appHeader = (
    <AppHeader />
  );
  const queryClient = useQueryClient();
  const insets = useSafeAreaInsets();

  const [tab, setTab] = useState<WatchlistTab>('movies');
  const [sort, setSort] = useState<WatchlistSort>('recent');
  const [editing, setEditing] = useState(false);
  /** Selected rows, by the same key the list is keyed on. */
  const [selected, setSelected] = useState<ReadonlySet<string>>(new Set());
  const [sortOpen, setSortOpen] = useState(false);

  const { data, isPending, isError, error, refetch, isFetching } = useQuery({
    queryKey: ['watchlist'],
    queryFn: () => api.watchlist(),
  });

  const all = useMemo(() => data?.items ?? [], [data]);
  const visible = useMemo(() => sortItems(filterByTab(all, tab), sort), [all, tab, sort]);

  const leaveEditing = useCallback(() => {
    setEditing(false);
    setSelected(new Set());
  }, []);

  /**
   * Removal, one request per title.
   *
   * `DELETE /watchlist/{type}/{id}` is idempotent by design — the controller
   * says so — so a retry after a dropped connection cannot remove the wrong
   * thing, and a failure part-way through leaves the list in a state the
   * refetch below simply reports rather than one nobody can reason about.
   */
  const remove = useMutation({
    mutationFn: async (items: readonly WatchlistItemCard[]) => {
      for (const item of items) {
        const target = removalTarget(item);
        if (target) await api.removeFromWatchlist(target.type, target.id);
      }
    },
    // Refetch rather than splice the cache: the server is the list, and the
    // home screen's own watchlist state reads the same key.
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['watchlist'] }),
    onSuccess: leaveEditing,
  });

  /**
   * Play.
   *
   * **The server already decided what that means.** `WatchlistPlayResolver`
   * resolves a movie to itself and a series to the most recent *unfinished*
   * episode, falling back to the first — the same rule
   * `/watchlist/series/{slug}` follows on the website, from the same service.
   * An earlier cut of this screen fetched the series detail here and picked
   * episode one, which was both an extra request and a different answer from
   * the web app for the same card.
   */
  const play = useCallback(
    (item: WatchlistItemCard) => {
      const target = playTargetFor(item);
      if (target === null) return;

      const slug = (item as { slug?: string }).slug;

      navigation.navigate('Watch', {
        type: target.type,
        id: target.id,
        title: titleOf(item),
        // Lets the player find the next episode, when we know the series.
        ...(target.type === 'episode' && slug !== undefined ? { seriesSlug: slug } : {}),
      });
    },
    [navigation],
  );

  /**
   * Pressing a card.
   *
   * **It plays, and that is the website's own wiring rather than a choice made
   * here.** `profile-hub/watchlist.blade.php` gives every card a `cardPath` of
   * `frontend.watchlist_play` for a movie and `frontend.watchlist_series_play`
   * for a series, and `card-style.blade.php` wraps the whole poster in a link
   * to it. Both routes end in a player. So on the site's own watchlist a
   * press already means play, and the app does the same rather than diverting
   * to a details page the web card does not link to.
   *
   * When there is nothing to play the site redirects to the detail page with a
   * note — an unreleased title, a series with no episodes — and
   * `WatchlistPlayResolver` returns null for exactly those. The app opens the
   * detail screen in that case, which is where the release date lives.
   */
  const openCard = useCallback(
    (item: WatchlistItemCard) => {
      if (playTargetFor(item) !== null) {
        play(item);
        return;
      }

      const slug = (item as { slug?: string }).slug;
      const kind = kindOf(item);

      // An episode has no page of its own in this app and its card does not
      // carry the series slug, so there is nowhere further to send it.
      if (slug === undefined || (kind !== 'movie' && kind !== 'series')) return;

      navigation.navigate('Title', { type: kind, slug, title: titleOf(item) });
    },
    [navigation, play],
  );

  if (isPending) return <Loading label="Loading your watchlist" />;

  if (isError) {
    return (
      <SafeAreaView style={styles.screen} edges={[]}>
        {appHeader}
        <Header editing={false} canEdit={false} onToggleEdit={() => undefined} />
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load your watchlist.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </SafeAreaView>
    );
  }

  const selectedItems = visible.filter((item, index) => selected.has(cardKey(item, index)));

  return (
    <SafeAreaView style={styles.screen} edges={[]}>
      {appHeader}
      <Header
        editing={editing}
        canEdit={all.length > 0}
        onToggleEdit={() => (editing ? leaveEditing() : setEditing(true))}
      />

      <Grid
        items={visible}
        editing={editing}
        selected={selected}
        refreshing={isFetching && !isPending}
        onRefresh={() => void refetch()}
        onPressItem={openCard}
        onToggleSelect={(key) =>
          setSelected((current) => {
            const next = new Set(current);
            if (next.has(key)) next.delete(key);
            else next.add(key);
            return next;
          })
        }
        header={
          <View>
            <Segmented
              tab={tab}
              onChange={(next) => {
                setTab(next);
                // Selections are made per card, and the cards change with the
                // tab. Carrying them across would mean pressing Remove and
                // deleting something no longer on screen.
                setSelected(new Set());
              }}
            />

            <View style={styles.countRow}>
              <Text style={styles.count}>{itemCountLabel(visible.length)}</Text>
              <SortButton
                sort={sort}
                onPress={() => setSortOpen(true)}
              />
            </View>
          </View>
        }
        emptyTitle={emptyTitleFor(tab, all.length)}
        emptyDetail={emptyDetailFor(all.length)}
      />

      {editing ? (
        <ActionBar
          count={selectedItems.length}
          busy={remove.isPending}
          bottomInset={insets.bottom}
          onCancel={leaveEditing}
          onRemove={() => remove.mutate(selectedItems)}
        />
      ) : null}

      <SortSheet
        visible={sortOpen}
        sort={sort}
        onPick={(next) => {
          setSort(next);
          setSortOpen(false);
        }}
        onClose={() => setSortOpen(false)}
      />

    </SafeAreaView>
  );
}

/* ── the pieces ──────────────────────────────────────────────────────── */

function Header({
  editing,
  canEdit,
  onToggleEdit,
}: {
  editing: boolean;
  canEdit: boolean;
  onToggleEdit: () => void;
}) {
  return (
    <View style={styles.header}>
      <Text accessibilityRole="header" style={styles.headerTitle}>
        My Watchlist
      </Text>

      {/*
        Hidden rather than disabled on an empty list: there is nothing to edit,
        and a greyed control still invites the tap that does nothing.
      */}
      {canEdit ? (
        <Focusable
          accessibilityRole="button"
          accessibilityLabel={editing ? 'Done editing' : 'Edit watchlist'}
          accessibilityState={{ selected: editing }}
          ringRadius={t.editRadius}
          onPress={onToggleEdit}
          style={[styles.edit, editing ? styles.editOn : null]}
        >
          <Text style={[styles.editText, editing ? styles.editTextOn : null]}>
            {editing ? 'Done' : 'Edit'}
          </Text>
        </Focusable>
      ) : null}
    </View>
  );
}

function Segmented({
  tab,
  onChange,
}: {
  tab: WatchlistTab;
  onChange: (tab: WatchlistTab) => void;
}) {
  return (
    <View style={styles.segment} accessibilityRole="tablist">
      {WATCHLIST_TABS.map(({ key, label }) => {
        const active = key === tab;
        return (
          <Focusable
            key={key}
            accessibilityRole="tab"
            accessibilityLabel={label}
            accessibilityState={{ selected: active }}
            ringRadius={t.segmentRadius}
            onPress={() => onChange(key)}
            style={styles.segmentItem}
          >
            {active ? (
              <LinearGradient
                colors={[t.segmentActiveFrom, t.segmentActiveTo]}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
                style={StyleSheet.absoluteFill}
              />
            ) : null}
            <Text style={[styles.segmentText, active ? styles.segmentTextOn : null]}>{label}</Text>
          </Focusable>
        );
      })}
    </View>
  );
}

function SortButton({ sort, onPress }: { sort: WatchlistSort; onPress: () => void }) {
  const label = WATCHLIST_SORTS.find((s) => s.key === sort)?.label ?? 'Sort';

  return (
    <Focusable
      accessibilityRole="button"
      accessibilityLabel={`Sort by ${label}`}
      accessibilityHint="Changes the order of your list"
      ringRadius={t.sortRadius}
      onPress={onPress}
      style={styles.sort}
    >
      <ArrowsDownUp size={t.sortIconSize} color={t.sortText} weight="bold" />
      <Text style={styles.sortText}>{label}</Text>
      <CaretDown size={12} color={t.sortText} weight="bold" />
    </Focusable>
  );
}

function Grid({
  items,
  editing,
  selected,
  refreshing,
  onRefresh,
  onPressItem,
  onToggleSelect,
  header,
  emptyTitle,
  emptyDetail,
}: {
  items: readonly WatchlistItemCard[];
  editing: boolean;
  selected: ReadonlySet<string>;
  refreshing: boolean;
  onRefresh: () => void;
  onPressItem: (item: WatchlistItemCard) => void;
  onToggleSelect: (key: string) => void;
  header: React.ReactElement;
  emptyTitle: string;
  emptyDetail?: string | undefined;
}) {
  /*
   * `PosterGrid`'s arithmetic, to the number.
   *
   * Rio, 2026-09-09, on the first cut of this: *"yes they are three cards, but
   * you have used big spaces, which is nothing related to our webapp cards and
   * design."* He was right and the mistake is worth naming. The card had been
   * pinned to the RAIL's poster width — the 3.5-across card — and then laid
   * out in three whole columns, so the row covered about six sevenths of the
   * screen and the slack was spread between the cards as gutters three times
   * the site's. The site has no such gap: its watchlist grid is Bootstrap
   * `row g-3 row-cols-2 row-cols-md-3`, one small gutter, cells that fill the
   * row.
   *
   * So the width comes from the row, not from the rail: the column count is
   * the site's breakpoint ladder (three on a phone), each cell is an equal
   * share of the width, and the gutter between them is `rail.cardGap` — the
   * site's own slide padding, the same value the home rails already spend.
   * The poster lands a little wider than a rail card and that is correct; a
   * grid row fills, a rail peeks.
   *
   * This is `PosterGrid`'s exact model, reproduced rather than reused only
   * because that component draws a title under every card and this screen must
   * not. Same numbers, so a watchlist card and a Movies card measure the same.
   */
  const metrics = useRailMetrics();
  const columns = columnsFor(metrics.width);
  const cardPadding = rail.cardGap / 2;
  const available = Math.max(0, metrics.width - metrics.inset * 2);
  const cellWidth = available / columns;
  const cardWidth = Math.max(1, cellWidth - cardPadding * 2);
  // `card.aspect` is width ÷ height — 0.714, five by seven, read off the
  // rendered site rather than assumed 2:3. Rounded, so a row of posters shares
  // one baseline instead of leaving a one-pixel seam under some of them.
  const cardHeight = Math.round(cardWidth / card.aspect);

  return (
    <View style={styles.grid}>
      {(
        <FlatList
          data={items as WatchlistItemCard[]}
          key={`watchlist-${columns}`}
          numColumns={columns}
          keyExtractor={cardKey}
          // No columnWrapperStyle: the gutter lives on the cells, so a short
          // last row keeps its cards the same width as a full one. A row-level
          // `justifyContent` would have re-spread the final row's one card.
          columnWrapperStyle={undefined}
          contentContainerStyle={[
            styles.gridContent,
            { paddingHorizontal: metrics.inset - cardPadding },
          ]}
          ListHeaderComponent={header}
          ListEmptyComponent={<EmptyState title={emptyTitle} {...(emptyDetail === undefined ? {} : { detail: emptyDetail })} />}
          showsVerticalScrollIndicator={false}
          refreshing={refreshing}
          onRefresh={onRefresh}
          renderItem={({ item, index }) => {
            const key = cardKey(item, index);
            return (
              <View
                style={{
                  width: cellWidth,
                  paddingHorizontal: cardPadding,
                  marginBottom: rail.cardGap,
                }}
              >
                <WatchlistCard
                  item={item}
                  width={cardWidth}
                  height={cardHeight}
                  editing={editing}
                  selected={selected.has(key)}
                  onPress={() => onPressItem(item)}
                  onToggleSelect={() => onToggleSelect(key)}
                />
              </View>
            );
          }}
        />
      )}
    </View>
  );
}

/**
 * The bar that appears while editing.
 *
 * It sits above the tab bar rather than replacing it, so a viewer can still
 * leave. Remove is disabled with nothing selected — that is a control whose
 * meaning depends on a count, not one that does nothing, so the disabled state
 * is the honest one here.
 */
function ActionBar({
  count,
  busy,
  bottomInset,
  onCancel,
  onRemove,
}: {
  count: number;
  busy: boolean;
  bottomInset: number;
  onCancel: () => void;
  onRemove: () => void;
}) {
  const nothing = count === 0 || busy;

  return (
    <View style={[styles.actionBar, { paddingBottom: Math.max(spacing.md, bottomInset) }]}>
      <Focusable
        accessibilityRole="button"
        accessibilityLabel="Cancel"
        ringRadius={t.editRadius}
        onPress={onCancel}
        style={styles.actionCancel}
      >
        <X size={16} color={colors.text} weight="bold" />
        <Text style={styles.actionCancelText}>Cancel</Text>
      </Focusable>

      <Focusable
        accessibilityRole="button"
        accessibilityLabel={count === 1 ? 'Remove 1 title' : `Remove ${count} titles`}
        accessibilityState={{ disabled: nothing, busy }}
        disabled={nothing}
        ringRadius={t.editRadius}
        onPress={onRemove}
        style={[styles.actionRemove, nothing ? styles.actionRemoveOff : null]}
      >
        <Trash size={16} color={colors.onPrimary} weight="bold" />
        <Text style={styles.actionRemoveText}>
          {busy ? 'Removing…' : count === 0 ? 'Remove' : `Remove ${count}`}
        </Text>
      </Focusable>
    </View>
  );
}

function SortSheet({
  visible,
  sort,
  onPick,
  onClose,
}: {
  visible: boolean;
  sort: WatchlistSort;
  onPick: (sort: WatchlistSort) => void;
  onClose: () => void;
}) {
  return (
    <Sheet visible={visible} onClose={onClose} title="Sort by">
      {WATCHLIST_SORTS.map(({ key, label }) => (
        <Focusable
          key={key}
          accessibilityRole="menuitem"
          accessibilityLabel={label}
          accessibilityState={{ selected: key === sort }}
          ringRadius={12}
          onPress={() => onPick(key)}
          style={styles.sheetRow}
        >
          <Text style={styles.sheetRowText}>{label}</Text>
          {key === sort ? <Check size={18} color={t.selectBg} weight="bold" /> : null}
        </Focusable>
      ))}
    </Sheet>
  );
}

/**
 * The bottom sheet the sort control opens.
 *
 * `Modal` with `onRequestClose`, which is what makes the Android back button
 * dismiss the sheet rather than the screen behind it — the same reason
 * `CountryPicker` uses it.
 */
function Sheet({
  visible,
  onClose,
  title,
  children,
}: {
  visible: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
}) {
  const insets = useSafeAreaInsets();

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Close"
        style={styles.scrim}
        onPress={onClose}
      />
      <View style={[styles.sheet, { paddingBottom: Math.max(spacing.xl, insets.bottom) }]}>
        <View style={styles.sheetHandle} />
        {title === '' ? null : (
          <Text numberOfLines={1} accessibilityRole="header" style={styles.sheetTitle}>
            {title}
          </Text>
        )}
        {children}
      </View>
    </Modal>
  );
}

/* ── empty states, which differ by why the list is empty ─────────────── */

function emptyTitleFor(tab: WatchlistTab, total: number): string {
  if (total === 0) return 'Nothing saved yet';
  return tab === 'movies' ? 'No movies saved' : 'No shows saved';
}

/**
 * One line on a first-run list, nothing on the others.
 *
 * A viewer whose whole watchlist is empty cannot see how to fill it, so five
 * words say where the control is. A viewer looking at an empty Movies tab has
 * the TV Shows tab on screen beside it and needs no sentence about it — that
 * is the difference Rio's copy rule turns on.
 */
function emptyDetailFor(total: number): string | undefined {
  return total === 0 ? 'Save titles with the bookmark.' : undefined;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    paddingBottom: spacing.lg,
  },
  headerTitle: {
    fontFamily: fonts.bold,
    fontSize: t.headerTitleSize,
    color: colors.text,
  },
  edit: {
    height: t.editHeight,
    paddingHorizontal: t.editPadX,
    borderRadius: t.editRadius,
    borderWidth: 1,
    borderColor: t.editBorder,
    alignItems: 'center',
    justifyContent: 'center',
  },
  editOn: { backgroundColor: t.selectBg, borderColor: t.selectBg },
  editText: { fontFamily: fonts.medium, fontSize: 13, color: t.editText },
  editTextOn: { color: colors.onPrimary },

  segment: {
    flexDirection: 'row',
    backgroundColor: t.segmentTrack,
    borderRadius: t.segmentRadius,
    padding: t.segmentPad,
    gap: t.segmentPad,
  },
  segmentItem: {
    flex: 1,
    height: t.segmentHeight - t.segmentPad * 2,
    borderRadius: t.segmentRadius - t.segmentPad,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  segmentText: { fontFamily: fonts.medium, fontSize: 14, color: t.segmentIdleText },
  segmentTextOn: { color: t.segmentActiveText },

  countRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.lg,
    marginBottom: spacing.lg,
  },
  count: { fontFamily: fonts.medium, fontSize: t.countSize, color: colors.text },
  sort: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    height: t.sortHeight,
    paddingHorizontal: spacing.md,
    borderRadius: t.sortRadius,
    backgroundColor: t.sortBg,
    borderWidth: 1,
    borderColor: t.sortBorder,
  },
  sortText: { fontFamily: fonts.medium, fontSize: 13, color: t.sortText },

  grid: { flex: 1 },
  gridContent: { paddingBottom: spacing.xxl },

  actionBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
    backgroundColor: t.actionBarBg,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: t.actionBarBorder,
  },
  actionCancel: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    height: 44,
    paddingHorizontal: spacing.lg,
    borderRadius: t.editRadius,
    borderWidth: 1,
    borderColor: t.actionBarBorder,
  },
  actionCancelText: { fontFamily: fonts.medium, fontSize: 14, color: colors.text },
  actionRemove: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.sm,
    height: 44,
    borderRadius: t.editRadius,
    backgroundColor: t.dangerFill,
  },
  actionRemoveOff: { opacity: 0.45 },
  actionRemoveText: { fontFamily: fonts.medium, fontSize: 14, color: colors.onPrimary },

  scrim: { position: 'absolute', top: 0, right: 0, bottom: 0, left: 0, backgroundColor: t.scrim },
  sheet: {
    marginTop: 'auto',
    backgroundColor: t.sheetBg,
    borderTopLeftRadius: t.sheetRadius,
    borderTopRightRadius: t.sheetRadius,
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.md,
  },
  sheetHandle: {
    alignSelf: 'center',
    width: 38,
    height: 4,
    borderRadius: 2,
    backgroundColor: t.sheetHandle,
    marginBottom: spacing.lg,
  },
  sheetTitle: {
    fontFamily: fonts.medium,
    fontSize: 13,
    color: t.blurbColor,
    marginBottom: spacing.sm,
  },
  sheetRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: spacing.md,
    minHeight: 52,
    paddingHorizontal: spacing.sm,
    borderRadius: 12,
  },
  sheetRowText: { flex: 1, fontFamily: fonts.regular, fontSize: 15, color: colors.text },
  sheetRowDanger: { color: t.danger },
});
