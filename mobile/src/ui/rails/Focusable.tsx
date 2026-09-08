import React, { useCallback, useState } from 'react';
import { Platform, Pressable, StyleSheet, View, type ViewStyle } from 'react-native';

import { focus, radius } from '../theme';

/**
 * Anything a viewer can act on, with a focus state that is visible.
 *
 * **Every interactive element in the app goes through this**, including in the
 * phone build, and that is the point. ADR-0002 commits to one codebase for
 * phone, tablet and TV, and a focus state that exists only in the TV variant
 * is a focus state nobody looks at until Phase 4 — by which time every screen
 * has to be revisited. Drawing it now costs a border and makes Phase 4 a
 * configuration change, which is what the brief asks for.
 *
 * The ring colour is `colors.borderFocus`, the same primary blue the website
 * puts on a focused form field. The app's focus state is the site's focus
 * state rather than a new invention.
 *
 * The ring sits *outside* the content rather than inset, so it never covers a
 * poster's artwork and never changes the layout when it appears — a ring that
 * reflows the rail on every d-pad press is worse than no ring.
 */
export type FocusableProps = {
  children: React.ReactNode;
  onPress?: (() => void) | undefined;
  onLongPress?: (() => void) | undefined;
  accessibilityLabel: string;
  accessibilityHint?: string | undefined;
  accessibilityRole?: 'button' | 'link' | 'imagebutton';
  /** Corner radius of the ring. Match the thing being ringed. */
  ringRadius?: number;
  style?: ViewStyle | undefined;
  disabled?: boolean;
  /**
   * Ask for initial focus when the screen mounts. On a remote this decides
   * where the d-pad starts; on a phone it does nothing.
   */
  hasTVPreferredFocus?: boolean;
  /** Reported so a rail can remember which card the viewer was last on. */
  onFocusChange?: ((focused: boolean) => void) | undefined;
};

export function Focusable({
  children,
  onPress,
  onLongPress,
  accessibilityLabel,
  accessibilityHint,
  accessibilityRole = 'button',
  ringRadius = radius.card,
  style,
  disabled = false,
  hasTVPreferredFocus = false,
  onFocusChange,
}: FocusableProps) {
  const [focused, setFocused] = useState(false);

  const handleFocus = useCallback(() => {
    setFocused(true);
    onFocusChange?.(true);
  }, [onFocusChange]);

  const handleBlur = useCallback(() => {
    setFocused(false);
    onFocusChange?.(false);
  }, [onFocusChange]);

  return (
    <Pressable
      accessibilityRole={accessibilityRole}
      accessibilityLabel={accessibilityLabel}
      {...(accessibilityHint === undefined ? {} : { accessibilityHint })}
      accessibilityState={{ disabled }}
      disabled={disabled}
      focusable={!disabled}
      hasTVPreferredFocus={hasTVPreferredFocus}
      onPress={onPress}
      onLongPress={onLongPress}
      onFocus={handleFocus}
      onBlur={handleBlur}
      style={({ pressed }) => [style, pressed && styles.pressed]}
    >
      {children}
      {focused ? (
        // pointerEvents none: the ring is decoration and must never eat the
        // press that put focus here in the first place.
        <View
          pointerEvents="none"
          style={[styles.ring, { borderRadius: ringRadius + focus.offset }]}
        />
      ) : null}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  /*
   * Touch feedback, and only on touch. A remote already says where it is by
   * moving the ring, so dimming on top of that reads as the card being
   * disabled. `Platform.isTV` is provided by react-native-tvos, which is the
   * react-native core in this app.
   */
  pressed: { opacity: Platform.isTV === true ? 1 : 0.72 },

  ring: {
    position: 'absolute',
    top: -focus.offset,
    left: -focus.offset,
    right: -focus.offset,
    bottom: -focus.offset,
    borderWidth: focus.width,
    borderColor: focus.color,
  },
});
