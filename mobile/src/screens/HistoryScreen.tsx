import { FlatList, StyleSheet, Text, View } from 'react-native';
import { useInfiniteQuery } from '@tanstack/react-query';
import { Image as ExpoImage } from 'expo-image';

import { api } from '../api/jambo';
import type { HistoryEntry } from '../api/catalogue';
import { imageUrl } from '../ui/media';
import { card, colors, radius, spacing, typography, watching } from '../ui/theme';
import { EmptyState, ErrorState, Loading, Spinner } from '../ui/components';
import { Focusable } from '../ui/rails/Focusable';
import type { AppScreenProps } from '../navigation/types';

/**
 * Everything watched, newest first.
 *
 * A record rather than a to-do list — unlike Continue Watching it keeps
 * finished titles, which is why it is its own screen and not a "see all" on
 * that rail.
 *
 * A row rather than a poster grid, because the useful thing here is *when* and
 * *how far*, and a grid of posters shows neither.
 *
 * A movie row carries a `slug` and opens its page, which a Continue Watching
 * card cannot. An **episode** row does not: the contract says `item` is
 * `MovieCard | Episode`, and an Episode has `still_url`, no `poster_url` and
 * no slug at all. Those rows are readable and not pressable — see `HistoryRow`.
 */
export function HistoryScreen({ navigation }: AppScreenProps<'History'>) {
  const { data, isPending, isError, error, refetch, fetchNextPage, hasNextPage, isFetchingNextPage } =
    useInfiniteQuery({
      queryKey: ['history'],
      queryFn: ({ pageParam }: { pageParam: string | undefined }) => api.history(pageParam),
      initialPageParam: undefined as string | undefined,
      getNextPageParam: (last) => last.nextCursor ?? undefined,
    });

  if (isPending) return <Loading label="Loading your history" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load your history.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  const entries: HistoryEntry[] = data.pages.flatMap((page) => page.items);

  return (
    <View style={styles.screen}>
      <FlatList
        data={entries}
        keyExtractor={(entry, index) =>
          `${entry.item?.type ?? 'x'}:${entry.item?.id ?? index}:${entry.watched_at ?? index}`
        }
        contentContainerStyle={styles.content}
        ListEmptyComponent={
          <EmptyState
            title="Nothing watched yet"
            detail="Once you start watching, everything you have seen is listed here."
          />
        }
        ListFooterComponent={
          isFetchingNextPage ? (
            <View style={styles.footer}>
              <Spinner />
            </View>
          ) : null
        }
        onEndReached={() => {
          if (hasNextPage && !isFetchingNextPage) void fetchNextPage();
        }}
        onEndReachedThreshold={0.5}
        renderItem={({ item: entry }) => <HistoryRow entry={entry} navigation={navigation} />}
      />
    </View>
  );
}

function HistoryRow({
  entry,
  navigation,
}: {
  entry: HistoryEntry;
  navigation: AppScreenProps<'History'>['navigation'];
}) {
  const item = entry.item;
  const title = item?.title ?? 'Untitled';

  /*
   * A history row can be an **episode**, not only a movie — the contract says
   * `item` is `MovieCard | Episode`, and the two are genuinely different: an
   * Episode carries `still_url` and no `poster_url`, and no `slug` at all.
   *
   * So an episode row shows its still and is not pressable. It cannot open a
   * page: the detail endpoint is slug-only, an episode has no slug, and
   * reaching its series would need a series slug the entry does not carry.
   * Rendering it as a movie would have shown a blank poster and a link that
   * went nowhere.
   */
  const isEpisode = item?.type === 'episode';
  const artwork = isEpisode
    ? (item as { still_url?: string }).still_url
    : (item as { poster_url?: string } | undefined)?.poster_url;
  const slug = isEpisode ? undefined : (item as { slug?: string } | undefined)?.slug;
  const percent = Math.max(0, Math.min(100, Math.round(entry.progress_percent ?? 0)));
  const watched = formatWatched(entry.watched_at);

  const spoken = [
    title,
    isEpisode ? 'episode' : null,
    entry.completed === true ? 'finished' : `${percent}% watched`,
    watched,
  ]
    .filter((part): part is string => typeof part === 'string' && part !== '')
    .join(', ');

  return (
    <Focusable
      accessibilityLabel={spoken}
      {...(slug === undefined ? {} : { accessibilityHint: 'Opens details' })}
      ringRadius={radius.card}
      onPress={slug === undefined ? undefined : () => navigation.push('Title', { type: 'movie', slug, title })}
      style={styles.row}
    >
      <ExpoImage
        source={imageUrl(artwork, isEpisode ? 128 : 64)}
        style={isEpisode ? styles.still : styles.poster}
        contentFit="cover"
        transition={120}
        cachePolicy="memory-disk"
        accessible={false}
      />

      <View style={styles.rowText}>
        <Text numberOfLines={1} style={styles.title}>
          {title}
        </Text>
        {watched === null ? null : <Text style={styles.meta}>{watched}</Text>}

        {/*
          Finished is stated, not implied by a full bar. "100%" and "finished"
          are different claims, and only one of them is what the server said.
        */}
        {entry.completed === true ? (
          <Text style={styles.done}>Finished</Text>
        ) : (
          <View style={styles.track}>
            <View style={[styles.bar, { width: `${percent}%` }]} />
          </View>
        )}
      </View>
    </Focusable>
  );
}

/**
 * "Today", "Yesterday", then a date.
 *
 * Relative only where it is unambiguous. "3 days ago" reads as precision the
 * timestamp does not have once a viewer is scrolling a long list.
 */
function formatWatched(iso: string | null | undefined): string | null {
  if (typeof iso !== 'string' || iso === '') return null;

  const when = new Date(iso);
  if (Number.isNaN(when.getTime())) return null;

  const startOfDay = (d: Date) => new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
  const days = Math.round((startOfDay(new Date()) - startOfDay(when)) / 86_400_000);

  if (days === 0) return 'Today';
  if (days === 1) return 'Yesterday';

  return when.toLocaleDateString(undefined, {
    day: 'numeric',
    month: 'short',
    ...(when.getFullYear() === new Date().getFullYear() ? {} : { year: 'numeric' }),
  });
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg, flexGrow: 1 },

  row: {
    flexDirection: 'row',
    gap: spacing.md,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  poster: {
    width: 56,
    height: 78,
    borderRadius: card.radius,
    backgroundColor: colors.surface,
  },
  // An episode still is 16:9, not a portrait poster. Same row height so the
  // list keeps one rhythm whichever kind of row it is.
  still: {
    width: 104,
    height: 78,
    borderRadius: card.radius,
    backgroundColor: colors.surface,
  },
  rowText: { flex: 1, justifyContent: 'center', gap: 4 },
  title: { ...typography.body, color: card.titleColor },
  meta: { ...typography.caption, color: colors.textMuted },
  done: { ...typography.caption, color: colors.okText },

  track: {
    height: watching.progressHeight,
    backgroundColor: watching.progressTrack,
    marginTop: spacing.xs,
    overflow: 'hidden',
  },
  bar: { height: '100%', backgroundColor: watching.progressBar },

  footer: { paddingVertical: spacing.xl, alignItems: 'center' },
});
