import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { FilmSlate } from 'phosphor-react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '../api/jambo';
import { seasonsOf, type Episode, type TitleDetail } from '../api/catalogue';
import { imageUrl } from '../ui/media';
import { useRailMetrics } from '../ui/metrics';
import { Button, ErrorState, Loading } from '../ui/components';
import { Focusable } from '../ui/rails/Focusable';
import { PremiumBadge } from '../ui/rails/PosterCard';
import { card, colors, fonts, radius, spacing, typography } from '../ui/theme';
import type { AppScreenProps } from '../navigation/types';

/**
 * A movie or a series page.
 *
 * One screen for both, because the website's two pages are the same page with
 * an episode list added: the same 21:9 backdrop, the same title block, the
 * same CTA row, the same genre and cast strips. Building them separately is
 * how the series page quietly stops matching the movie page.
 *
 * **There is no Play button in this slice, and that is deliberate.** The
 * player arrives with `expo-jambo-media` in a later slice, and a Play control
 * that cannot play is the one thing the screen rules forbid outright — worse
 * than an absent one, because a viewer reads it as the app being broken rather
 * than as a feature not being here yet. The page says so in a line instead.
 *
 * **Download is absent for the same reason** and a different one: Phase 3 owns
 * it, and the licence and encrypted cache behind it do not exist yet.
 */
export function TitleDetailScreen({ route, navigation }: AppScreenProps<'Title'>) {
  const { type, slug } = route.params;
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['title', type, slug],
    queryFn: () => api.titleDetail(type, slug),
  });

  if (isPending) return <Loading label="Loading" />;

  if (isError) {
    return (
      <SafeAreaView style={styles.screen} edges={['top']}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load this title.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </SafeAreaView>
    );
  }

  const detail: TitleDetail = data.detail;
  const seasons = seasonsOf(detail);
  const title = detail.title ?? 'Untitled';

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <Backdrop detail={detail} width={metrics.width} />

        <View style={styles.body}>
          <Text accessibilityRole="header" style={styles.title}>
            {title}
          </Text>

          <MetaRow detail={detail} released={data.isReleased} />

          <View style={styles.ctaRow}>
            <WatchlistButton detail={detail} type={type} />
          </View>

          {/*
            Said plainly rather than hidden. A detail page with no way to watch
            and no explanation reads as a broken page; one sentence turns it
            into a page that is honest about where the app is.
          */}
          <View style={styles.notice}>
            <FilmSlate size={18} color={colors.textMuted} weight="regular" />
            <Text style={styles.noticeText}>
              Playback arrives in the next update. Everything else here works.
            </Text>
          </View>

          {typeof detail.synopsis === 'string' && detail.synopsis !== '' ? (
            <Text style={styles.synopsis}>{detail.synopsis}</Text>
          ) : null}

          <ChipStrip
            label="Genres"
            items={detail.genres ?? []}
            onPress={(genre) => {
              if (genre.slug !== undefined) {
                navigation.push('Taxonomy', { kind: 'genre', slug: genre.slug, name: genre.name ?? '' });
              }
            }}
          />
          <ChipStrip label="Categories" items={detail.categories ?? []} />

          {seasons.length > 0 ? <Seasons seasons={seasons} /> : null}

          <CastStrip detail={detail} navigation={navigation} />
        </View>
      </ScrollView>
    </View>
  );
}

/**
 * The 21:9 backdrop, as CHANGELOG 1.8.9 set it on the website's detail pages.
 *
 * It runs under the status bar and fades into the page background, so the
 * artwork is the first thing on screen rather than a picture in a box.
 */
function Backdrop({ detail, width }: { detail: TitleDetail; width: number }) {
  const height = Math.round(width / (21 / 9));

  return (
    <View style={{ width, height }}>
      <ExpoImage
        // Falls back to the poster: `backdrop_url` is nullable in practice and
        // an empty hero is worse than a cropped poster.
        source={imageUrl(detail.backdrop_url ?? detail.poster_url, width)}
        style={StyleSheet.absoluteFill}
        contentFit="cover"
        transition={180}
        cachePolicy="memory-disk"
        accessible={false}
      />
      <LinearGradient
        colors={['transparent', 'rgba(0, 0, 0, 0.6)', colors.background]}
        locations={[0, 0.6, 1]}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />
    </View>
  );
}

/**
 * Year, certification, runtime, views — each shown only if the server sent it.
 *
 * ⚠️ `rating` is a certification (G, PG, NC-17), not a star score. It is drawn
 * as a badge, which is what the website does with it.
 */
