import React, { useCallback } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '../api/jambo';
import {
  asGenreCards,
  asPersonCards,
  asProgressCards,
  asTitleCards,
  asVjCards,
  cardKey,
  isNumberedRail,
  renderableRails,
  type Rail as RailData,
  type TitleCard,
} from '../api/catalogue';
import { colors, rail as railTheme, spacing } from '../ui/theme';
import { useRailMetrics, type RailMetrics } from '../ui/metrics';
import { ErrorState, Loading } from '../ui/components';
import { Hero } from '../ui/rails/Hero';
import { PosterCard } from '../ui/rails/PosterCard';
import { ProgressCard } from '../ui/rails/ProgressCard';
import { Rail } from '../ui/rails/Rail';
import { TopTenCard } from '../ui/rails/TopTenCard';
import { GenreCard, PersonCard, VjCard } from '../ui/rails/TaxonomyCards';

/**
 * The home screen: the website's home screen, rail for rail.
 *
 * `GET /api/v1/home` calls the same `HomeRailsService` the site's view composer
 * calls, so the rails, their order and the rules that choose them are the
 * website's rather than a second implementation that drifts the first time an
 * admin reorders a category. This screen adds no ordering and no filtering of
 * its own beyond dropping rails it has no component for.
 *
 * **It renders what it is given.** `kind` picks the card, never `key` — the
 * contract is explicit that `key` is open-ended because category shelves
 * arrive as `category:<slug>` — and an unknown `kind` is skipped rather than
 * guessed at. A rail added on the server therefore reaches viewers with no app
 * release, which is the property the endpoint was built for.
 */
export function HomeScreen() {
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch, isRefetching } = useQuery({
    queryKey: ['home'],
    queryFn: () => api.home(),
  });

  if (isPending) return <Loading label="Loading Jambo" />;

  if (isError) {
    return (
      <SafeAreaView style={styles.screen} edges={['top']}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load the home screen.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </SafeAreaView>
    );
  }

  const rails = renderableRails(data.rails);
  const hero = data.hero ?? [];

  return (
    <SafeAreaView style={styles.screen} edges={['top']}>
      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={isRefetching}
            onRefresh={() => {
              void refetch();
            }}
            tintColor={colors.primary}
            colors={[colors.primary]}
          />
        }
      >
        <Hero items={hero as TitleCard[]} width={metrics.width} onPress={() => {}} />

        <View style={styles.rails}>
          {rails.map((rail) => (
            <RailFor key={rail.key ?? rail.title} rail={rail} metrics={metrics} />
          ))}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

/**
 * One rail, dispatched on `kind`.
 *
 * The numbered Top 10 treatment is the one thing decided by `key`, and it is a
 * lookup with a default rather than a switch — see `isNumberedRail`. Every key
 * that is not one of the two ranked shelves falls through to the ordinary
 * poster card, including keys that do not exist yet.
 */
function RailFor({ rail, metrics }: { rail: RailData; metrics: RailMetrics }) {
  const title = rail.title ?? '';
  const inset = metrics.inset;

  const openTitle = useCallback(() => {
    // Slice 2b builds the detail screens after Home; until then a card is
    // focusable and pressable but goes nowhere. Deliberately a no-op rather
    // than a navigation to a screen that does not exist.
  }, []);

  switch (rail.kind) {
    case 'titles': {
      const items = asTitleCards(rail);
      const numbered = isNumberedRail(rail.key);

      return (
        <Rail
          title={title}
          data={items}
          itemWidth={metrics.slideWidth}
          inset={inset}
          keyExtractor={cardKey}
          renderItem={({ item, index }) => (
            <CardSlot metrics={metrics}>
              {numbered ? (
                <TopTenCard
                  item={item}
                  rank={index + 1}
                  width={metrics.posterWidth}
                  height={metrics.posterHeight}
                  onPress={openTitle}
                />
              ) : (
                <PosterCard
                  item={item}
                  width={metrics.posterWidth}
                  height={metrics.posterHeight}
                  onPress={openTitle}
                />
              )}
            </CardSlot>
          )}
        />
      );
    }

    case 'progress':
      return (
        <Rail
          title={title}
          data={asProgressCards(rail)}
          // A still is 5:3 and wider than a poster, so the rail pages by a
          // different stride. Passing the poster stride would make
          // scrollToIndex land between cards on a remote.
          itemWidth={metrics.stillWidth + railTheme.cardGap}
          inset={inset}
          keyExtractor={(item, index) =>
            `${item.resume?.type ?? 'cw'}:${item.resume?.id ?? index}`
          }
          renderItem={({ item }) => (
            <CardSlot metrics={metrics}>
              <ProgressCard
                item={item}
                width={metrics.stillWidth}
                height={metrics.stillHeight}
                onPress={openTitle}
              />
            </CardSlot>
          )}
        />
      );

    case 'genres':
      return (
        <Rail
          title={title}
          data={asGenreCards(rail)}
          itemWidth={metrics.stillWidth + railTheme.cardGap}
          inset={inset}
          keyExtractor={(item, index) => item.slug ?? String(index)}
          renderItem={({ item }) => (
            <CardSlot metrics={metrics}>
              <GenreCard
                item={item}
                width={metrics.stillWidth}
                height={metrics.posterHeight}
              />
            </CardSlot>
          )}
        />
      );

    case 'vjs':
      return (
        <Rail
          title={title}
          data={asVjCards(rail)}
          itemWidth={metrics.stillWidth + railTheme.cardGap}
          inset={inset}
          keyExtractor={(item, index) => item.slug ?? String(index)}
          renderItem={({ item }) => (
            <CardSlot metrics={metrics}>
              <VjCard item={item} width={metrics.stillWidth} />
            </CardSlot>
          )}
        />
      );

    case 'people':
      return (
        <Rail
          title={title}
          data={asPersonCards(rail)}
          itemWidth={metrics.posterWidth + railTheme.cardGap}
          inset={inset}
          keyExtractor={(item, index) => item.slug ?? String(index)}
          renderItem={({ item }) => (
            <CardSlot metrics={metrics}>
              <PersonCard item={item} width={metrics.posterWidth} />
            </CardSlot>
          )}
        />
      );

    default:
      // An unknown kind is skipped, as the contract asks. A server that starts
      // sending a rail this build has no component for must not produce a
      // heading over an empty row.
      return null;
  }
}

/**
 * The gutter between cards.
 *
 * Half on each side rather than a margin on one, because that is how the site
 * does it — swiper runs `spaceBetween: 0` and the visible gap is the slide's
 * own padding. It also means the first and last cards are inset by the same
 * half, which is why the rail's own padding subtracts it.
 */
function CardSlot({
  children,
  metrics,
}: {
  children: React.ReactNode;
  metrics: RailMetrics;
}) {
  return <View style={{ paddingHorizontal: metrics.cardPadding }}>{children}</View>;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { paddingBottom: spacing.xxl },
  // The hero runs to the screen edge; the rails start after it.
  rails: { paddingTop: spacing.xl },
});
