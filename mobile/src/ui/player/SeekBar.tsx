import { useState } from 'react';
import { StyleSheet, View, type GestureResponderEvent, type LayoutChangeEvent } from 'react-native';

import { player } from '../theme';
import { progressFraction, seekTargetMs } from './playback';

/**
 * The scrubber.
 *
 * Three rules from `apple-design` decide how this behaves, and each of them
 * rules out the easy implementation:
 *
 * 1. **Feedback is continuous during the gesture, not at the end.** The fill
 *    and the time display follow the finger the whole way; the actual seek is
 *    committed on release. A bar that only moves when the finger lifts is the
 *    failure that rule exists to name.
 * 2. **Respond on touch-down.** The thumb grows the instant it is touched,
 *    before any movement, and touch-down is itself a seek — tapping the middle
 *    of the bar jumps there, as the site's own time slider does.
 * 3. **Never lock out input.** Nothing here waits for the player.
 *
 * And one rule from this project, the same shape as the two input bugs slice
 * 2b hit: **an incoming update must never overwrite what the viewer is
 * doing.** The player reports its position four times a second, and a bar that
 * rendered those while a finger was on it would fight the finger and jump back
 * on every tick. `dragMs` takes precedence while scrubbing, exactly as the
 * profile form must not be re-seeded from a background refetch mid-typing.
 *
 * **The responder props are used directly rather than `PanResponder`.** The
 * first cut built a `PanResponder` in a `useMemo` and reached the props
 * through a set of latest-value refs, because a memoised handler closing over
 * `width` would have kept the zero from first render and sent every seek to
 * position 0. That works, but it needs five refs and an effect to maintain
 * them, and `react-hooks/refs` objects to it with reason. These props are read
 * by the responder system at gesture time, so ordinary callbacks over current
 * props are correct, cannot go stale, and delete the whole apparatus.
 */
export type SeekBarProps = {
  positionMs: number;
  durationMs: number;
  bufferedMs: number;
  /** Fires continuously while dragging, so the clock can follow the finger. */
  onScrub?: ((positionMs: number) => void) | undefined;
  /** Fires once, on release. This is the only one that moves the player. */
  onSeek: (positionMs: number) => void;
  onScrubbingChange?: ((scrubbing: boolean) => void) | undefined;
};

export function SeekBar({
  positionMs,
  durationMs,
  bufferedMs,
  onScrub,
  onSeek,
  onScrubbingChange,
}: SeekBarProps) {
  const [width, setWidth] = useState(0);
  const [dragMs, setDragMs] = useState<number | null>(null);

  const positionFrom = (event: GestureResponderEvent): number =>
    seekTargetMs(event.nativeEvent.locationX, width, durationMs);

  const shown = dragMs ?? positionMs;
  const fraction = progressFraction(shown, durationMs);
  const buffered = progressFraction(bufferedMs, durationMs);
  const scrubbing = dragMs !== null;
  const thumbSize = scrubbing ? player.thumbSize : player.thumbSize * 0.7;

  return (
    <View
      style={styles.hitArea}
      onLayout={(event: LayoutChangeEvent) => setWidth(event.nativeEvent.layout.width)}
      accessibilityRole="adjustable"
      accessibilityLabel="Seek"
      accessibilityValue={{ min: 0, max: Math.max(1, durationMs), now: Math.round(shown) }}
      onStartShouldSetResponder={() => true}
      onMoveShouldSetResponder={() => true}
      onResponderGrant={(event) => {
        const target = positionFrom(event);
        setDragMs(target);
        onScrubbingChange?.(true);
        onScrub?.(target);
      }}
      onResponderMove={(event) => {
        const target = positionFrom(event);
        setDragMs(target);
        onScrub?.(target);
      }}
      onResponderRelease={() => {
        if (dragMs !== null) onSeek(dragMs);
        setDragMs(null);
        onScrubbingChange?.(false);
      }}
      onResponderTerminate={() => {
        // A gesture taken over by a parent. Abandon the scrub rather than
        // committing a seek the viewer did not finish.
        setDragMs(null);
        onScrubbingChange?.(false);
      }}
    >
      {/*
        `pointerEvents="none"` on everything inside is load-bearing, not
        tidiness: `locationX` is reported relative to the view the touch landed
        on, so a finger that happened to start on the thumb would report a
        coordinate in the thumb's 12dp space and seek to the very beginning of
        the film. With the children transparent to touch, the outer view is
        always the target and the coordinate is always in the track's space.
      */}
      <View style={styles.track} pointerEvents="none">
        <View style={[styles.buffer, { width: `${buffered * 100}%` }]} />
        <View style={[styles.fill, { width: `${fraction * 100}%` }]} />
      </View>

      {/*
        The thumb sits ON the end of the fill, offset by half its own width. It
        grows on touch-down: the site's thumb is invisible until hover or
        focus, and a phone has neither, so being touched is the equivalent
        state.
      */}
      <View
        pointerEvents="none"
        style={[
          styles.thumb,
          {
            left: `${fraction * 100}%`,
            marginLeft: -thumbSize / 2,
            width: thumbSize,
            height: thumbSize,
            borderRadius: player.thumbSize,
          },
        ]}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  /*
   * The 32dp row the site gives `.media-slider`, not the 3dp line it draws.
   * A touch target the height of the visible bar is a bar nobody can grab.
   */
  hitArea: {
    height: player.hitArea,
    justifyContent: 'center',
    flex: 1,
  },
  track: {
    height: player.trackHeight,
    borderRadius: player.trackRadius,
    backgroundColor: player.trackBg,
    overflow: 'hidden',
  },
  buffer: {
    position: 'absolute',
    left: 0,
    top: 0,
    bottom: 0,
    backgroundColor: player.trackBuffer,
  },
  fill: {
    position: 'absolute',
    left: 0,
    top: 0,
    bottom: 0,
    backgroundColor: player.trackFill,
  },
  thumb: {
    position: 'absolute',
    backgroundColor: player.thumbColor,
  },
});
