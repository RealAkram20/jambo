import { FlatList, StyleSheet, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import type { GenreCard as GenreCardData } from '../api/catalogue';
import { colors, genreTile, spacing } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { EmptyState, ErrorState, Loading } from '../ui/components';
import { GenreCard } from '../ui/rails/TaxonomyCards';
import type { AppScreenProps } from '../navigation/types';

/**
 * Every genre, which is the Genres rail's "View all".
 *
 * The website's `/all-genres` is a grid of the same tile the rail draws, and
 * at 390pt its `row-cols-1` resolves to a single full-width column — 358 wide
 * against the rail's 164, the same 5:3, the same 8pt corner. So this is one
 * column of `GenreCard` at the content width, which is the site's page rather
 * than a phone-shaped reinterpretation of it.
 *
 * Not paginated, and that is the endpoint's shape rather than an omission:
 * `GET /genres` returns all of them because there are eight. If that ever
 * becomes hundreds the endpoint gains paging first and this follows it, the
 * same way CollectionScreen pages the way its endpoint pages.
 */
export function GenresScreen({ route, navigation }: AppScreenProps<'Genres'>) {
  const title = route.params?.title ?? 'Genres';
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['genres'],
    queryFn: () => api.genres(),
    // The list changes when an admin adds a genre, which is rarely. An hour
    // matches what the home screen already does for collections.
    staleTime: 60 * 60 * 1000,
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

  if (data.length === 0) {
    return (
      <View style={styles.screen}>
        <EmptyState title="No genres yet" detail="Genres appear here once titles are published." />
      </View>
    );
  }

  const width = metrics.width - metrics.inset * 2;

  return (
    <View style={styles.screen}>
      <FlatList
        data={data}
        keyExtractor={(item: GenreCardData, index) => item.slug ?? String(index)}
        contentContainerStyle={[styles.list, { paddingHorizontal: metrics.inset }]}
        showsVerticalScrollIndicator={false}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <GenreCard
              item={item}
              width={width}
              onPress={
                item.slug === undefined
                  ? undefined
                  : () =>
                      navigation.navigate('Taxonomy', {
                        kind: 'genre',
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
  // The site's grid gutter, which is the same half-gap the rail puts between
  // two tiles — applied vertically here because the column is one wide.
  row: { marginBottom: genreTile.gap * 2 },
});
