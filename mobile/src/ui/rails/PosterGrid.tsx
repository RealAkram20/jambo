import React, { useCallback } from 'react';
import { FlatList, StyleSheet, Text, View } from 'react-native';

import type { TitleCard } from '../../api/catalogue';
import { cardKey } from '../../api/catalogue';
import { colors, spacing, typography } from '../theme';
import type { RailMetrics } from '../metrics';
import { EmptyState, Spinner } from '../components';
import { PosterCard } from './PosterCard';

/**
 * A grid of posters, for the archive screens: Movies, Series, Watchlist, and
 * the taxonomy pages.
 *
 * The column count is the rail's cards-per-view rounded down, so a grid and a
 * rail on the same device show posters of the same size. Two components that
 * each decided their own poster width would drift by a few pixels and the
 * difference would be visible on any screen that showed both.
 *
 * `onEndReached` drives the cursor pagination. The website calls this "Load
 * More" and renders a button; an infinite list is the phone equivalent and the
 * plan's §6.2 says so explicitly.
 */
export function PosterGrid({
  items,
  metrics,
  onPressItem,
  onEndReached,
  loadingMore = false,
  header,
  emptyTitle,
  emptyDetail,
}: {
  items: readonly TitleCard[];
  metrics: RailMetrics;
  onPressItem?: ((item: TitleCard) => void) | undefined;
  onEndReached?: (() => void) | undefined;
  loadingMore?: boolean;
  header?: React.ReactElement | undefined;
  emptyTitle: string;
  emptyDetail: string;
}) {
  /*
   * Whole columns only. A grid has no peek — the half card in a rail is what
   * says "this scrolls sideways", and a half card at the right edge of a grid
   * just looks like a clipped poster.
   */
  const columns = Math.max(2, Math.floor(metrics.cardsPerView));
  const available = metrics.width - metrics.inset * 2;
  const cellWidth = available / columns;
  const posterWidth = cellWidth - metrics.cardPadding * 2;
  const posterHeight = Math.round(posterWidth / (metrics.posterWidth / metrics.posterHeight));

  const renderItem = useCallback(
    ({ item }: { item: TitleCard }) => (
      <View style={{ width: cellWidth, paddingHorizontal: metrics.cardPadding }}>
        <PosterCard
          item={item}
          width={posterWidth}
          height={posterHeight}
          onPress={onPressItem ? () => onPressItem(item) : undefined}
        />
        {/*
          A grid does show the title, unlike a rail. A rail is scanned as a
          shelf and its cards are captioned by the heading above them; a grid
          is a catalogue, and an unlabelled catalogue of 200 posters is a
          puzzle. The site's own archive pages caption their cards the same way
          once there is room for it.
        */}
        <Text numberOfLines={2} style={styles.title}>
          {item.title ?? 'Untitled'}
        </Text>
      </View>
    ),
    [cellWidth, metrics.cardPadding, onPressItem, posterHeight, posterWidth],
  );

  return (
    <FlatList
      data={items as TitleCard[]}
      key={`grid-${columns}`}
      numColumns={columns}
      renderItem={renderItem}
      keyExtractor={cardKey}
      contentContainerStyle={[
        styles.content,
        { paddingHorizontal: metrics.inset - metrics.cardPadding },
      ]}
      ListHeaderComponent={header ?? null}
      ListEmptyComponent={<EmptyState title={emptyTitle} detail={emptyDetail} />}
      ListFooterComponent={
        loadingMore ? (
          <View style={styles.footer}>
            <Spinner />
          </View>
        ) : null
      }
      onEndReached={onEndReached}
      // Half a screen ahead: far enough that the next page is usually there
      // before the viewer reaches it, near enough that a fast scroll through a
      // long catalogue does not fetch four pages it never shows.
      onEndReachedThreshold={0.5}
      showsVerticalScrollIndicator={false}
    />
  );
}

const styles = StyleSheet.create({
  content: { paddingBottom: spacing.xxl },
  title: {
    ...typography.caption,
    color: colors.text,
    marginTop: spacing.sm,
    marginBottom: spacing.lg,
  },
  footer: { paddingVertical: spacing.xl, alignItems: 'center' },
});
