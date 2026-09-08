import { useCallback, useRef, useState } from 'react';
import {
  FlatList,
  StyleSheet,
  Text,
  View,
  type NativeScrollEvent,
  type NativeSyntheticEvent,
} from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';

import { colors, fonts, hero, spacing, typography } from '../theme';
import { imageUrl } from '../media';
import { Focusable } from './Focusable';
import type { PosterItem } from './PosterCard';

/**
 * The home banner: the admin's curated picks, one at a time.
 *
 * The website runs this as an auto-rotating swiper. The app does not
 * auto-rotate, and that is a deliberate departure with a reason: a carousel
 * that moves on its own takes the thing a viewer is reading away from them,
 * and on a remote it moves the focus target out from under the d-pad. The
 * website's own CHANGELOG (1.8.21) records the rotation as something added for
 * a desktop banner. Swiping and the d-pad both work here; nothing moves unless
 * the viewer moves it.
 *
 * `hero` items are `MovieCard`s and `SeriesCard`s, so the only artwork they
 * carry is `poster_url` — a portrait poster. A 21:9 backdrop exists on the
 * *detail* resources, not on the card, so this draws the poster into a shorter
 * box and lets the gradient carry the text. Asking the server for backdrops on
 * the hero would be a better banner and is a PHP change; it is named in the
 * worklog rather than done here.
 */
export function Hero({
  items,
  width,
  onPress,
}: {
  items: readonly PosterItem[];
  width: number;
  onPress: (item: PosterItem) => void;
}) {
  const [index, setIndex] = useState(0);
  const listRef = useRef<FlatList<PosterItem>>(null);

  const height = heroHeight(width);

  const getItemLayout = useCallback(
    (_: ArrayLike<PosterItem> | null | undefined, i: number) => ({
      length: width,
      offset: width * i,
      index: i,
    }),
    [width],
  );

  const onMomentumEnd = useCallback(
    (event: NativeSyntheticEvent<NativeScrollEvent>) => {
      const next = Math.round(event.nativeEvent.contentOffset.x / Math.max(1, width));
      setIndex(next);
    },
    [width],
  );

  if (items.length === 0) return null;

  return (
    <View style={styles.block}>
      <FlatList
        ref={listRef}
        data={items as PosterItem[]}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        keyExtractor={(item, i) => `${item.type ?? ''}${item.id ?? i}`}
        getItemLayout={getItemLayout}
        onMomentumScrollEnd={onMomentumEnd}
        renderItem={({ item }) => (
          <HeroSlide item={item} width={width} height={height} onPress={() => onPress(item)} />
        )}
      />

      {/*
        Position, not decoration: with one screen-wide slide and no arrows,
        the dots are the only thing that says there are five more. Hidden from
        screen readers because the list already announces its own position.
      */}
      <View
        style={styles.dots}
        pointerEvents="none"
        accessibilityElementsHidden
        importantForAccessibility="no-hide-descendants"
      >
        {items.map((item, i) => (
          <View
            key={`${item.type ?? ''}${item.id ?? i}`}
            style={[styles.dot, i === index && styles.dotActive]}
          />
        ))}
      </View>
    </View>
  );
}

function HeroSlide({
  item,
  width,
  height,
  onPress,
}: {
  item: PosterItem;
  width: number;
  height: number;
  onPress: () => void;
}) {
  const title = item.title ?? 'Untitled';

  return (
    <Focusable
      accessibilityLabel={item.year ? `${title}, ${item.year}` : title}
      accessibilityHint="Opens details"
      ringRadius={0}
      onPress={onPress}
      style={{ width, height }}
    >
      <ExpoImage
        source={imageUrl(item.poster_url, width)}
        style={StyleSheet.absoluteFill}
        contentFit="cover"
        // Posters put the title and the faces in the upper half, and this box
        // is much shorter than a poster — centring would crop to a midriff.
        contentPosition="top"
        recyclingKey={item.slug ?? String(item.id ?? title)}
        transition={180}
        cachePolicy="memory-disk"
        accessible={false}
      />

      {/*
        The scrim is what makes the headline legible over arbitrary artwork,
        and it runs to the site's black so the banner reads as continuous with
        the rails below rather than as a photo with a hard edge.
      */}
      <LinearGradient
        colors={['rgba(0, 0, 0, 0.15)', 'rgba(0, 0, 0, 0.55)', colors.background]}
        locations={[0, 0.55, 1]}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />

      <View style={styles.caption} pointerEvents="none">
        <Text numberOfLines={2} style={styles.title}>
          {title}
        </Text>
        <HeroMeta item={item} />
      </View>
    </Focusable>
  );
}

/**
 * The meta line under the headline.
 *
 * ⚠️ `rating` is **not** a star rating. The database holds a content
 * certification — `G`, `PG`, `PG-13`, `R`, `NC-17` — and the site's own hero
 * renders it as a certification badge (`hero-banner.blade.php` falls back to
 * the literal `'PG'`). The five-star display on the website comes from a
 * different source entirely, `ratings()->avg('stars')`, which no catalogue
 * endpoint exposes. `docs/api/openapi.yaml` typed the field as a `number`,
 * which would have produced "NC-17 stars"; the spec has been corrected.
 *
 * Nothing is rendered for a field the server did not send. A dash where a year
 * should be is honest; a zero is not.
 */
function HeroMeta({ item }: { item: PosterItem }) {
  const parts: string[] = [];
  if (typeof item.year === 'number') parts.push(String(item.year));

  const certification = (item as { rating?: unknown }).rating;
  if (typeof certification === 'string' && certification !== '') parts.push(certification);

  if (parts.length === 0) return null;

  return <Text style={styles.meta}>{parts.join('  ·  ')}</Text>;
}

/**
 * How tall the banner is.
 *
 * Sized off the width rather than fixed, for the same reason the rails are:
 * a fixed height is a phone height, and on a rotated phone it would be most of
 * the screen. 4:3 in portrait keeps the artwork readable; in landscape the
 * width is large and the height must not follow it, so it is capped against
 * the width instead of growing with it.
 */
function heroHeight(width: number): number {
  return Math.round(Math.min(width * 0.75, 420));
}

const styles = StyleSheet.create({
  block: { position: 'relative' },

  caption: {
    position: 'absolute',
    left: spacing.lg,
    right: spacing.lg,
    bottom: spacing.xl,
  },
  title: {
    fontFamily: fonts.black,
    fontSize: hero.titleSize,
    fontWeight: String(hero.titleWeight) as '800',
    letterSpacing: hero.tracking,
    color: colors.onPrimary,
  },
  meta: {
    ...typography.caption,
    color: colors.textMuted,
    marginTop: spacing.xs,
  },

  dots: {
    position: 'absolute',
    bottom: spacing.sm,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 6,
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.border,
  },
  dotActive: { backgroundColor: colors.primary, width: 18 },
});
