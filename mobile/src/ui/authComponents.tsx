import React, { useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View, type TextInputProps } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import Svg, { Path } from 'react-native-svg';
import { ArrowRight, Check, Eye, EyeSlash } from 'phosphor-react-native';

import { auth, colors, spacing, touchPadding, typography } from './theme';

/**
 * The pieces the sign-in screen is made of.
 *
 * Kept apart from `components.tsx` on purpose. That file is the app's ordinary
 * kit — the buttons and fields every other screen uses, sized and coloured
 * from the website's own tokens. These are the auth screens' treatment, which
 * the website has no equivalent of, and mixing them would quietly make the
 * glassy card the default surface for the whole app.
 */

/** A labelled field with an icon well, matching the mockup's input. */
export function AuthField({
  label,
  icon,
  error,
  accessory,
  ...input
}: TextInputProps & {
  label: string;
  icon: React.ReactNode;
  error?: string | null;
  accessory?: React.ReactNode;
}) {
  const [focused, setFocused] = useState(false);
  const invalid = error !== null && error !== undefined && error !== '';

  return (
    <View style={styles.fieldGroup}>
      <View style={styles.labelRow}>
        {/* A real, always-visible label. A placeholder disappears the moment
            somebody types, which is when they most want to check the field. */}
        <Text style={styles.label} nativeID={`label-${label}`}>
          {label}
        </Text>
        {accessory}
      </View>

      <View style={[styles.well, focused && styles.wellFocused, invalid && styles.wellInvalid]}>
        <View style={styles.wellIcon}>{icon}</View>

        <TextInput
          accessibilityLabel={label}
          accessibilityLabelledBy={`label-${label}`}
          placeholderTextColor={colors.placeholder}
          {...input}
          onFocus={(e) => {
            setFocused(true);
            input.onFocus?.(e);
          }}
          onBlur={(e) => {
            setFocused(false);
            input.onBlur?.(e);
          }}
          style={styles.input}
        />
      </View>

      {invalid ? (
        // Announced, not merely coloured: status must never be carried by
        // colour alone, and a field error no screen reader mentions is a form
        // that silently refuses to submit.
        <Text accessibilityLiveRegion="polite" style={styles.error}>
          {error}
        </Text>
      ) : null}
    </View>
  );
}

/** The show/hide control inside the password field. */
export function RevealToggle({ shown, onToggle }: { shown: boolean; onToggle: () => void }) {
  return (
    <Pressable
      accessibilityRole="button"
      // The label says what the control *does*, not what it shows. "Eye" tells
      // a screen-reader user nothing about the consequence of pressing it.
      accessibilityLabel={shown ? 'Hide password' : 'Show password'}
      accessibilityState={{ checked: shown }}
      hitSlop={12}
      onPress={onToggle}
      style={styles.reveal}
    >
      {shown ? (
        <Eye size={22} color={colors.placeholder} weight="regular" />
      ) : (
        <EyeSlash size={22} color={colors.placeholder} weight="regular" />
      )}
    </Pressable>
  );
}

export function Checkbox({
  checked,
  onToggle,
  label,
}: {
  checked: boolean;
  onToggle: () => void;
  label: string;
}) {
  return (
    <Pressable
      accessibilityRole="checkbox"
      accessibilityState={{ checked }}
      accessibilityLabel={label}
      hitSlop={{ top: 10, bottom: 10, left: 4, right: 10 }}
      onPress={onToggle}
      style={styles.checkboxRow}
    >
      <View style={[styles.checkbox, checked && styles.checkboxOn]}>
        {checked ? <Check size={15} color={colors.onPrimary} weight="bold" /> : null}
      </View>
      <Text style={styles.checkboxLabel}>{label}</Text>
    </Pressable>
  );
}

/** The primary action: full width, gradient, with the mockup's trailing arrow. */
export function GradientButton({
  label,
  onPress,
  busy = false,
  disabled = false,
}: {
  label: string;
  onPress: () => void;
  busy?: boolean;
  disabled?: boolean;
}) {
  const inactive = disabled || busy;
  const pad = touchPadding(auth.buttonHeight);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ disabled: inactive, busy }}
      hitSlop={{ top: pad, bottom: pad }}
      disabled={inactive}
      onPress={onPress}
      style={({ pressed }) => [pressed && styles.pressed, inactive && styles.inactive]}
    >
      <LinearGradient
        colors={[auth.buttonFrom, auth.buttonTo]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={styles.primary}
      >
        <Text style={styles.primaryLabel}>{busy ? 'Signing in…' : label}</Text>
        {busy ? null : <ArrowRight size={20} color={colors.onPrimary} weight="bold" />}
      </LinearGradient>
    </Pressable>
  );
}

