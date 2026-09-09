import { FlatList, StyleSheet, Text, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import type { ContinueWatchingCard } from '../api/catalogue';
import { colors, spacing, typography } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { EmptyState, ErrorState, Loading } from '../ui/components';
import { ProgressCard } from '../ui/rails/ProgressCard';
import type { AppScreenProps } from '../navigation/types';

/**
 * Continue Watching, as its own screen.
 *
 * A single column of the same 5:3 cards the home rail draws, at the full
 * content width — this screen exists to show the whole row rather than the
 * first few, so a grid of small cards would be the rail again with extra
 * steps.
 *
 * Not paginated, because the endpoint is not. The row is capped server-side:
 * `WatchHistoryItem` keeps only the most recent N titles, because viewers
 * routinely bail during the end credits and in-progress rows would otherwise
 * pile up forever. Adding a cursor the server ignores would look like it
 * worked right up until somebody had more than a page.
 */
export function ContinueWatchingScreen(_: AppScreenProps<'ContinueWatching'>) {
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['continue-watching'],
    queryFn: () => api.continueWatching(),
  });

  if (isPending) return <Loading label="Loading Continue Watching" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load Continue Watching.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  const width = metrics.width - metrics.inset * 2;
  const height = Math.round(width / (metrics.stillWidth / metrics.stillHeight));

  return (
    <View style={styles.screen}>
      <FlatList
        data={data}
        keyExtractor={(item: ContinueWatchingCard, index) =>
          `${item.resume?.type ?? 'cw'}:${item.resume?.id ?? index}`
        }
        contentContainerStyle={styles.content}
        ListHeaderComponent={
          data.length === 0 ? null : (
            <Text style={styles.notice}>
              Resume arrives with the player in the next update.
            </Text>
          )
        }
        ListEmptyComponent={
          <EmptyState
            title="Nothing in progress"
            detail="Titles you start but do not finish appear here so you can pick them up where you left off."
          />
        }
        renderItem={({ item }) => (
          <View style={styles.row}>
            {/*
              No `onPress`, so the card is not interactive — see ProgressCard.
              Resume needs the player, and the card cannot fall back to the
              title's page because `ContinueWatchingCard` carries an id and no
              slug while `/movies/{slug}` is slug-only (`/movies/1` is a 404,
              checked). A card that is focusable and does nothing is worse than
              one that plainly is not.
            */}
            <ProgressCard item={item} width={width} height={height} />
          </View>
        )}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, flexGrow: 1 },
  row: { marginBottom: spacing.lg },
  notice: { ...typography.caption, color: colors.textMuted, marginBottom: spacing.lg },
});
