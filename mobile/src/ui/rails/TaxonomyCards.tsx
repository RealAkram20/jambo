import { StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';

import { card, colors, fonts, radius, spacing, typography } from '../theme';
import { imageUrl } from '../media';
import type {
  GenreCard as GenreCardData,
  PersonCard as PersonCardData,
  VjCard as VjCardData,
} from '../../api/catalogue';
import { Focusable } from './Focusable';

/**
 * The three rails that are not titles: genres, VJs and personalities.
 *
 * They share a file because they share a shape — a label, sometimes a picture,
 * one press target — and splitting them into three modules that differ by two
 * lines each is how a kit stops being a kit.
 *
 * Counts are shown only when the server sends them. `movies_count` and
 * `shows_count` are nullable in the contract, and a card that renders "0
 * movies" for an unknown count states something false about the catalogue.
 */

export type GenreItem = GenreCardData;
/** VJs carry counts, personalities do not - the union is what the rails send. */
export type PersonLikeItem = VjCardData | PersonCardData;

/**
 * A genre chip.
 *
 * The site's genre cards carry a per-genre `colour` from the admin. It is used
 * as a tint behind the name rather than as the fill: several of the seeded
 * colours are fully saturated (`#e53935`), and white body text on those fails
 * contrast outright. A low-opacity tint over the site's own surface keeps the
 * genre identifiable and the label readable.
 */
export function GenreCard({
  item,
  width,
  height,
  onPress,
}: {
  item: GenreItem;
  width: number;
  height: number;
  onPress?: (() => void) | undefined;
}) {
  const name = item.name ?? 'Genre';
  const total = countOf(item.movies_count, item.shows_count);

  return (
    <Focusable
      accessibilityLabel={total === null ? name : `${name}, ${total} titles`}
      ringRadius={radius.card}
      onPress={onPress}
      style={{ width }}
    >
      <View
        style={[
          styles.genre,
          { width, height: Math.round(height * 0.62), borderRadius: radius.card },
          typeof item.colour === 'string' && item.colour !== ''
            ? { borderColor: item.colour }
            : null,
        ]}
      >
        <Text numberOfLines={2} style={styles.genreName}>
          {name}
        </Text>
        {total === null ? null : <Text style={styles.genreCount}>{total} titles</Text>}
      </View>
    </Focusable>
  );
}

/**
 * A VJ. The image is a 16:9 still — `image_url` falls back through the VJ's
 * most recent published title server-side, so a VJ with no photo still has a
 * card rather than a hole.
 */
export function VjCard({
  item,
  width,
  onPress,
}: {
  item: PersonLikeItem;
  width: number;
  onPress?: (() => void) | undefined;
}) {
  const name = item.name ?? 'VJ';
  const total = countsOf(item);
  const height = Math.round(width / (16 / 9));

  return (
    <Focusable
      accessibilityLabel={total === null ? name : `${name}, ${total} titles`}
      ringRadius={radius.card}
      onPress={onPress}
      style={{ width }}
    >
      <ExpoImage
        source={imageUrl(item.image_url, width)}
        style={[styles.vjImage, { width, height, borderRadius: radius.card }]}
        contentFit="cover"
        recyclingKey={item.slug ?? name}
        transition={120}
        cachePolicy="memory-disk"
        accessible={false}
      />
      <Text numberOfLines={1} style={styles.name}>
        {name}
      </Text>
      {total === null ? null : <Text style={styles.meta}>{total} titles</Text>}
    </Focusable>
  );
}

/** A cast member or personality: a round portrait and a name, as the site draws it. */
export function PersonCard({
  item,
  width,
  onPress,
}: {
  item: PersonLikeItem;
  width: number;
  onPress?: (() => void) | undefined;
}) {
  const name = item.name ?? 'Unknown';

  return (
    <Focusable
      accessibilityLabel={name}
      ringRadius={width / 2}
      onPress={onPress}
      style={{ width }}
    >
      <ExpoImage
        source={imageUrl(item.image_url, width)}
        style={[styles.portrait, { width, height: width, borderRadius: width / 2 }]}
        contentFit="cover"
        recyclingKey={item.slug ?? name}
        transition={120}
        cachePolicy="memory-disk"
        accessible={false}
      />
      <Text numberOfLines={2} style={styles.nameCentred}>
        {name}
      </Text>
    </Focusable>
  );
}

/**
 * Movies plus series, when the server sent either.
 *
 * Returns null — not zero — when both are absent. The contract marks both
 * nullable, and "0 titles" under a genre that has hundreds is a screen lying
 * about the catalogue.
 */
function countOf(movies: number | null | undefined, shows: number | null | undefined): number | null {
  if (typeof movies !== 'number' && typeof shows !== 'number') return null;
  return (movies ?? 0) + (shows ?? 0);
}

/**
 * The same question for a card that may or may not carry counts. `PersonCard`
 * has no count fields at all, so reading them off the union needs the check
 * rather than an assertion.
 */
function countsOf(item: PersonLikeItem): number | null {
  const counted = item as { movies_count?: number | null; shows_count?: number | null };
  return countOf(counted.movies_count, counted.shows_count);
}

const styles = StyleSheet.create({
  genre: {
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.divider,
    paddingHorizontal: spacing.md,
    justifyContent: 'center',
  },
  genreName: { ...typography.body, fontFamily: fonts.medium, color: card.titleColor },
  genreCount: { ...typography.caption, color: colors.textMuted, marginTop: spacing.xs },

  vjImage: { backgroundColor: colors.surface },
  portrait: { backgroundColor: colors.surface },

  name: {
    ...typography.caption,
    fontFamily: fonts.medium,
    color: card.titleColor,
    marginTop: spacing.sm,
  },
  nameCentred: {
    ...typography.caption,
    fontFamily: fonts.medium,
    color: card.titleColor,
    marginTop: spacing.sm,
    textAlign: 'center',
  },
  meta: { ...typography.caption, color: colors.textMuted, marginTop: 2 },
});
