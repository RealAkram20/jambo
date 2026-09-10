import { useCallback, useState } from 'react';
import {
  FlatList,
  Pressable,
  StyleSheet,
  View,
  type NativeScrollEvent,
  type NativeSyntheticEvent,
} from 'react-native';
import { CaretLeft, CaretRight } from 'phosphor-react-native';

import { banner, colors, rankedBanner } from '../theme';

/**
 * One screen-wide slide at a time, and something that says there are more.
 *
 * All three of the website's home banners are the same swiper with different
 * furniture — the hero and the series slider show pagination dots on a phone,
 * the movies slider shows a pair of circular arrows because its thumbnail
 * column computes to `0x0` below `lg`. That is the only difference between
 * them, so it is a prop rather than a third copy of a paging FlatList.
 *
 * **Two deliberate departures from the site, inherited from the hero and kept
 * for the same reasons.**
 *
 * 1. **No auto-rotation.** The site runs a 7-second timer on all three
 *    (`data-jambo-rotate`). A carousel that moves on its own takes the thing a
 *    viewer is reading away from them, and on a remote it moves the focus
 *    target out from under the d-pad.
 * 2. **No thumbnail rail.** It is `display: none` at phone width on the site
 *    too, so this is not really a departure.
 */
export function BannerPager<T>({
  items,
  width,
  indicator,
  dynamicDots = false,
  keyExtractor,
  renderItem,
}: {
  items: readonly T[];
  width: number;
  /** What tells the viewer there are more slides. The site chooses per banner. */
  indicator: 'dots' | 'arrows';
  /**
   * Swiper's `dynamicBullets`, where the bullets shrink with their distance
   * from the active one.
   *
   * A flag rather than a constant because the site is not consistent about
   * it, and both renderings were measured on the same page: the tab slider
   * sets it (bullets read 10, 6.6 and 3.3 wide), the hero does not (every
   * bullet reads 10, the inactive ones at half opacity). Guessing one for
   * both would have been wrong somewhere.
   */
  dynamicDots?: boolean;
  keyExtractor: (item: T, index: number) => string;
  renderItem: (item: T) => React.ReactNode;
}) {
  const [index, setIndex] = useState(0);
  const [list, setList] = useState<FlatList<T> | null>(null);

  const getItemLayout = useCallback(
    (_: ArrayLike<T> | null | undefined, i: number) => ({
      length: width,
      offset: width * i,
      index: i,
    }),
    [width],
  );

  const onMomentumEnd = useCallback(
    (event: NativeSyntheticEvent<NativeScrollEvent>) => {
      setIndex(Math.round(event.nativeEvent.contentOffset.x / Math.max(1, width)));
    },
    [width],
  );

  /*
   * Move by one, and clamp rather than wrap.
   *
   * The site's swiper has `loop: false` on both of these, so the first slide's
   * "previous" does nothing there either. Clamping also keeps the arrow's own
   * disabled state honest — a control that looks live and does nothing is
   * worse than one that is visibly at the end.
   */
  const go = useCallback(
    (delta: number) => {
      const next = Math.min(Math.max(index + delta, 0), items.length - 1);
      if (next === index) return;

      setIndex(next);
      list?.scrollToIndex({ index: next, animated: true });
    },
    [index, items.length, list],
  );

  if (items.length === 0) return null;

  return (
    <View style={styles.block}>
      <FlatList
        ref={setList}
        data={items as T[]}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        keyExtractor={keyExtractor}
        getItemLayout={getItemLayout}
        onMomentumScrollEnd={onMomentumEnd}
        renderItem={({ item }) => <View style={{ width }}>{renderItem(item)}</View>}
      />

      {indicator === 'arrows' ? (
        <Arrows index={index} count={items.length} onGo={go} />
      ) : (
        <Dots index={index} count={items.length} dynamic={dynamicDots} />
      )}
    </View>
  );
}

/**
 * The movies banner's navigation: two 24pt circles, 4pt in from each edge,
 * centred on the slide.
 *
 * They are drawn at the site's size and given a hit slop that takes the touch
 * target to the platform minimum. Shrinking the circle to fit a finger would
 * have changed the design; leaving a 24pt tap target would have shipped a
 * control most thumbs miss. The slop costs nothing visually and is invisible
 * to a mouse or a remote.
 */
function Arrows({
  index,
  count,
  onGo,
}: {
  index: number;
  count: number;
  onGo: (delta: number) => void;
}) {
  const slop = Math.round((44 - rankedBanner.movies.arrowSize) / 2);

  return (
    <View style={styles.arrows} pointerEvents="box-none">
      <Arrow
        direction="prev"
        disabled={index === 0}
        slop={slop}
        onPress={() => onGo(-1)}
      />
      <Arrow
        direction="next"
        disabled={index >= count - 1}
        slop={slop}
        onPress={() => onGo(1)}
      />
    </View>
  );
}

function Arrow({
  direction,
  disabled,
  slop,
  onPress,
}: {
  direction: 'prev' | 'next';
  disabled: boolean;
  slop: number;
  onPress: () => void;
}) {
  const Glyph = direction === 'prev' ? CaretLeft : CaretRight;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={direction === 'prev' ? 'Previous slide' : 'Next slide'}
      accessibilityState={{ disabled }}
      disabled={disabled}
      hitSlop={slop}
      onPress={onPress}
      style={[styles.arrow, disabled && styles.arrowDisabled]}
    >
      <Glyph size={rankedBanner.movies.arrowGlyph} color={colors.onPrimary} />
    </Pressable>
  );
}

/**
 * The pagination strip.
 *
 * Position, not decoration: with one screen-wide slide and no arrows, this is
 * the only thing that says there are more. Hidden from screen readers because
 * the list already announces its own position, and a row of unlabelled dots
 * would only repeat it as noise.
 */
function Dots({ index, count, dynamic }: { index: number; count: number; dynamic: boolean }) {
  return (
    <View
      style={styles.dots}
      pointerEvents="none"
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
    >
      {Array.from({ length: count }, (_, i) => (
        <View
          key={i}
          style={[
            styles.dot,
            dynamic && { transform: [{ scale: dotScale(Math.abs(i - index)) }] },
            i !== index && { opacity: banner.dotInactiveOpacity },
          ]}
        />
      ))}
    </View>
  );
}

/** Swiper shrinks a dynamic bullet by its distance from the active one. */
function dotScale(distance: number): number {
  if (distance === 0) return 1;

  return distance === 1 ? rankedBanner.series.dotScaleNear : rankedBanner.series.dotScaleFar;
}

const styles = StyleSheet.create({
  block: { position: 'relative' },

  arrows: {
    position: 'absolute',
    top: 0,
    bottom: 0,
    left: rankedBanner.movies.arrowInset,
    right: rankedBanner.movies.arrowInset,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  arrow: {
    width: rankedBanner.movies.arrowSize,
    height: rankedBanner.movies.arrowSize,
    borderRadius: rankedBanner.movies.arrowSize / 2,
    borderWidth: rankedBanner.movies.arrowBorder,
    borderColor: colors.onPrimary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  // Swiper fades a disabled arrow rather than removing it, so the control
  // stays where the thumb learned it was.
  arrowDisabled: { opacity: rankedBanner.series.dotInactiveOpacity },

  dots: {
    height: banner.dotStripHeight,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: banner.dotGap * 2,
  },
  dot: {
    width: banner.dotSize,
    height: banner.dotSize,
    borderRadius: banner.dotSize / 2,
    backgroundColor: colors.primary,
  },
});
