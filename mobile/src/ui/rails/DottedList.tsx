import { StyleSheet, Text, View, type StyleProp, type TextStyle, type ViewStyle } from 'react-native';

import { colors, rankedBanner } from '../theme';

/**
 * Streamit's `.movie-tag` list: a run of short strings with a round dot
 * between them and none after the last.
 *
 * Two of the daily banners' lines are this same list — the movies banner's
 * genre chips and the series banner's "August 2023 · 2 Seasons" — and on the
 * site they are one rule. Writing it twice is how one of them ends up with a
 * dot after its last item.
 *
 * **The three spacings are ratios, because the site's are ems.** Its
 * `li::after` is `0.375em` wide at `0.5625em` from the right, inside
 * `1.725em` of padding — which is why the same rule draws a 5.25pt dot beside
 * 14pt genre text and a 6.75pt dot beside 18pt meta text. Freezing either as
 * a pixel value would have made the other wrong.
 *
 * The colour is a prop for the same reason and not a shared token: the site
 * paints the genre dot in the primary blue and the series meta dot in white,
 * on the same page, and neither is a mistake to normalise away.
 */
export function DottedList({
  parts,
  fontSize,
  lineHeight,
  weight,
  tracking,
  dotColor,
  gap,
  centred = false,
  style,
}: {
  parts: readonly string[];
  fontSize: number;
  lineHeight: number;
  weight?: TextStyle['fontWeight'];
  tracking?: number;
  dotColor: string;
  /** The gap the site's flex row puts between items, on top of the padding. */
  gap: number;
  centred?: boolean;
  style?: StyleProp<ViewStyle>;
}) {
  if (parts.length === 0) return null;

  const dot = fontSize * rankedBanner.dotRatio;
  const dotRight = fontSize * rankedBanner.dotRightRatio;
  const trailing = fontSize * rankedBanner.dotGapRatio;

  const text: TextStyle = {
    color: colors.text,
    fontSize,
    lineHeight,
    ...(weight === undefined ? {} : { fontWeight: weight }),
    ...(tracking === undefined ? {} : { letterSpacing: tracking }),
  };

  return (
    <View style={[styles.row, centred && styles.centred, { gap }, style]}>
      {parts.map((part, index) => {
        const last = index === parts.length - 1;

        return (
          <View key={`${part}-${index}`} style={styles.item}>
            {/*
              The padding is on the item rather than between items because
              that is where the site puts it, and it is what reserves the
              space the absolutely positioned dot sits in.
            */}
            <Text style={[text, last ? null : { paddingRight: trailing }]}>{part}</Text>

            {last ? null : (
              <View
                pointerEvents="none"
                style={[
                  styles.dot,
                  {
                    width: dot,
                    height: dot,
                    borderRadius: dot / 2,
                    backgroundColor: dotColor,
                    right: dotRight,
                    marginTop: -dot / 2,
                  },
                ]}
              />
            )}
          </View>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center' },
  centred: { justifyContent: 'center' },
  item: { position: 'relative', justifyContent: 'center' },
  // `top: 50%` then back by half its own height, which is the site's
  // `top: 50%; transform: translateY(-50%)`.
  dot: { position: 'absolute', top: '50%' },
});
