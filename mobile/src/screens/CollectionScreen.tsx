import { StyleSheet, View } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import type { TitleCard } from '../api/catalogue';
import { colors } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { ErrorState, Loading } from '../ui/components';
import { PosterGrid } from '../ui/rails/PosterGrid';
import type { AppScreenProps } from '../navigation/types';

/**
 * A whole rail, rather than the ten cards the home screen has room for.
 *
 * This is the website's "View all", which every home rail carries next to its
 * heading. The server answers from `RailArchiveCatalog` — the same class the
 * site's own `/collection/{rail}` page calls — so the archive and the rail
 * cannot list different titles.
 *
 * **Paged by offset, not by a cursor**, because that is what the endpoint
 * offers and the reason is a good one: the pinned rails order with a raw
 * `FIELD()` clause no cursor can be built from, and the plain ones order on
 * `created_at`, which bulk-imported titles share to the second — a cursor on
 * that skipped every tied row and produced an empty page two on real data.
 * The app pages the way the server pages rather than inventing its own.
 */
export function CollectionScreen({ route, navigation }: AppScreenProps<'Collection'>) {
  const { rail, title } = route.params;
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery({
      queryKey: ['collection', rail],
      queryFn: ({ pageParam }: { pageParam: number | undefined }) => api.collection(rail, pageParam),
      initialPageParam: undefined as number | undefined,
      // `next_page` is null at the end, which is what stops the list asking
      // for a page that does not exist.
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

  const items: TitleCard[] = data.pages.flatMap((page) => page.items);

  return (
    <View style={styles.screen}>
      <PosterGrid
        items={items}
        metrics={metrics}
        loadingMore={isFetchingNextPage}
        onEndReached={() => {
          if (hasNextPage && !isFetchingNextPage) void fetchNextPage();
        }}
        onPressItem={(item) => {
          if (item.slug === undefined) return;
          navigation.push('Title', {
            type: item.type === 'series' ? 'series' : 'movie',
            slug: item.slug,
            ...(item.title === undefined ? {} : { title: item.title }),
          });
        }}
        emptyTitle={`Nothing in ${title}`}
        // This shelf is on the home screen, so an empty archive is a state
        // worth explaining rather than a dead end.
        emptyDetail="This shelf is empty right now. It refills as new titles are published."
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
});
