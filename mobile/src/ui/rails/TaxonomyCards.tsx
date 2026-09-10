import { StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';

import { card, colors, fonts, genreTile, radius, spacing, typography } from '../theme';
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
 * A genre tile: the website's, measured rather than approximated.
 *
 * A genre owns no artwork of its own, so the site borrows a still from its
 * most recent published title, darkens the left of it and centres the name
 * over the whole tile. That is `card-genres-grid.blade.php`, and until
 * 2026-09-10 the app drew a bordered chip with a title count instead — Rio saw
 * the two side by side and asked for the site's.
 *
 * The picture arrives as `image_url` on the card, resolved server-side by
 * `Genre::attachFeaturedImages` so both surfaces show the same still for the
 * same genre. Null is a real answer for a genre with no published content, and
 * the tile then draws its label on the surface colour rather than an empty
 * frame.
 *
 * Every measurement is in `theme.genreTile` with the selector it came from.
 * The per-genre `colour` the admin sets is deliberately NOT used: the site's
 * own tile does not use it either, and several seeded values are fully
 * saturated (`#e53935`), which white 16pt text fails contrast against.
 */
export function GenreCard({
  item,
  width,
  onPress,
}: {
  item: GenreItem;
  width: number;
  onPress?: (() => void) | undefined;
}) {
  const name = item.name ?? 'Genre';
  const total = countOf(item.movies_count, item.shows_count);
  const height = Math.round(width / genreTile.aspect);
  const art = imageUrl(item.image_url, width);

  return (
    <Focusable
      // The count is not drawn — the site's tile is the name alone — but it is
      // real information the server already sends, and a screen reader has the
      // room for it where a 164pt tile does not.
      accessibilityLabel={total === null ? name : `${name}, ${total} titles`}
      ringRadius={genreTile.radius}
      onPress={onPress}
      style={{ width }}
    >
      <View style={[styles.genre, { width, height, borderRadius: genreTile.radius }]}>
        {art === null ? null : (
          <ExpoImage
            source={art}
            style={StyleSheet.absoluteFill}
            contentFit="cover"
            // `object-position: 50% 0%` on the site. These stills are borrowed
            // from a title, so the top of the frame is where the faces are.
            contentPosition="top"
            recyclingKey={item.slug ?? name}
            transition={180}
            cachePolicy="memory-disk"
            accessible={false}
          />
        )}

        {/*
          Left to right, not top to bottom. The label is centred over the whole
          tile, so a vertical scrim would darken the artwork above and below it
          and leave the text itself sitting on the brightest band.
        */}
        <LinearGradient
          colors={genreTile.scrimColors}
          locations={genreTile.scrimLocations}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={StyleSheet.absoluteFill}
          pointerEvents="none"
        />

        <View style={styles.genreLabel} pointerEvents="none">
          <Text numberOfLines={2} style={styles.genreName}>
            {name}
          </Text>
        </View>
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
    // The surface shows through only where a genre has no artwork, which is a
    // genre with no published content.
    backgroundColor: colors.surface,
    overflow: 'hidden',
  },
  /* Inset to 0 and centred in both axes, which is what the site's
     `.blog-description` computes to: absolute, inset 0, flex, centred. */
  genreLabel: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.sm,
  },
  genreName: {
    fontFamily: fonts.medium,
    fontSize: genreTile.labelSize,
    lineHeight: genreTile.labelLineHeight,
    fontWeight: genreTile.labelWeight,
    color: colors.onPrimary,
    textAlign: 'center',
  },

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
