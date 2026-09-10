import { useCallback, useId, useState } from 'react';
import {
  StyleSheet,
  Text,
  View,
  type LayoutChangeEvent,
  type TextLayoutEvent,
} from 'react-native';
import Svg, { Defs, Image as SvgImage, Pattern, Text as SvgText } from 'react-native-svg';

const TEXTURE = require('../../../assets/streamit/texture-text.webp');

/**
 * Streamit's `.texture-text`, which is a headline filled with an image.
 *
 * The site's hero title, its trending headline and its Top 10 numerals are all
 * the same effect: `color: transparent` plus `background-clip: text` over
 * `frontend/images/pages/texure.webp`. Reading `color` off the site gets
 * `rgba(0,0,0,0)` and draws nothing, which is why this is a component and not
 * a style.
 *
 * React Native has no `background-clip: text`. `react-native-svg` is already
 * installed for the icon set, and a `<Pattern>` used as a text `fill` is the
 * same operation the browser performs — where
 * `@react-native-masked-view/masked-view` would be a new native module, and so
 * an `expo prebuild --clean` and a full rebuild, for no gain.
 *
 * **The truncation is measured, not estimated.** SVG has no ellipsis and no
 * concept of a line box, so the text is first laid out by a real, invisible
 * `<Text numberOfLines>` at the same font, size and tracking; RN reports back
 * the exact string it would have drawn, ellipsis included, along with the
 * baseline. That string is what the SVG draws. Estimating a character width
 * from the font size — the shortcut the Top 10 numeral can afford because
 * digits are tabular — puts "Mortal Kombat 2 Vj Junior" and "IIII" at the same
 * width, and titles are the one thing here that is never tabular.
 *
 * Until that first layout there is nothing to draw, so the invisible text is
 * all that renders for one frame. It is invisible rather than absent because
 * an absent one never lays out.
 */
export function TextureText({
  children,
  size,
  weight,
  tracking = 0,
  fontFamily,
  numberOfLines = 1,
  align = 'left',
  accessibilityHidden = false,
}: {
  children: string;
  size: number;
  weight: number;
  tracking?: number;
  fontFamily: string;
  numberOfLines?: number;
  align?: 'left' | 'center';
  /** For a headline the surrounding block already announces. */
  accessibilityHidden?: boolean;
}) {
  const [lines, setLines] = useState<MeasuredLine[] | null>(null);
  const [boxWidth, setBoxWidth] = useState<number | null>(null);

  /**
   * The width the line box actually got.
   *
   * A belt to `onTextLayout`'s braces. The whole design assumes RN reports the
   * *truncated* line back, and if some platform or version reports the full
   * string instead, the SVG would be sized to a string that does not fit and
   * would paint out past the slide. Clamping to the box the text was laid out
   * in makes that failure a clipped tail rather than a headline lying across
   * the artwork. When truncation works, the measured line is narrower than the
   * box and this clamp does nothing at all.
   */
  const onLayout = useCallback((event: LayoutChangeEvent) => {
    const next = event.nativeEvent.layout.width;
    setBoxWidth((previous) => (previous === next ? previous : next));
  }, []);

  const onTextLayout = useCallback((event: TextLayoutEvent) => {
    const measured = event.nativeEvent.lines.map((line) => ({
      text: line.text,
      width: line.width,
      height: line.height,
      ascender: line.ascender,
    }));

    setLines((previous) => (sameLines(previous, measured) ? previous : measured));
  }, []);

  const font = { fontFamily, fontSize: size, letterSpacing: tracking };

  return (
    <View
      style={[styles.block, align === 'center' && styles.center]}
      onLayout={onLayout}
      accessible={!accessibilityHidden}
      accessibilityRole={accessibilityHidden ? undefined : 'header'}
      {...(accessibilityHidden
        ? { accessibilityElementsHidden: true, importantForAccessibility: 'no-hide-descendants' as const }
        : { accessibilityLabel: children })}
    >
      {/*
        The measuring pass, and also the thing that reserves the space.

        `opacity: 0` rather than `display: none` or a conditional, because a
        text node that is not laid out reports no lines and the effect never
        starts. It stays mounted so a rotation or a font-scale change
        re-measures on its own.

        Keeping it in normal flow — with the SVG absolutely positioned over it
        — is what makes the headline behave like text to everything around it:
        the caption block below sits where the real line box ends, and the
        wrapping is RN's rather than something computed here. It also stops
        the render loop the other way round would have, where the SVG's size
        drives the layout that re-measures the SVG.
      */}
      <Text
        style={[font, styles.measure]}
        numberOfLines={numberOfLines}
        ellipsizeMode="tail"
        onTextLayout={onTextLayout}
        accessible={false}
        importantForAccessibility="no-hide-descendants"
      >
        {children}
      </Text>

      {lines === null ? null : (
        <View
          style={[styles.paint, align === 'center' && styles.paintCenter]}
          pointerEvents="none"
        >
          <TexturedLines
            lines={lines}
            size={size}
            weight={weight}
            tracking={tracking}
            fontFamily={fontFamily}
            align={align}
            maxWidth={boxWidth}
          />
        </View>
      )}
    </View>
  );
}

