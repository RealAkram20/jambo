import React, { useCallback, useMemo } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { useNavigation } from '@react-navigation/native';

import { AppHeader } from '../ui/AppHeader';
import { api } from '../api/jambo';
import {
  asBannerSlides,
  asGenreCards,
  asPersonCards,
  asProgressCards,
  asTitleCards,
  asVjCards,
  cardKey,
  collectionKeyFor,
  isNumberedRail,
  renderableRails,
  type BannerSlide,
  type ContinueWatchingCard,
  type HeroCard,
  type Rail as RailData,
  type TitleCard,
} from '../api/catalogue';
import type { AppStackParams } from '../navigation/types';
import type { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { colors, genreTile, rail as railTheme, spacing } from '../ui/theme';
import { useRailMetrics, type RailMetrics } from '../ui/metrics';
import { ErrorState, Loading } from '../ui/components';
import { Hero } from '../ui/rails/Hero';
import { PosterCard } from '../ui/rails/PosterCard';
import { ProgressCard } from '../ui/rails/ProgressCard';
import { resumeTarget } from '../ui/player/resume';
import { Rail } from '../ui/rails/Rail';
import { RankedBanner } from '../ui/rails/RankedBanner';
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

  /*
   * Opens a title from a rail card, a hero slide or a daily banner slide.
   *
   * All four shapes carry `type` and `slug`, which is all this reads — so
   * every surface on this screen shares one navigation path rather than
   * several that can disagree about what a series opens.
   */
  const openTitle = useCallback(
    (item: TitleCard | HeroCard | BannerSlide) => {
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
    <AppHeader />
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
        <Loading label="Loading Jambo" />
      </View>
    );
  }

  if (isError) {
    return (
      <View style={styles.screen}>
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
        <Hero items={hero} width={metrics.width} onPress={openTitle} />

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
              onResume={(item) => {
                const target = resumeTarget(item);
                // Null cannot reach here — the card renders inert without a
                // target — but a guard beats a non-null assertion that would
                // crash the player screen on a malformed card.
                if (target !== null) navigation.push('Watch', target);
              }}
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
 * Presentation within a kind comes from `style`, which the server sends and
 * this build reads with a default: `numbered` draws the Top 10 numerals, the
 * two banner styles pick a layout, and anything else — including a style that
 * does not exist yet — falls through to the ordinary treatment. It used to be
 * a hardcoded set of two rail keys here, which meant a third ranked shelf
 * needed an app release to look right.
 */
function RailFor({
  rail,
  metrics,
  onOpenTitle,
  onOpenTaxonomy,
  onResume,
  onSeeAll,
}: {
  rail: RailData;
  metrics: RailMetrics;
  onOpenTitle: (item: TitleCard | BannerSlide) => void;
  onOpenTaxonomy: (kind: 'genre' | 'vj' | 'cast', slug: string, name: string) => void;
  /** Resume a Continue Watching card. A callback, like the others here, so
   *  this component keeps knowing nothing about the navigator. */
  onResume: (item: ContinueWatchingCard) => void;
  /** Undefined for a rail with no archive — the Rail then draws no link. */
  onSeeAll: (() => void) | undefined;
}) {
  const title = rail.title ?? '';
  const inset = metrics.inset;

  switch (rail.kind) {
    case 'titles': {
      const items = asTitleCards(rail);
      const numbered = isNumberedRail(rail);

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
                The card resumes, as of the player slice.

                It could not before, and the reason shaped the call it uses:
                `ContinueWatchingCard` carries `resume: {type, id}` and no
                slug, and the detail endpoint is slug-only — `/movies/1` is a
                404, checked against the running API — so the card could not
                even fall back to opening the title's page. An earlier cut
                passed the id through `onOpenTitle`, which returns early
                without a slug, so every tap silently did nothing; that is why
                slice 2b shipped it deliberately inert.
                `POST /playback/sessions` takes exactly `{type, id}`, so this
                needed no server change at all.

                `resumeTarget` returning null still renders it inert, which is
                the honest fallback for a card the server sent incomplete.

                The remove button is absent for a different reason and is still
                absent: the website's card has one, but the DELETE behind it is
                `['web', 'Authenticate']` in Frontend's web routes, so a bearer
                token cannot call it.
              */}
              <ProgressCard
                item={item}
                width={metrics.stillWidth}
                height={metrics.stillHeight}
                onPress={resumeTarget(item) === null ? undefined : () => onResume(item)}
              />
            </CardSlot>
          )}
        />
      );

    case 'genres': {
      /*
       * Two tiles per view, which is the site's `data-mobile="2"`: a 358pt
       * content width holds two 179pt slides, each a 164pt tile inside a
       * 7.5pt gutter. The shared `stillWidth` is derived from the poster
       * rail's card count and gave 2.4 — a third tile half off the edge,
       * where the site shows two whole ones.
       */
      const slide = (metrics.width - inset * 2) / 2;
      const tile = slide - genreTile.gap * 2;

      return (
        <Rail
          title={title}
          data={asGenreCards(rail)}
          itemWidth={slide}
          inset={inset}
          // The website's Genres rail carries "View All" next to its heading,
          // and the app's did not. It goes to the genre index, not to a
          // collection of titles — see seeAllFor.
          onSeeAll={onSeeAll}
          keyExtractor={(item, index) => item.slug ?? String(index)}
          renderItem={({ item }) => (
            <View style={{ paddingHorizontal: genreTile.gap }}>
              {/*
                No `height`: the genre tile is 5:3 on the site and derives its
                own from the width. It was being given the poster height and
                drawing a bordered chip; see TaxonomyCards.
              */}
              <GenreCard
                item={item}
                width={tile}
                onPress={
                  item.slug === undefined
                    ? undefined
                    : () => onOpenTaxonomy('genre', item.slug as string, item.name ?? '')
                }
              />
            </View>
          )}
        />
      );
    }

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

    /*
     * The two daily Top 10 banners.
     *
     * Full-bleed and headingless, which is why they are drawn outside the
     * rails' gutter — see `styles.rails`. `style` chooses between them, and
     * `RankedBanner` treats an unrecognised one as the movies layout rather
     * than rendering nothing: a banner drawn slightly wrong is recoverable, a
     * blank space where ten titles should be is not.
     */
    case 'banner':
      return (
        <RankedBanner
          style={rail.style}
          items={asBannerSlides(rail)}
          width={metrics.width}
          onPress={onOpenTitle}
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

  /*
   * Genres, whose archive is a list of GENRES rather than of titles.
   *
   * It is named here rather than derived because no rule derives it: there is
   * no `genres` key in `RailArchiveCatalog`, so the collection check below
   * correctly refuses it, and the website agrees — its rail links to
   * `/all-genres`, not to `/collection/genres`. Without this the rail showed
   * no "View all" at all, which is what Rio noticed.
   */
  if (key === 'genres') {
    return () => navigation.navigate('Genres', { title });
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
