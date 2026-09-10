import { useCallback, useEffect, useRef, useState } from 'react';
import {
  AccessibilityInfo,
  Animated,

  StyleSheet,
  Text,
  View,
  useWindowDimensions,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import {
  ArrowClockwise,
  ArrowCounterClockwise,
  CaretLeft,
  Gear,
  Pause,
  Play,
  SkipForward,
} from 'phosphor-react-native';

import { Focusable } from '../rails/Focusable';
import { fonts, player, spacing } from '../theme';
import { formatTime } from './playback';
import { SeekBar } from './SeekBar';

/** How long the controls stay up after the last interaction, while playing. */
const AUTO_HIDE_MS = 3500;

/** What ± seeks by. The site's own `<media-seek-button seconds="10">`. */
export const SEEK_STEP_MS = 10_000;

export type PlayerControlsProps = {
  title: string;
  subtitle?: string | undefined;
  positionMs: number;
  durationMs: number;
  bufferedMs: number;
  isPlaying: boolean;
  visible: boolean;
  onRequestShow: () => void;
  onRequestHide: () => void;
  onPlayPause: () => void;
  onSeek: (positionMs: number) => void;
  onSeekBy: (deltaMs: number) => void;
  onBack: () => void;
  onSettings: () => void;
  onNextEpisode?: (() => void) | undefined;
  /**
   * Hide the play/seek row, leaving the title bar and the scrubber.
   *
   * Set while the end card is up. Without it the card's own buttons render on
   * top of the play button — seen on the emulator, and it reads as two
   * controls fighting for the same place rather than as a finished film.
   */
  hideTransport?: boolean;
};

/**
 * Everything a viewer touches.
 *
 * React Native rather than Kotlin, on purpose: these have to wear the design
 * tokens captured off the website's watch page, they have to be reachable by a
 * d-pad through the app's own `Focusable`, and they have to be changeable
 * without a native rebuild.
 *
 * **The scrim is a gradient, not a blur, and the tokens decided that.**
 * `color.playerControlsBg` captured `transparent` and
 * `effect.playerControlsBackdrop` captured `none`, while `effect.playerOverlay`
 * captured a real `linear-gradient`. So the site darkens behind its controls
 * with a scrim and nothing else. Reaching for `expo-blur` would have been a
 * native dependency added to imitate something the website does not do — the
 * exact decision §6.4 of the plan says to settle with evidence.
 */
export function PlayerControls({
  title,
  subtitle,
  positionMs,
  durationMs,
  bufferedMs,
  isPlaying,
  visible,
  onRequestShow,
  onRequestHide,
  onPlayPause,
  onSeek,
  onSeekBy,
  onBack,
  onSettings,
  onNextEpisode,
  hideTransport = false,
}: PlayerControlsProps) {
  const { width } = useWindowDimensions();
  const [scrubMs, setScrubMs] = useState<number | null>(null);
  const [scrubbing, setScrubbing] = useState(false);
  const [reduceMotion, setReduceMotion] = useState(false);

  /*
   * `useState` with a lazy initialiser, not `useRef(...).current`.
   *
   * Both create the value once, but reading `.current` during render is what
   * `react-hooks/refs` forbids — and the rule is right in general, even though
   * an Animated.Value is the case where it is harmless. The lazy initialiser
   * expresses "create this once and never replace it" without touching a ref
   * during render at all.
   */
  const [opacity] = useState(() => new Animated.Value(visible ? 1 : 0));
  const hideTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  /*
   * Reduced motion is read AND subscribed to. A viewer can turn it on while
   * the app is open, and `apple-design` is explicit that reduced motion means
   * a gentler equivalent rather than no feedback — so the controls still
   * appear and disappear, they simply do it without a fade.
   */
  useEffect(() => {
    let alive = true;
    void AccessibilityInfo.isReduceMotionEnabled().then((enabled) => {
      if (alive) setReduceMotion(enabled);
    });
    const sub = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduceMotion);
    return () => {
      alive = false;
      sub.remove();
    };
  }, []);

  /*
   * Animate from the CURRENT on-screen value, never from the target.
   *
   * `Animated.timing` on an existing value does exactly this: interrupting a
   * half-finished fade continues from where it actually is rather than
   * snapping to 0 or 1 first. That is the interruptibility rule, and it is
   * the reason the controls can be dismissed mid-appearance without a jump.
   */
  useEffect(() => {
    Animated.timing(opacity, {
      toValue: visible ? 1 : 0,
      duration: reduceMotion ? 0 : 180,
      useNativeDriver: true,
    }).start();
  }, [visible, reduceMotion, opacity]);

  /*
   * Auto-hide, and it must not fire while a finger is on the seek bar. The
   * controls vanishing mid-scrub takes the bar out from under the gesture.
   */
  useEffect(() => {
    if (hideTimer.current !== null) clearTimeout(hideTimer.current);

    if (!visible || !isPlaying || scrubbing) return undefined;

    hideTimer.current = setTimeout(onRequestHide, AUTO_HIDE_MS);

    return () => {
      if (hideTimer.current !== null) clearTimeout(hideTimer.current);
    };
  }, [visible, isPlaying, scrubbing, onRequestHide, positionMs]);

  const keepAwake = useCallback(() => {
    onRequestShow();
  }, [onRequestShow]);

  const shownMs = scrubMs ?? positionMs;

  /*
   * The duration's colour follows the site's container query rather than the
   * device. `player.css` dims it to 60% only past 42rem, and the app's player
   * is fullscreen — so a landscape phone is in the wide state and a portrait
   * one is not, which is precisely what the website does at the same widths.
   */
  const durationColor = width >= player.timeMutedFromWidth ? player.timeMutedColor : player.timeColor;

  return (
    <Animated.View
      style={[StyleSheet.absoluteFill, { opacity }]}
      pointerEvents={visible ? 'auto' : 'none'}
    >
      {/* Top scrim, reversed, so the title has the same contrast as the bar. */}
      <LinearGradient
        colors={[player.scrimTo, player.scrimFrom]}
        style={styles.topScrim}
        pointerEvents="none"
      />
      <LinearGradient
        colors={[player.scrimFrom, player.scrimVia, player.scrimTo]}
        style={styles.bottomScrim}
        pointerEvents="none"
      />

      <View style={styles.top}>
        <IconButton label="Back" onPress={onBack} icon={CaretLeft} />
        <View style={styles.titleBlock}>
          <Text numberOfLines={1} style={styles.title}>
            {title}
          </Text>
          {subtitle === undefined || subtitle === '' ? null : (
            <Text numberOfLines={1} style={styles.subtitle}>
              {subtitle}
            </Text>
          )}
        </View>
        <IconButton label="Settings" onPress={onSettings} icon={Gear} />
      </View>

      <View style={styles.centre} pointerEvents={hideTransport ? 'none' : 'auto'}>
        {hideTransport ? null : (
          <>
        <IconButton
          label="Back ten seconds"
          onPress={() => {
            keepAwake();
            onSeekBy(-SEEK_STEP_MS);
          }}
          icon={ArrowCounterClockwise}
          size={44}
        />
        <IconButton
          label={isPlaying ? 'Pause' : 'Play'}
          onPress={() => {
            keepAwake();
            onPlayPause();
          }}
          icon={isPlaying ? Pause : Play}
          size={64}
          weight="fill"
          primary
          hasTVPreferredFocus
        />
        <IconButton
          label="Forward ten seconds"
          onPress={() => {
            keepAwake();
            onSeekBy(SEEK_STEP_MS);
          }}
          icon={ArrowClockwise}
          size={44}
        />
          </>
        )}
      </View>

      <View style={styles.bottom}>
        <Text style={[styles.time, styles.timeCurrent]}>{formatTime(shownMs)}</Text>
        <SeekBar
          positionMs={positionMs}
          durationMs={durationMs}
          bufferedMs={bufferedMs}
          onScrub={setScrubMs}
          onScrubbingChange={(active) => {
            setScrubbing(active);
            if (!active) setScrubMs(null);
            if (active) onRequestShow();
          }}
          onSeek={onSeek}
        />
        <Text style={[styles.time, { color: durationColor }]}>{formatTime(durationMs)}</Text>
        {onNextEpisode === undefined ? null : (
          <IconButton label="Next episode" onPress={onNextEpisode} icon={SkipForward} />
        )}
      </View>
    </Animated.View>
  );
}