type MeasuredLine = { text: string; width: number; height: number; ascender: number };

function TexturedLines({
  lines,
  size,
  weight,
  tracking,
  fontFamily,
  align,
  maxWidth,
}: {
  lines: MeasuredLine[];
  size: number;
  weight: number;
  tracking: number;
  fontFamily: string;
  align: 'left' | 'center';
  /** The laid-out line box. See `onLayout` above for why this is a ceiling. */
  maxWidth: number | null;
}) {
  // One pattern per instance. Several textured headlines can be on screen at
  // once — the banner keeps its neighbouring slides mounted — and a duplicated
  // SVG id makes every one after the first resolve `url(#…)` to the wrong
  // pattern. useId contains characters (« » :) that are not valid in an SVG
  // fragment identifier.
  const id = `tx${useId().replace(/[^a-zA-Z0-9]/g, '')}`;

  // A hair of headroom on each axis: the measured width excludes the trailing
  // side bearing of the last glyph, and an italic or a heavily tracked face
  // can paint a pixel past it. Clipping a headline's last letter is the kind
  // of fault that only shows up on one title.
  const drawn = Math.ceil(Math.max(...lines.map((line) => line.width)) + size * 0.08);
  const width = maxWidth === null ? drawn : Math.min(drawn, Math.ceil(maxWidth));
  const height = Math.ceil(lines.reduce((total, line) => total + line.height, 0));

  return (
    <Svg width={width} height={height}>
      <Defs>
        <Pattern id={id} patternUnits="userSpaceOnUse" width={width} height={height}>
          {/*
            `slice` rather than `meet`: the browser's `background-size: cover`
            crops the texture to the box, and letting it letterbox instead
            would leave the top and bottom of the glyphs unfilled.
          */}
          <SvgImage
            href={TEXTURE}
            width={width}
            height={height}
            preserveAspectRatio="xMidYMid slice"
          />
        </Pattern>
      </Defs>

      {lines.map((line, index) => (
        <SvgText
          key={`${index}:${line.text}`}
          x={align === 'center' ? width / 2 : 0}
          textAnchor={align === 'center' ? 'middle' : 'start'}
          // SVG text hangs from its baseline, and RN reports where that
          // baseline sat in the line box it measured. Using the box height
          // instead would drop every glyph by its descender.
          y={lines.slice(0, index).reduce((total, l) => total + l.height, 0) + line.ascender}
          fill={`url(#${id})`}
          fontFamily={fontFamily}
          fontSize={size}
          fontWeight={String(weight)}
          letterSpacing={tracking}
        >
          {line.text}
        </SvgText>
      ))}
    </Svg>
  );
}

/**
 * Whether a re-measure actually changed anything.
 *
 * `onTextLayout` fires on every layout pass, and setting state from it
 * unconditionally is a render loop: the SVG's size changes the parent's
 * layout, which re-measures, which sets state again. Comparing the value is
 * what stops it, and it has to compare the text as well as the width — a
 * title that truncates differently at the same width is a different string.
 */
function sameLines(a: MeasuredLine[] | null, b: MeasuredLine[]): boolean {
  if (a === null || a.length !== b.length) return false;

  return a.every(
    (line, i) =>
      line.text === b[i]?.text &&
      line.width === b[i]?.width &&
      line.height === b[i]?.height &&
      line.ascender === b[i]?.ascender,
  );
}

const styles = StyleSheet.create({
  block: { position: 'relative' },
  // Laid out, painted transparent. See the comment at its use.
  measure: { opacity: 0 },
  center: { alignItems: 'center' },
  /*
   * Over the measured text, taking no space of its own. `bottom`/`right` are
   * left off so the SVG keeps its intrinsic size rather than being stretched
   * to the box — the pattern is sized to the glyphs, and stretching it would
   * scale the texture with the headline's length.
   */
  paint: { position: 'absolute', left: 0, top: 0 },
  /* Centred over the line box rather than pinned to its left edge. */
  paintCenter: { right: 0, alignItems: 'center' },
});
