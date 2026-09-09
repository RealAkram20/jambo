import React, { useCallback, useMemo } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { useNavigation } from '@react-navigation/native';

import { api } from '../api/jambo';
import {
  asGenreCards,
  asPersonCards,
  asProgressCards,
  asTitleCards,
  asVjCards,
  cardKey,
  collectionKeyFor,
  isNumberedRail,
  renderableRails,
  type Rail as RailData,
  type TitleCard,
} from '../api/catalogue';
import type { AppStackParams } from '../navigation/types';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { colors, rail as railTheme, spacing } from '../ui/theme';
import { useRailMetrics, type RailMetrics } from '../ui/metrics';
import { ErrorState, Loading } from '../ui/components';
import { AppHeader } from '../ui/AppHeader';
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
  const navigation = useNavigation<NativeStackNavigationProp<AppStackParams>>();

  const openTitle = useCallback(
    (item: TitleCard) => {
      if (item.slug === undefined) return;
      navigation.navigate('Title', {
        type: item.type === 'series' ? 'series' : 'movie',
        slug: item.slug,
        ...(item.title === undefined ? {} : { title: item.title }),
      });
    },
    [navigation],
  );

  const header = (
    <AppHeader
      onSearch={() => navigation.navigate('Search')}
      // The account icon opens the profile drawer, which is the website's
      // own profile-hub sidebar. The Account screen is still a route and the
      // drawer's identity block is what reaches it.
      onAccount={() => navigation.navigate('ProfileMenu')}
    />
  );

  const { data, isPending, isError, error, refetch, isRefetching } = useQuery({
    queryKey: ['home'],
    queryFn: () => api.home(),
  });

  /*
   * Which rails have an archive to link to.
   *
   * Asked of the server rather than hardcoded. The rail and collection key
   * spaces do not line up (see `collectionKeyFor`), three rails have no
   * archive at all, and one collection has no rail — so the only honest test
   * of "is there a View all here" is whether the derived key is in the list
   * the server publishes. A collection added server-side then lights up its
   * rail with no app release, and a rail without one shows no link rather
   * than a link to a 404.
   *
   * It is allowed to fail: `?? []` means a dropped request costs the "View
   * all" links and nothing else, rather than taking the home screen with it.
   */
  const { data: collections } = useQuery({
    queryKey: ['collections'],
    queryFn: () => api.collections(),
    staleTime: 60 * 60 * 1000,
  });

  const archives = useMemo(
    () => new Set((collections ?? []).map((c) => c.key)),
    [collections],
  );

  if (isPending) {
    return (
      <View style={styles.screen}>
        {header}
        <Loading label="Loading Jambo" />
      </View>
    );
  }

  if (isError) {
    return (
      <View style={styles.screen}>
        {header}
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
      </View>
    );
  }

  const rails = renderableRails(data.rails);
  const hero = data.hero ?? [];

  return (
    <View style={styles.screen}>
      {header}
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
        <Hero items={hero as TitleCard[]} width={metrics.width} onPress={openTitle} />

        <View style={styles.rails}>
          {rails.map((rail) => (
            <RailFor
              key={rail.key ?? rail.title}
              rail={rail}
              metrics={metrics}
              onOpenTitle={openTitle}
              onOpenTaxonomy={(kind, slug, name) =>
                navigation.navigate('Taxonomy', { kind, slug, name })
              }
              onSeeAll={seeAllFor(rail, archives, navigation)}
            />
          ))}
        </View>
      </ScrollView>
    </View>
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
function RailFor({
  rail,
  metrics,
  onOpenTitle,
  onOpenTaxonomy,
  onSeeAll,
}: {
  rail: RailData;
  metrics: RailMetrics;
  onOpenTitle: (item: TitleCard) => void;
  onOpenTaxonomy: (kind: 'genre' | 'vj' | 'cast', slug: string, name: string) => void;
  /** Undefined for a rail with no archive — the Rail then draws no link. */
  onSeeAll: (() => void) | undefined;
}) {
  const title = rail.title ?? '';
  const inset = metrics.inset;

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
          onSeeAll={onSeeAll}
          keyExtractor={cardKey}
          renderItem={({ item, index }) => (
            <CardSlot metrics={metrics}>
              {numbered ? (
                <TopTenCard
                  item={item}
                  rank={index + 1}
                  width={metrics.posterWidth}
                  height={metrics.posterHeight}
                  onPress={() => onOpenTitle(item)}
                />
              ) : (
                <PosterCard
                  item={item}
                  width={metrics.posterWidth}
                  height={metrics.posterHeight}
                  onPress={() => onOpenTitle(item)}
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
              {/*
                No `onPress`, so the card is not interactive at all.

                Its one real action is resume, and resume needs the player. It
                cannot fall back to opening the title's page either:
                `ContinueWatchingCard` carries `resume: {type, id}` and no
                slug, and the detail endpoint is slug-only — `/movies/1` is a
                404, checked against the running API. An earlier cut of this
                passed the id through `onOpenTitle`, which returns early
                without a slug, so every tap silently did nothing. That is the
                dead control the rules forbid, and it is why `ProgressCard`
                now renders a plain announced View when it has no action.

                The remove button is absent for a different reason: the
                website's card has one, but the DELETE behind it is
                `['web', 'Authenticate']` in Frontend's web routes, so a
                bearer token cannot call it.
              */}
              <ProgressCard
                item={item}
                width={metrics.stillWidth}
                height={metrics.stillHeight}
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
                onPress={
                  item.slug === undefined
                    ? undefined
                    : () => onOpenTaxonomy('genre', item.slug as string, item.name ?? '')
                }
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
              <VjCard
                item={item}
                width={metrics.stillWidth}
                onPress={
                  item.slug === undefined
                    ? undefined
                    : () => onOpenTaxonomy('vj', item.slug as string, item.name ?? '')
                }
              />
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
              <PersonCard
                item={item}
                width={metrics.posterWidth}
                onPress={
                  item.slug === undefined
                    ? undefined
                    : () => onOpenTaxonomy('cast', item.slug as string, item.name ?? '')
                }
              />
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
 * Where a rail's "View all" goes, or nothing at all.
 *
 * Three outcomes, and the third is the one that matters:
 *
 *  - a **category shelf** (`category:<slug>`) goes to its taxonomy archive,
 *    which is a better screen than a generic collection and already exists;
 *  - a rail whose derived collection key is one the **server publishes** goes
 *    to that collection;
 *  - anything else gets **no link**.
 *
 * That last case is not a fallback, it is the point. Three home rails —
 * `top_movies`, `top_series`, `international_series` — have no archive on the
 * server, and offering "View all" on them would be a control whose only
 * outcome is a 404. The check is against the live list rather than a
 * hardcoded one, so the answer stays right when the server changes.
 */
function seeAllFor(
  rail: RailData,
  archives: ReadonlySet<string>,
  navigation: NativeStackNavigationProp<AppStackParams>,
): (() => void) | undefined {
  const key = rail.key;
  const title = rail.title ?? '';

  if (key === undefined) return undefined;

  if (key.startsWith('category:')) {
    const slug = key.slice('category:'.length);
    return slug === ''
      ? undefined
      : () => navigation.navigate('Taxonomy', { kind: 'category', slug, name: title });
  }

  const collection = collectionKeyFor(key);
  if (collection === null || !archives.has(collection)) return undefined;

  return () => navigation.navigate('Collection', { rail: collection, title });
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