function IconButton({
  label,
  onPress,
  icon: Icon,
  size = player.buttonSize,
  weight = 'regular',
  primary = false,
  hasTVPreferredFocus = false,
}: {
  label: string;
  onPress: () => void;
  icon: typeof Play;
  size?: number;
  weight?: 'regular' | 'fill';
  primary?: boolean;
  hasTVPreferredFocus?: boolean;
}) {
  return (
    <Focusable
      accessibilityLabel={label}
      onPress={onPress}
      ringRadius={size / 2}
      hasTVPreferredFocus={hasTVPreferredFocus}
    >
      <View
        style={[
          styles.iconButton,
          { width: size, height: size, borderRadius: size / 2 },
          primary && styles.iconButtonPrimary,
        ]}
      >
        <Icon
          size={Math.round(size * (primary ? 0.42 : 0.55))}
          color={primary ? player.buttonPrimaryFg : player.controlsFg}
          weight={weight}
        />
      </View>
    </Focusable>
  );
}

const styles = StyleSheet.create({
  topScrim: { position: 'absolute', top: 0, left: 0, right: 0, height: 120 },
  bottomScrim: { position: 'absolute', bottom: 0, left: 0, right: 0, height: 160 },

  top: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingTop: spacing.md,
  },
  titleBlock: { flex: 1 },
  title: {
    color: player.controlsFg,
    fontFamily: fonts.medium,
    fontSize: 16,
  },
  subtitle: {
    color: player.timeMutedColor,
    fontFamily: fonts.regular,
    fontSize: 13,
    marginTop: 1,
  },

  centre: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.xl,
  },

  bottom: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.md,
    paddingBottom: spacing.md,
  },
  time: {
    fontFamily: fonts.regular,
    fontSize: player.timeSize,
    // Tabular figures are not available here, so a fixed width stops the seek
    // bar shifting sideways every time the seconds digit changes width.
    minWidth: 48,
  },
  timeCurrent: { color: player.timeColor, textAlign: 'right' },

  iconButton: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: player.buttonBg,
  },
  iconButtonPrimary: { backgroundColor: player.buttonPrimaryBg },
});

export { AUTO_HIDE_MS };
