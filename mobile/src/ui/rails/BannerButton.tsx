import { StyleSheet, Text, View } from 'react-native';
import { Play } from 'phosphor-react-native';

import { banner, colors } from '../theme';
import { Focusable } from './Focusable';

/**
 * The call to action on a banner: Streamit's `.btn-primary`, not the app's
 * pill.
 *
 * These really are two controls on the site rather than one drawn twice. The
 * sign-in button is a full-width pill and `ui/components.tsx` draws it; this
 * one hugs its label, squares off to a 7px corner and carries a filled play
 * triangle, which is `components/widgets/custom-button.blade.php`. Widening
 * the pill with an `icon` prop would have produced a control that matches
 * neither.
 *
 * It lives here rather than inside the hero because all three of the
 * website's banners use it — the hero and the Top 10 movie slider say "Play
 * Now", the series slider says "Stream Now" — so the label is a prop and the
 * next banner does not copy this file.
 */
export function BannerButton({
  label,
  onPress,
  align = 'start',
  hasTVPreferredFocus = false,
}: {
  label: string;
  onPress: () => void;
  /**
   * Which edge the button hugs.
   *
   * A prop because the site's three banners do not agree: the hero and the
   * series slider are left-aligned blocks, and the Top 10 movies slider
   * centres every line of its content below `lg`. It is alignment, not width
   * — the button hugs its label either way.
   */
  align?: 'start' | 'center';
  hasTVPreferredFocus?: boolean;
}) {
  return (
    <Focusable
      accessibilityLabel={label}
      accessibilityHint="Opens details"
      ringRadius={banner.buttonRadius}
      onPress={onPress}
      hasTVPreferredFocus={hasTVPreferredFocus}
      style={align === 'center' ? styles.wrapCentre : styles.wrap}
    >
      <View style={styles.button}>
        <Text style={styles.label}>{label}</Text>
        {/*
          Decorative: the label already says what the control does, and a
          screen reader announcing "Play Now, play" is noise.
        */}
        <Play size={banner.buttonIconSize} color={colors.onPrimary} weight="fill" />
      </View>
    </Focusable>
  );
}

const styles = StyleSheet.create({
  // The site's button hugs its label rather than filling the column.
  wrap: { alignSelf: 'flex-start' },
  wrapCentre: { alignSelf: 'center' },
  button: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: banner.buttonGap,
    backgroundColor: colors.primary,
    borderRadius: banner.buttonRadius,
    paddingVertical: banner.buttonPadY,
    paddingHorizontal: banner.buttonPadX,
  },
  label: {
    color: colors.onPrimary,
    fontSize: banner.buttonFontSize,
    lineHeight: banner.buttonLineHeight,
    fontWeight: '600',
    letterSpacing: banner.buttonTracking,
  },
});
