import { StyleSheet } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '../api/jambo';
import { colors } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { ErrorState, Loading } from '../ui/components';
import { PosterGrid } from '../ui/rails/PosterGrid';

/**
 * The viewer's watchlist.
 *
 * Not paginated, because the endpoint is not. Adding a `?cursor=` the server
 * ignores would work perfectly until somebody had more than a page of titles,
 * which is the worst moment to find out.
 *
 * The list can contain episodes as well as movies and series. They are shown
 * with the same poster card: an `Episode` carries `still_url` rather than
 * `poster_url`, so the card falls back to no image rather than to somebody
 * else's artwork. Episode-shaped cards on this screen are rare — the website
 * adds shows, not episodes — and giving them their own presentation is work
 * for the screen that can actually play one.
 */
export function WatchlistScreen() {
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['watchlist'],
    queryFn: () => api.watchlist(),
  });

  if (isPending) return <Loading label="Loading your watchlist" />;

  if (isError) {
    return (
      <SafeAreaView style={styles.screen} edges={['top']}>
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

  return (
    <SafeAreaView style={styles.screen} edges={['top']}>
      <PosterGrid
        items={data.items}
        metrics={metrics}
        emptyTitle="Nothing saved yet"
        // Says what the thing is and what to do next, rather than "No records
        // found" — which tells a viewer nothing about how to change it.
        emptyDetail="Titles you save are kept here so you can find them again. Look for the bookmark on any film or series."
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
});
