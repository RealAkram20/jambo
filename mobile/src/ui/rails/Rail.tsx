import { useCallback, useRef } from 'react';
import { FlatList, StyleSheet, Text, View, type ListRenderItem } from 'react-native';

import { rail, typography } from '../theme';
import { Focusable } from './Focusable';

/**
 * A horizontal shelf: a heading, an optional "View all", and a row of cards.
 *
 * **Structured for a remote, used on a phone.** Phase 4 adds
 * `TVFocusGuideView` and focus memory — returning to the card the viewer was
 * on when they last left this rail — and the brief is explicit that it must be
 * an added prop rather than a restructure. Three things here make that true:
 *
 *  - the list holds a `ref`, so `scrollToIndex` is available without threading
 *    one through every caller later;
 *  - `getItemLayout` is supplied, which is what lets `scrollToIndex` jump to a
 *    card the list has never rendered — exactly the case when focus is
 *    restored to card 9 of a rail that has not scrolled;
 *  - every card in the kit reports its own focus through
 *    `Focusable.onFocusChange`, so the index a rail would restore is already
 *    observable and needs no new plumbing in the cards.
 *
 * Nothing here depends on touch. The heading's "View all" and the cards are
 * all `Focusable`, and scrolling follows focus rather than a drag.
 */
export type RailProps<T> = {
  title: string;
  data: readonly T[];
  renderItem: ListRenderItem<T>;
  keyExtractor: (item: T, index: number) => string;
  /** Card width plus gutter, so the list can jump by index. */
  itemWidth: number;
  inset: number;
  onSeeAll?: (() => void) | undefined;
};

export function Rail<T>({
  title,
  data,
  renderItem,
  keyExtractor,
  itemWidth,
  inset,
  onSeeAll,
}: RailProps<T>) {
  const listRef = useRef<FlatList<T>>(null);

  const getItemLayout = useCallback(
    (_: ArrayLike<T> | null | undefined, index: number) => ({
      length: itemWidth,
      offset: itemWidth * index,
      index,
    }),
    [itemWidth],
  );

  const onScrollToIndexFailed = useCallback(() => {
    // Only reachable if a rail shrinks under a restored index. Land at the
    // start rather than throwing, which is what the default does.
    listRef.current?.scrollToOffset({ offset: 0, animated: false });
  }, []);

  /*
   * A rail with nothing in it renders nothing at all — no heading, no empty
   * state. The API already drops empty rails ("a heading over nothing is worse
   * than no heading" is the server's own comment on the endpoint), so this
   * only fires on a client-side filter; the reasoning is the same either way.
   *
   * Every hook above runs first. An early return before them would make the
   * hook order depend on whether the rail had data, which React forbids and
   * which breaks the moment a rail arrives empty and then fills.
   */
  if (data.length === 0) return null;

  return (
    <View style={styles.rail}>
      <View style={[styles.header, { paddingHorizontal: inset }]}>
        <Text accessibilityRole="header" numberOfLines={1} style={styles.title}>
          {title}
        </Text>
        {onSeeAll ? (
          <Focusable
            accessibilityLabel={`View all ${title}`}
            ringRadius={6}
            onPress={onSeeAll}
            style={styles.seeAllHit}
          >
            <Text style={styles.seeAll}>View all</Text>
          </Focusable>
        ) : null}
      </View>

      <FlatList
        ref={listRef}
        data={data as T[]}
        renderItem={renderItem}
        keyExtractor={keyExtractor}
        horizontal
        showsHorizontalScrollIndicator={false}
        // The cards carry half the gutter each, so the row's own padding is
        // the screen inset less that half — otherwise the first poster sits
        // 7dp further in than the heading above it.
        contentContainerStyle={{ paddingHorizontal: inset - rail.cardGap / 2 }}
        getItemLayout={getItemLayout}
        onScrollToIndexFailed={onScrollToIndexFailed}
        // A rail is at most a few dozen cards. The cost of getting these wrong
        // is a blank gap where a poster should be during a fast flick.
        initialNumToRender={6}
        windowSize={5}
        removeClippedSubviews
      />
    </View>
  );
}

const styles = StyleSheet.create({
  // The gap below a rail is the site's own: 30 at a phone viewport, measured
  // from the vendor swiper's margin on the home page — NOT `--jambo-rail-gap`,
  // which is the tighter rhythm the detail pages use.
  rail: { marginBottom: rail.gap },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    // 12 on a phone. The site writes it as `mb-2 pb-1` and only widens to 24
    // at the md breakpoint, so the 24 in the plan's token table was a desktop
    // reading of a phone value.
    marginBottom: rail.headingGap,
    gap: 12,
  },
  title: {
    ...typography.railHeading,
    color: rail.headingColor,
    flexShrink: 1,
  },
  seeAllHit: { paddingVertical: 4, paddingHorizontal: 6 },
  seeAll: {
    ...typography.button,
    fontSize: rail.viewAllSize,
    color: rail.viewAllColor,
  },
});
