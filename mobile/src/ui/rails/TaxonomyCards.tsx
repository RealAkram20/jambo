import { StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';

import { card, colors, fonts, genreTile, personTile, spacing, typography } from '../theme';
import { imageUrl } from '../media';
import type {
  GenreCard as GenreCardData,
  PersonCard as PersonCardData,
  VjCard as VjCardData,
} from '../../api/catalogue';
import { Focusable } from './Focusable';

/**
 * The rails that are not titles: genres, VJs and personalities.
 *
 * They share a file because they share a shape — a label, sometimes a picture,
 * one press target — and splitting them into three modules that differ by two
 * lines each is how a kit stops being a kit.
 *
 * **There are two cards here, not three, and that is the website's doing.**
 * `sections/vjs.blade.php` includes `cards/card-genres-grid` — the same partial
 * the genres rail includes — so a VJ on the site IS a genre tile: a 5:3 still
 * with the name centred over it. The app had a third card of its own for VJs,
 * a 16:9 still with the name and a title count underneath, and Rio saw the two
 * side by side on 2026-09-10: *"fix these sections"*. `VjCard` is now the tile,
 * so the two surfaces cannot drift.
 *
 * Counts are shown only when the server sends them. `movies_count` and
 * `shows_count` are nullable in the contract, and a card that renders "0
 * movies" for an unknown count states something false about the catalogue.
 */

/**
 * The genre tile draws genres and VJs alike, because the site's two sections
 * include one partial. Both carry a slug, a name, an image and nullable
 * counts, so this is one shape rather than a cast at each call site.
 */
export type GenreItem = GenreCardData | VjCardData;
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
  const total = countsOf(item);
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
 * A VJ, which on this product is a genre tile.
 *
 * **It is `GenreCard` under another name, deliberately.** The site's VJs
 * section includes `cards/card-genres-grid`, the genres card, so the two rails
 * are one component there and are one component here. It is kept as a named
 * export rather than the home screen calling `GenreCard` directly, because the
 * two rails mean different things and a future divergence should be a change
 * to this function rather than a search through call sites.
 *
 * What went when it stopped being its own card: a 16:9 crop where the site is
 * 5:3, a name printed under the image where the site centres it over the art,
 * and a **"N titles" line the site does not draw at all**. The count is not
 * lost — `GenreCard` still announces it to a screen reader, which has room a
 * 164pt tile does not.
 */
export function VjCard(props: {
  item: PersonLikeItem;
  width: number;
  onPress?: (() => void) | undefined;
}) {
  return <GenreCard {...props} item={props.item as GenreItem} />;
}

/**
 * A cast member or personality: a portrait and a name below it.
 *
 * **A rounded rectangle, not a circle**, which is the correction Rio asked for
 * on 2026-09-10. `cards/personality-card.blade.php` renders `rounded-3` on an
 * image the stylesheet gives `aspect-ratio: 1 / 1.3`; the app drew a round
 * portrait, which is what a streaming app's cast row usually looks like and is
 * not what this one looks like. Measured at 165 x 214.5 with a 16pt gap under
 * it, and the name at 14px/500 rather than the app's caption size.
 *
 * Every number is in `theme.personTile` with the selector it came from.
 */
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
  const height = Math.round(width / personTile.aspect);

  return (
    <Focusable
      accessibilityLabel={name}
      ringRadius={personTile.radius}
      onPress={onPress}
      style={{ width }}
    >
      <ExpoImage
        source={imageUrl(item.image_url, width)}
        style={[styles.portrait, { width, height, borderRadius: personTile.radius }]}
        contentFit="cover"
        // `object-position: 50% 0%` on the site, and it matters more here than
        // on a still: these are people, and a centre crop of a 1/1.3 portrait
        // takes the top off a head.
        contentPosition="top"
        recyclingKey={item.slug ?? name}
        transition={120}
        cachePolicy="memory-disk"
        accessible={false}
      />
      <Text numberOfLines={2} style={styles.personName}>
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
 * The same question for a card that may or may not carry counts.
 *
 * The parameter is the SHAPE rather than the union, because three card types
 * flow through here and only two of them have the fields — a personality
 * carries no counts at all. Typing it structurally lets each caller pass its
 * own card without a cast, and a cast is what would let a renamed field
 * through silently.
 */
function countsOf(item: { movies_count?: number | null; shows_count?: number | null }): number | null {
  return countOf(item.movies_count, item.shows_count);
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

  portrait: { backgroundColor: colors.surface },

  /* `.cast-title`: 14px/500, centred, white, 16pt under the image. */
  personName: {
    ...typography.caption,
    fontFamily: fonts.medium,
    fontSize: personTile.nameSize,
    fontWeight: personTile.nameWeight,
    color: card.titleColor,
    marginTop: personTile.gapBelow,
    textAlign: 'center',
  },
});
