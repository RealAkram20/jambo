import { useId } from 'react';
import { StyleSheet, View } from 'react-native';
import Svg, { Defs, Image as SvgImage, Pattern, Text as SvgText } from 'react-native-svg';

import { fonts, rail, topTen } from '../theme';
import { tokens } from '../tokens';
import { PosterCard, type PosterItem } from './PosterCard';

const TEXTURE = require('../../../assets/streamit/top-ten-texture.webp');

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
 * React Native has no `background-clip: text`. The equivalent that needs no
 * new dependency is SVG: `react-native-svg` is already installed for the icon
 * set, and a `<Pattern>` holding the same texture, used as a text `fill`, is
 * the same operation the browser performs. The alternative was
 * `@react-native-masked-view/masked-view`, a native module — and a new native
 * module means `expo prebuild --clean` and a full rebuild, which this buys
 * nothing over an SVG.
 *
 * The texture file is Streamit's own and is copied out of
 * `public/frontend/images/pages/` rather than downloaded, so it is under
 * source control with the rest of the design.
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
  // One pattern per instance. Several Top 10 rails render on the home screen
  // at once, and a duplicated SVG id makes every numeral after the first
  // resolve `url(#…)` to the wrong pattern. React's useId contains characters
  // (« » :) that are not valid in an SVG fragment identifier.
  const id = `tt${useId().replace(/[^a-zA-Z0-9]/g, '')}`;

  const label = String(value);
  // Roboto Black at this size is about 0.62em per digit, plus the overhang the
  // texture needs so the fill reaches the edges of the glyph.
  const boxWidth = Math.ceil(size * 0.66 * label.length);
  const boxHeight = Math.ceil(size * 1.05);

  return (
    <Svg width={boxWidth} height={boxHeight}>
      <Defs>
        <Pattern id={id} patternUnits="userSpaceOnUse" width={boxWidth} height={boxHeight}>
          <SvgImage
            href={TEXTURE}
            width={boxWidth}
            height={boxHeight}
            preserveAspectRatio="xMidYMid slice"
          />
        </Pattern>
      </Defs>
      <SvgText
        x={0}
        // Baseline, not top: SVG text hangs from its baseline, so the glyph
        // needs the box height less its descender to sit inside the box.
        y={boxHeight * 0.86}
        fill={`url(#${id})`}
        fontFamily={fonts.black}
        fontSize={size}
        fontWeight={String(topTen.weight)}
      >
        {label}
      </SvgText>
    </Svg>
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