/** A rule with a word in it. */
export function OrDivider({ label = 'OR' }: { label?: string }) {
  return (
    <View style={styles.dividerRow}>
      <View style={styles.rule} />
      <Text style={styles.dividerLabel}>{label}</Text>
      <View style={styles.rule} />
    </View>
  );
}

/**
 * Google's mark, drawn rather than imported.
 *
 * Phosphor ships a monochrome `GoogleLogo`, and Google's own brand guidelines
 * require the four-colour G on a sign-in button. It is four paths; a
 * dependency for that would be worse.
 */
export function GoogleMark({ size = 20 }: { size?: number }) {
  return (
    <Svg width={size} height={size} viewBox="0 0 48 48">
      <Path
        fill="#4285F4"
        d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"
      />
      <Path
        fill="#34A853"
        d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"
      />
      <Path
        fill="#FBBC05"
        d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34C2.85 17.09 2 20.45 2 24s.85 6.91 2.34 9.88l7.35-5.7z"
      />
      <Path
        fill="#EA4335"
        d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"
      />
    </Svg>
  );
}

/** A secondary, bordered action — the Google button. */
export function QuietButton({
  label,
  icon,
  onPress,
  disabled = false,
}: {
  label: string;
  icon?: React.ReactNode;
  onPress: () => void;
  disabled?: boolean;
}) {
  const pad = touchPadding(auth.buttonHeight);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ disabled }}
      hitSlop={{ top: pad, bottom: pad }}
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.quiet,
        pressed && styles.pressed,
        disabled && styles.inactive,
      ]}
    >
      {icon}
      <Text style={styles.quietLabel}>{label}</Text>
    </Pressable>
  );
}

/** An inline text link — "Forgot password?", "Create one". */
export function LinkText({
  label,
  onPress,
  withArrow = false,
}: {
  label: string;
  onPress: () => void;
  withArrow?: boolean;
}) {
  return (
    <Pressable accessibilityRole="link" accessibilityLabel={label} hitSlop={10} onPress={onPress}>
      <View style={styles.linkRow}>
        <Text style={styles.link}>{label}</Text>
        {withArrow ? <ArrowRight size={16} color={colors.primary} weight="bold" /> : null}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  fieldGroup: { marginBottom: spacing.lg },
  labelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: spacing.sm,
  },
  label: { fontFamily: 'Roboto_700Bold', fontSize: 15, lineHeight: 20, color: colors.fieldText },

  well: {
    height: auth.inputHeight,
    borderRadius: auth.inputRadius,
    backgroundColor: auth.inputBg,
    borderWidth: 1,
    borderColor: auth.inputBorder,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
  },
  wellFocused: {
    borderColor: colors.primary,
    // Android draws elevation shadows in the shadow colour, which gives the
    // mockup's glow without a blur layer.
    shadowColor: colors.primary,
    shadowOpacity: 0.6,
    shadowRadius: 8,
    elevation: 6,
  },
  wellInvalid: { borderColor: colors.errorBorder },
  wellIcon: { marginRight: spacing.md },
  input: {
    flex: 1,
    height: '100%',
    fontFamily: 'Roboto_400Regular',
    fontSize: 16,
    color: colors.fieldText,
    padding: 0,
  },
  reveal: { paddingLeft: spacing.md },
  error: {
    ...typography.caption,
    color: colors.errorText,
    marginTop: spacing.xs,
  },

  checkboxRow: { flexDirection: 'row', alignItems: 'center', marginBottom: spacing.xl },
  checkbox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 1.5,
    borderColor: auth.quietBorder,
    alignItems: 'center',
    justifyContent: 'center',
  },
  checkboxOn: { backgroundColor: colors.primary, borderColor: colors.primary },
  checkboxLabel: {
    fontFamily: 'Roboto_400Regular',
    fontSize: 15,
    color: colors.text,
    marginLeft: spacing.md,
  },

  primary: {
    height: auth.buttonHeight,
    borderRadius: auth.buttonRadius,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
  },
  primaryLabel: {
    fontFamily: 'Roboto_700Bold',
    fontSize: 17,
    color: colors.onPrimary,
  },
  pressed: { opacity: 0.88 },
  inactive: { opacity: 0.5 },

  dividerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: spacing.xl,
    gap: spacing.lg,
  },
  rule: { flex: 1, height: 1, backgroundColor: auth.rule },
  dividerLabel: {
    fontFamily: 'Roboto_500Medium',
    fontSize: 13,
    color: colors.textMuted,
    letterSpacing: 1,
  },

  quiet: {
    flex: 1,
    height: auth.buttonHeight,
    borderRadius: auth.buttonRadius,
    backgroundColor: auth.quietBg,
    borderWidth: 1,
    borderColor: auth.quietBorder,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: spacing.md,
  },
  quietLabel: { fontFamily: 'Roboto_500Medium', fontSize: 16, color: colors.text },

  linkRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.xs },
  link: { fontFamily: 'Roboto_700Bold', fontSize: 15, color: colors.primary },
});
