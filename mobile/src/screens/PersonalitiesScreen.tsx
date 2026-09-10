import { FlatList, StyleSheet, View } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import type { PersonCard as PersonCardData } from '../api/catalogue';
import { colors, personTile, spacing } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { EmptyState, ErrorState, Loading, Spinner } from '../ui/components';
import { PersonCard } from '../ui/rails/TaxonomyCards';
import type { AppScreenProps } from '../navigation/types';

/**
 * Everyone with published work, which is the Personality rail's "View all".
 *
 * The website's `/all-personality` is a grid of `cards/cast`, and at 390pt its
 * `row-cols-1` resolves to a single full-width column: the card 358 wide on
 * the same 1/1.3 portrait and the same 8pt corner the rail's card has, with
 * the name at 16px and the role line under it. So this is one column of
 * `PersonCard` in its `index` variant at the content width — the site's page
 * rather than a phone-shaped reinterpretation of it. Same reasoning, and the
 * same shape, as `GenresScreen`.
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
export function PersonalitiesScreen({ route, navigation }: AppScreenProps<'Personalities'>) {
  const title = route.params?.title ?? 'Personalities';
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery({
      queryKey: ['personalities'],
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
          title="No personalities yet"
          detail="People appear here once their titles are published."
        />
      </View>
    );
  }

  const width = metrics.width - metrics.inset * 2;

  return (
    <View style={styles.screen}>
      <FlatList
        data={people}
        keyExtractor={(item: PersonCardData, index) => item.slug ?? String(index)}
        contentContainerStyle={[styles.list, { paddingHorizontal: metrics.inset }]}
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
          <View style={styles.row}>
            <PersonCard
              item={item}
              width={width}
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
  /* The site's grid gutter, which is the same half-gap the rail puts between
     two cards — applied vertically here because the column is one wide. */
  row: { marginBottom: personTile.gap * 2 },
  footer: { paddingVertical: spacing.lg },
});
