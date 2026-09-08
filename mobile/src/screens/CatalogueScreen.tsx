import { useCallback } from 'react';
import { StyleSheet } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '../api/jambo';
import type { MovieList, SeriesList, TitleCard } from '../api/catalogue';
import { colors } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { ErrorState, Loading } from '../ui/components';
import { PosterGrid } from '../ui/rails/PosterGrid';

/**
 * The Movies and Series archives.
 *
 * One component for both, because they are the same screen against a different
 * endpoint. Two near-identical screens is how the second one quietly stops
 * matching the first.
 *
 * Cursor pagination, with the cursor treated as opaque. The endpoint's own
 * comment records that these lists order on `created_at`, which seeded and
 * bulk-imported titles share to the second, and that an offset cursor skipped
 * every tied row — page two of latest-movies came back empty on real data. A
 * client that built its own cursor would reproduce that.
 */
type Page = MovieList | SeriesList;

export function CatalogueScreen({
  kind,
}: {
  kind: 'movies' | 'series';
}) {
  const metrics = useRailMetrics();

  const fetchPage = useCallback(
    ({ pageParam }: { pageParam: string | undefined }): Promise<Page> =>
      kind === 'movies' ? api.movies(pageParam) : api.series(pageParam),
    [kind],
  );

  const { data, isPending, isError, error, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery({
      queryKey: [kind],
      queryFn: fetchPage,
      initialPageParam: undefined as string | undefined,
      // `next_cursor` is null at the end of the list, which is what stops the
      // infinite list asking for a page that does not exist.
      getNextPageParam: (last: Page) => last.next_cursor ?? undefined,
    });

  if (isPending) return <Loading label={kind === 'movies' ? 'Loading movies' : 'Loading series'} />;

  if (isError) {
    return (
      <SafeAreaView style={styles.screen} edges={['top']}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load the catalogue.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </SafeAreaView>
    );
  }

  const items: TitleCard[] = data.pages.flatMap((page): TitleCard[] => page.items ?? []);

  return (
    <SafeAreaView style={styles.screen} edges={['top']}>
      <PosterGrid
        items={items}
        metrics={metrics}
        loadingMore={isFetchingNextPage}
        onEndReached={() => {
          // `hasNextPage` is what makes this safe to fire on every scroll: an
          // exhausted list has no next cursor and asks for nothing.
          if (hasNextPage && !isFetchingNextPage) void fetchNextPage();
        }}
        emptyTitle={kind === 'movies' ? 'No movies yet' : 'No series yet'}
        emptyDetail="Nothing has been published here. Check back soon."
      />
    </SafeAreaView>
  );
}

export function MoviesScreen() {
  return <CatalogueScreen kind="movies" />;
}

export function SeriesScreen() {
  return <CatalogueScreen kind="series" />;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
});