function MetaRow({ detail, released }: { detail: TitleDetail; released: boolean }) {
  const runtime = (detail as { runtime_minutes?: number | null }).runtime_minutes;
  const parts: string[] = [];

  if (typeof detail.year === 'number') parts.push(String(detail.year));
  if (typeof runtime === 'number' && runtime > 0) parts.push(formatRuntime(runtime));
  if (typeof detail.views_count === 'number') parts.push(`${formatCount(detail.views_count)} views`);

  return (
    <View style={styles.metaRow}>
      {typeof detail.rating === 'string' && detail.rating !== '' ? (
        <View style={styles.certification}>
          <Text style={styles.certificationText}>{detail.rating}</Text>
        </View>
      ) : null}

      {typeof detail.tier_required === 'string' && detail.tier_required !== '' ? (
        <View style={styles.badgeSlot}>
          <PremiumBadge />
        </View>
      ) : null}

      {parts.length > 0 ? <Text style={styles.meta}>{parts.join('  ·  ')}</Text> : null}

      {!released ? <Text style={styles.upcoming}>Coming soon</Text> : null}
    </View>
  );
}

/**
 * Add to or remove from the watchlist.
 *
 * Optimistic, with a real rollback: the tap is the whole interaction and
 * waiting a second on a Ugandan connection to see a bookmark fill in feels
 * broken. Both calls are idempotent server-side, so a retry after a dropped
 * connection cannot flip the state the wrong way — which is exactly why the
 * API has add and remove rather than the website's toggle.
 */
function WatchlistButton({ detail, type }: { detail: TitleDetail; type: 'movie' | 'series' }) {
  const queryClient = useQueryClient();
  const [saved, setSaved] = useState(false);
  const [busy, setBusy] = useState(false);

  // The API's word for a series is `show`.
  const watchableType = type === 'movie' ? 'movie' : 'show';
  const id = detail.id;

  const toggle = useCallback(async () => {
    if (busy || id === undefined) return;

    const next = !saved;
    setSaved(next);
    setBusy(true);

    try {
      if (next) {
        await api.addToWatchlist(watchableType, id);
      } else {
        await api.removeFromWatchlist(watchableType, id);
      }
      await queryClient.invalidateQueries({ queryKey: ['watchlist'] });
    } catch {
      // Put it back. A bookmark that says "saved" over a request that failed
      // is a screen telling the viewer something untrue about their account.
      setSaved(!next);
    } finally {
      setBusy(false);
    }
  }, [busy, id, queryClient, saved, watchableType]);

  return (
    <Button
      label={saved ? 'On your watchlist' : 'Add to watchlist'}
      tone="quiet"
      busy={busy}
      disabled={id === undefined}
      onPress={() => void toggle()}
    />
  );
}

function ChipStrip({
  label,
  items,
  onPress,
}: {
  label: string;
  items: readonly { slug?: string; name?: string }[];
  onPress?: ((item: { slug?: string; name?: string }) => void) | undefined;
}) {
  if (items.length === 0) return null;

  return (
    <View style={styles.section}>
      <Text accessibilityRole="header" style={styles.sectionHeading}>
        {label}
      </Text>
      <View style={styles.chips}>
        {items.map((item, index) => (
          <Focusable
            key={item.slug ?? String(index)}
            accessibilityLabel={item.name ?? ''}
            ringRadius={radius.pill}
            onPress={onPress ? () => onPress(item) : undefined}
            style={styles.chip}
          >
            <Text style={styles.chipText}>{item.name}</Text>
          </Focusable>
        ))}
      </View>
    </View>
  );
}

/**
 * The season and episode list.
 *
 * A plain vertical list rather than the website's grid/scroller toggle: the
 * toggle exists on desktop because a grid of 24 episode tiles fits there, and
 * on a phone both modes collapse to the same single column. Building a control
 * whose two states look identical would be building a control for its own
 * sake.
 */
function Seasons({ seasons }: { seasons: readonly { number?: number; title?: string | null; episodes?: Episode[] }[] }) {
  const [openSeason, setOpenSeason] = useState<number>(seasons[0]?.number ?? 1);

  return (
    <View style={styles.section}>
      <Text accessibilityRole="header" style={styles.sectionHeading}>
        Episodes
      </Text>

      {seasons.length > 1 ? (
        <View style={styles.chips}>
          {seasons.map((season, index) => {
            const number = season.number ?? index + 1;
            const active = number === openSeason;

            return (
              <Focusable
                key={number}
                accessibilityLabel={season.title ?? `Season ${number}`}
                accessibilityHint={active ? 'Showing' : 'Show this season'}
                ringRadius={radius.pill}
                onPress={() => setOpenSeason(number)}
                style={[styles.chip, active ? styles.chipActive : null] as never}
              >
                <Text style={[styles.chipText, active && styles.chipTextActive]}>
                  {season.title ?? `Season ${number}`}
                </Text>
              </Focusable>
            );
          })}
        </View>
      ) : null}

      {(seasons.find((s, i) => (s.number ?? i + 1) === openSeason)?.episodes ?? []).map(
        (episode) => <EpisodeRow key={episode.id} episode={episode} />,
      )}
    </View>
  );
}

