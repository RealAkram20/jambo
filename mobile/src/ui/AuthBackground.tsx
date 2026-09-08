import { StyleSheet, View } from 'react-native';
import Svg, { Defs, Ellipse, Path, RadialGradient, Stop } from 'react-native-svg';

import { auth, colors } from './theme';

/**
 * The ambient glow behind the sign-in card.
 *
 * SVG radial gradients rather than blurred `View`s, for two reasons that both
 * show up on the target handset. A soft blob made from a rounded View needs a
 * real blur to look like anything but a circle, and blur on Android is either
 * unavailable or expensive; a radial gradient is drawn once by the GPU and
 * costs nothing to keep on screen. It also scales to any viewport without
 * resampling, which a background image would not — this same screen has to sit
 * behind a 5.5" phone and a 10" tablet.
 *
 * `pointerEvents="none"` so none of this can ever intercept a tap meant for
 * the form: it is decoration, and decoration that swallows touches is the kind
 * of bug that only reproduces on the device.
 */
export function AuthBackground() {
  return (
    <View style={StyleSheet.absoluteFill} pointerEvents="none">
      <Svg width="100%" height="100%" style={StyleSheet.absoluteFill}>
        <Defs>
          <RadialGradient id="glowLeft" cx="50%" cy="50%" r="50%">
            <Stop offset="0" stopColor={colors.primary} stopOpacity={auth.glowStrong} />
            <Stop offset="1" stopColor={colors.primary} stopOpacity="0" />
          </RadialGradient>
          <RadialGradient id="glowRight" cx="50%" cy="50%" r="50%">
            <Stop offset="0" stopColor={colors.primary} stopOpacity={auth.glowSoft} />
            <Stop offset="1" stopColor={colors.primary} stopOpacity="0" />
          </RadialGradient>
        </Defs>

        {/* Off the left edge, high — the light source the card sits under. */}
        <Ellipse cx="4%" cy="26%" rx="62%" ry="30%" fill="url(#glowLeft)" />
        {/* Bottom right, weaker, so the composition is not symmetrical. */}
        <Ellipse cx="98%" cy="84%" rx="58%" ry="28%" fill="url(#glowRight)" />
        {/* A low wash behind the card itself, lifting it off pure black. */}
        <Ellipse cx="50%" cy="55%" rx="70%" ry="34%" fill="url(#glowRight)" />
      </Svg>
    </View>
  );
}

/**
 * The play mark that sits over the card's top-right corner.
 *
 * Drawn rather than imported: it is a watermark at low opacity, and a shape
 * this simple as an SVG path is a few bytes against an image asset that would
 * need its own export, its own resolution decision and its own place in the
 * branding pipeline.
 */
export function PlayWatermark({ size = 112 }: { size?: number }) {
  return (
    <Svg width={size} height={size} viewBox="0 0 100 100">
      {/*
        A triangle with genuinely round corners, not a sharp one.
        `strokeLinejoin="round"` with a thick stroke in the fill colour rounds
        the joins without hand-writing three arc segments — the first version
        used a sharp path and read as stray line-art crossing the card rather
        than as a play mark tucked into its corner.
      */}
      {/*
        One flat shape at a single opacity, not a fill plus a separate stroke.
        Giving the two different alphas made the rounded edge darker than the
        middle, so it read as an outlined triangle rather than the soft filled
        mark in the design. `opacity` on the element composites the whole thing
        once instead.
      */}
      <Path
        d="M32 24 L74 50 L32 76 Z"
        fill={colors.primary}
        stroke={colors.primary}
        strokeWidth={18}
        strokeLinejoin="round"
        opacity={auth.watermarkFill}
      />
    </Svg>
  );
}
