import { FlatList, StyleSheet, View } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import type { PersonCard as PersonCardData } from '../api/catalogue';
import { colors, personTile, spacing } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { EmptyState, ErrorState, Loading, Spinner } from '../ui/components';
import { PersonCard } from '../ui/rails/TaxonomyCards';
import type { AppScreenProps } from '../navigation/types';

/** The site's `row-cols-3` on a phone. Three columns, edge to edge. */
const COLUMNS = 3;

/**
 * Every cast member and personality — the Personality rail's "View all".
 *
 * This is the website's `/cast-list`, and at 390pt its `row-cols-3` resolves to
 * three columns spanning the viewport edge to edge: columns of 129.98 each
 * holding a 97.98 card on the same 1/1.3 portrait and 8pt corner the rail's
 * card has, the name at 16px and the role line under it. So the gutter is 16
 * inside every cell and the grid is deliberately NOT inset like the rest of the
 * app's lists, because the site's is not.
 *
 * **It mirrored `/all-personality` for one commit and that page no longer
 * exists.** Rio, 2026-09-10: *"the /all-personality should be /cast-list so we
 * can remove the /all-personality for casts"*. He was right and the controller
 * showed why — the two actions ran the identical query and differed only in
 * which view they rendered, one card per row against three. `/cast-list` is the
 * survivor because it is the page the site's own sidebar navigates to. The
 * layout here was re-measured rather than carried over, because the two pages
 * showed the same people in different grids.
 *
 * **Paged, unlike the genres index, because the endpoint is.** `GET /cast`
 * returns 40 at a time by OFFSET — its primary order is
 * `(movies_count + shows_count) DESC`, a raw expression no cursor can be built
 * from — so this follows the endpoint's own shape rather than imposing one.
 *
 * **The order is not the website's, and that predates this screen.** The site
 * lists everybody by surname, including people with nothing published. The
 * endpoint lists only those with published work, most-present first, and its
 * docblock defends that as what makes the grid useful. A screen is not the
 * place to re-litigate it; it is named here so the difference is not mistaken
 * for a bug.
 */
export function CastListScreen({ route, navigation }: AppScreenProps<'CastList'>) {
  const title = route.params?.title ?? 'Cast';
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery({
      queryKey: ['cast-list'],
      queryFn: ({ pageParam }: { pageParam: number }) => api.people(pageParam),
      initialPageParam: 1,
      getNextPageParam: (last) => last.nextPage ?? undefined,
    });

  if (isPending) return <Loading label={`Loading ${title}`} />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : `We could not load ${title}.`
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  const people = data.pages.flatMap((page) => page.items);

  if (people.length === 0) {
    return (
      <View style={styles.screen}>
        <EmptyState
          title="No cast yet"
          detail="People appear here once their titles are published."
        />
      </View>
    );
  }

  /*
   * The cell is a third of the FULL width, not of an inset content box, which
   * is what makes this match the site: its grid has no outer gutter and each
   * column carries its own 16 inside.
   */
  const cell = metrics.width / COLUMNS;
  const card = cell - personTile.indexGutter * 2;

  return (
    <View style={styles.screen}>
      <FlatList
        data={people}
        numColumns={COLUMNS}
        keyExtractor={(item: PersonCardData, index) => item.slug ?? String(index)}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        onEndReached={() => {
          if (hasNextPage && !isFetchingNextPage) void fetchNextPage();
        }}
        onEndReachedThreshold={0.5}
        ListFooterComponent={
          isFetchingNextPage ? (
            <View style={styles.footer}>
              <Spinner />
            </View>
          ) : null
        }
        renderItem={({ item }) => (
          <View style={[styles.cell, { width: cell }]}>
            <PersonCard
              item={item}
              width={card}
              variant="index"
              onPress={
                item.slug === undefined
                  ? undefined
                  : () =>
                      navigation.navigate('Taxonomy', {
                        kind: 'cast',
                        slug: item.slug as string,
                        name: item.name ?? '',
                      })
              }
            />
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  list: { paddingTop: spacing.md, paddingBottom: spacing.xxl },
  /* The site's `.col` — the gutter lives inside the cell, not around the
     grid, which is why the list itself has no horizontal padding. */
  cell: {
    paddingHorizontal: personTile.indexGutter,
    marginBottom: personTile.indexGutter,
  },
  footer: { paddingVertical: spacing.lg },
});