function EpisodeRow({ episode }: { episode: Episode }) {
  const locked =
    typeof episode.effective_tier_required === 'string' && episode.effective_tier_required !== '';

  return (
    <Focusable
      accessibilityLabel={`Episode ${episode.number ?? ''}, ${episode.title ?? ''}${locked ? ', premium' : ''}`}
      ringRadius={radius.card}
      style={styles.episode}
    >
      <ExpoImage
        source={imageUrl(episode.still_url, 160)}
        style={styles.episodeStill}
        contentFit="cover"
        transition={120}
        cachePolicy="memory-disk"
        accessible={false}
      />
      <View style={styles.episodeText}>
        <Text numberOfLines={1} style={styles.episodeTitle}>
          {episode.number}. {episode.title}
        </Text>
        {typeof episode.runtime_minutes === 'number' ? (
          <Text style={styles.episodeMeta}>{formatRuntime(episode.runtime_minutes)}</Text>
        ) : null}
        {typeof episode.synopsis === 'string' && episode.synopsis !== '' ? (
          <Text numberOfLines={2} style={styles.episodeSynopsis}>
            {episode.synopsis}
          </Text>
        ) : null}
      </View>
      {/*
        Drawn from `effective_tier_required`, never from `tier_required` — the
        contract is explicit that an episode's own plan is usually null because
        the admin sets it on the series, and reading null as "free" would put a
        free badge on most of the catalogue.
      */}
      {locked ? (
        <View style={styles.episodeBadge}>
          <PremiumBadge />
        </View>
      ) : null}
    </Focusable>
  );
}

function CastStrip({
  detail,
  navigation,
}: {
  detail: TitleDetail;
  navigation: AppScreenProps<'Title'>['navigation'];
}) {
  const cast = detail.cast ?? [];
  if (cast.length === 0) return null;

  return (
    <View style={styles.section}>
      <Text accessibilityRole="header" style={styles.sectionHeading}>
        Cast
      </Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false}>
        {cast.map((person, index) => (
          <Focusable
            key={person.slug ?? String(index)}
            accessibilityLabel={person.name ?? ''}
            ringRadius={36}
            onPress={
              person.slug === undefined
                ? undefined
                : () =>
                    navigation.push('Taxonomy', {
                      kind: 'cast',
                      slug: person.slug as string,
                      name: person.name ?? '',
                    })
            }
            style={styles.castMember}
          >
            <ExpoImage
              source={imageUrl(person.photo_url, 72)}
              style={styles.castPhoto}
              contentFit="cover"
              transition={120}
              cachePolicy="memory-disk"
              accessible={false}
            />
            <Text numberOfLines={2} style={styles.castName}>
              {person.name}
            </Text>
          </Focusable>
        ))}
      </ScrollView>
    </View>
  );
}

/** "1h 30m", the way the website writes it, and never "90m" for a feature. */
function formatRuntime(minutes: number): string {
  const hours = Math.floor(minutes / 60);
  const rest = minutes % 60;
  if (hours === 0) return `${rest}m`;
  return rest === 0 ? `${hours}h` : `${hours}h ${rest}m`;
}

/** 445088 → "445K". A view count is a sense of scale, not an audited figure. */
function formatCount(value: number): string {
  if (value >= 1_000_000) return `${Math.round(value / 100_000) / 10}M`;
  if (value >= 1_000) return `${Math.round(value / 1_000)}K`;
  return String(value);
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { paddingBottom: spacing.xxl },
  body: { paddingHorizontal: spacing.lg, marginTop: -spacing.xl },

  title: { ...typography.title, color: card.titleColor },

  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  certification: {
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 6,
    paddingVertical: 1,
    borderRadius: 3,
  },
  certificationText: { ...typography.caption, color: colors.text, fontSize: 12 },
  badgeSlot: { width: card.badgeSize, height: card.badgeSize },
  meta: { ...typography.caption, color: colors.textMuted },
  upcoming: { ...typography.caption, color: colors.warning },

  ctaRow: { marginTop: spacing.lg },

  notice: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.lg,
    padding: spacing.md,
    borderRadius: radius.alert,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.divider,
  },
  noticeText: { ...typography.caption, color: colors.textMuted, flexShrink: 1 },

  synopsis: { ...typography.body, color: colors.text, marginTop: spacing.lg },

  section: { marginTop: spacing.xl },
  sectionHeading: {
    ...typography.railHeading,
    color: colors.text,
    marginBottom: spacing.md,
  },

  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  chip: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.pill,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
  },
  chipActive: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { ...typography.caption, color: colors.text },
  chipTextActive: { color: colors.onPrimary, fontFamily: fonts.medium },

  episode: {
    flexDirection: 'row',
    gap: spacing.md,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.divider,
  },
  episodeStill: {
    width: 128,
    height: 72,
    borderRadius: radius.card,
    backgroundColor: colors.surface,
  },
  episodeText: { flex: 1 },
  episodeTitle: { ...typography.body, fontFamily: fonts.medium, color: card.titleColor },
  episodeMeta: { ...typography.caption, color: colors.textMuted, marginTop: 2 },
  episodeSynopsis: { ...typography.caption, color: colors.textMuted, marginTop: 4 },
  episodeBadge: { width: card.badgeSize, height: card.badgeSize },

  castMember: { width: 80, marginRight: spacing.md, alignItems: 'center' },
  castPhoto: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.surface,
  },
  castName: {
    ...typography.caption,
    color: colors.text,
    textAlign: 'center',
    marginTop: spacing.sm,
  },
});
