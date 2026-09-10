import { StyleSheet, View } from 'react-native';

import { fonts, rail, topTen } from '../theme';
import { tokens } from '../tokens';
import { PosterCard, type PosterItem } from './PosterCard';
import { TextureText } from './TextureText';

/**
 * A ranked poster, for the Top 10 rails.
 *
 * The numeral is the whole design problem. Streamit does not colour it: its
 * computed `color` is `rgba(0,0,0,0)` and there is no text stroke, because the
 * letterform is filled with a texture image through `background-clip: text`
 * (`.texture-text`, fill `frontend/images/pages/texure.webp`). An app that
 * read `color` off the site would draw nothing at all — which is exactly what
 * the first token export produced, and why `asset.topTenTexture` is a token.
 *
 * How that is drawn lives in `TextureText`, which the home banner's headline
 * uses too — the site fills both from the same `texure.webp` through the same
 * `.texture-text` rule, so a second implementation here would be two things
 * that have to be changed together and will not be.
 */
export function TopTenCard({
  item,
  rank,
  width,
  height,
  onPress,
  hasTVPreferredFocus = false,
}: {
  item: PosterItem;
  rank: number;
  width: number;
  height: number;
  onPress?: (() => void) | undefined;
  hasTVPreferredFocus?: boolean;
}) {
  return (
    <View style={styles.block}>
      <PosterCard
        item={item}
        width={width}
        height={height}
        onPress={onPress}
        hasTVPreferredFocus={hasTVPreferredFocus}
      />
      {/*
        Decorative, and deliberately so. The rank is already in the reading
        order — the rail is ordered and the card announces its title — so a
        screen reader that also read "1" over every poster would be adding
        noise, not information.
      */}
      <View pointerEvents="none" style={styles.numeral} accessibilityElementsHidden importantForAccessibility="no-hide-descendants">
        <TextureNumeral value={rank} size={numeralSize(width)} />
      </View>
    </View>
  );
}

/**
 * The site draws the numeral at 60px against an 88dp poster — roughly two
 * thirds of the card. Scaling by the card keeps that proportion at every
 * width, instead of leaving a phone-sized numeral on a TV-sized poster.
 *
 * The ratio is measured rather than chosen, and both halves of it are tokens:
 * `font.size.topTenNumber` over the poster width the export read at a phone
 * viewport, which is the slide less its gutter.
 */
const REFERENCE_POSTER_WIDTH = tokens.size.posterWidth - rail.cardGap;

function numeralSize(posterWidth: number): number {
  return Math.round(posterWidth * (topTen.size / REFERENCE_POSTER_WIDTH));
}

function TextureNumeral({ value, size }: { value: number; size: number }) {
  return (
    <TextureText size={size} weight={topTen.weight} fontFamily={fonts.black} accessibilityHidden>
      {String(value)}
    </TextureText>
  );
}

const styles = StyleSheet.create({
  block: { position: 'relative' },
  /*
   * Bottom-left, hanging off the poster's edge as the site's does. `overflow`
   * is not clipped anywhere up the tree, so the part that hangs out stays
   * visible — that overhang is the effect.
   */
  numeral: { position: 'absolute', left: -4, bottom: -6 },
});
