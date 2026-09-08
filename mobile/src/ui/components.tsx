import React, { useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  type TextInputProps,
  type ViewStyle,
} from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { SafeAreaView } from 'react-native-safe-area-context';

import { bundledPreloader, type BrandSource } from './branding';

import {
  BUTTON_HEIGHT,
  FIELD_HEIGHT,
  colors,
  radius,
  spacing,
  touchPadding,
  typography,
} from './theme';

/**
 * The shared pieces every screen in this slice is built from.
 *
 * One component per idea, used everywhere it appears. The alternative — each
 * screen styling its own button — is how two buttons end up two pixels apart
 * and nobody can say which is right.
 */

export function Screen({
  children,
  scroll = false,
  style,
}: {
  children: React.ReactNode;
  scroll?: boolean;
  style?: ViewStyle;
}) {
  const content = <View style={[styles.screenBody, style]}>{children}</View>;

  return (
    <SafeAreaView style={styles.screen} edges={['top', 'bottom']}>
      {scroll ? (
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
        >
          {content}
        </ScrollView>
      ) : (
        content
      )}
    </SafeAreaView>
  );
}

export function Title({ children }: { children: React.ReactNode }) {
  // `accessibilityRole="header"` rather than styling alone: a screen reader
  // needs to be able to jump between headings, and a big bold Text is not a
  // heading to anything but an eye.
  return (
    <Text accessibilityRole="header" style={styles.title}>
      {children}
    </Text>
  );
}

export function Heading({ children }: { children: React.ReactNode }) {
  return (
    <Text accessibilityRole="header" style={styles.heading}>
      {children}
    </Text>
  );
}

export function Body({ children }: { children: React.ReactNode }) {
  return <Text style={styles.body}>{children}</Text>;
}

export function Caption({ children }: { children: React.ReactNode }) {
  return <Text style={styles.caption}>{children}</Text>;
}

export function Surface({ children, style }: { children: React.ReactNode; style?: ViewStyle }) {
  return <View style={[styles.surface, style]}>{children}</View>;
}

export function Button({
  label,
  onPress,
  busy = false,
  disabled = false,
  tone = 'primary',
}: {
  label: string;
  onPress: () => void;
  busy?: boolean;
  disabled?: boolean;
  tone?: 'primary' | 'quiet' | 'danger';
}) {
  const inactive = disabled || busy;
  const pad = touchPadding(BUTTON_HEIGHT);

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      accessibilityState={{ disabled: inactive, busy }}
      // The control is *drawn* at the site's 46dp and *touchable* at 48. See
      // the note on MIN_TOUCH_TARGET in theme.ts.
      hitSlop={{ top: pad, bottom: pad }}
      disabled={inactive}
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        tone === 'primary' && styles.buttonPrimary,
        tone === 'quiet' && styles.buttonQuiet,
        tone === 'danger' && styles.buttonDanger,
        pressed && styles.buttonPressed,
        inactive && styles.buttonInactive,
      ]}
    >
      {busy ? (
        <ActivityIndicator color={tone === 'primary' ? colors.onPrimary : colors.text} />
      ) : (
        <Text
          style={[
            styles.buttonLabel,
            tone === 'primary' ? styles.buttonLabelPrimary : styles.buttonLabelQuiet,
            tone === 'danger' && styles.buttonLabelDanger,
          ]}
        >
          {label}
        </Text>
      )}
    </Pressable>
  );
}

export function Field({
  label,
  hint,
  error,
  ...input
}: TextInputProps & { label: string; hint?: string; error?: string | null }) {
  const [focused, setFocused] = useState(false);

  return (
    <View style={styles.fieldGroup}>
      {/* A real label, always visible. A placeholder is not a label: it
          disappears the moment somebody starts typing, which is exactly when
          they are most likely to want to check what the field was for. */}
      <Text style={styles.fieldLabel} nativeID={`label-${label}`}>
        {label}
      </Text>
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
        style={[
          styles.field,
          focused && styles.fieldFocused,
          error !== null && error !== undefined && styles.fieldError,
        ]}
      />
      {hint !== undefined && error === null ? <Text style={styles.hint}>{hint}</Text> : null}
      {error !== null && error !== undefined ? (
        // Announced rather than only coloured: status must never be carried by
        // colour alone, and a field error nobody's screen reader mentions is a
        // form that silently refuses to submit.
        <Text accessibilityLiveRegion="polite" style={styles.fieldErrorText}>
          {error}
        </Text>
      ) : null}
    </View>
  );
}

export function Alert({ tone, children }: { tone: 'error' | 'ok'; children: React.ReactNode }) {
  return (
    <View
      accessibilityLiveRegion="polite"
      accessibilityRole="alert"
      style={[styles.alert, tone === 'error' ? styles.alertError : styles.alertOk]}
    >
      <Text
        style={[styles.alertText, tone === 'error' ? styles.alertTextError : styles.alertTextOk]}
      >
        {children}
      </Text>
    </View>
  );
}

/**
 * The loading state, using the site's own preloader animation.
 *
 * `expo-image` rather than React Native's `Image`: the admin's preloader is an
 * animated GIF, and RN's own Image does not animate GIFs on Android unless the
 * Fresco animated-gif dependency is added to the native build. It would render
 * a still first frame — which looks like a broken spinner rather than a
 * deliberate one, and only on real devices.
 *
 * `source` is overridable so a caller with `GET /app/config` in hand can pass
 * the server-driven URL; the bundled file is the default and the offline
 * fallback. See `src/ui/branding.ts`.
 */
export function Loading({ label, source }: { label: string; source?: BrandSource }) {
  return (
    <View style={styles.centred}>
      {/*
        The preloader alone — no caption and no wordmark. Rio's call
        (2026-09-09): the site's loading animation already carries the brand,
        and a logo plus a line of text stacked above it is three things saying
        the same thing.

        `label` survives as the accessibility name rather than as visible text.
        A screen reader needs to announce *something* here, and "image" is not
        it — the whole content of this screen is that the app is busy.
      */}
      <ExpoImage
        source={source ?? bundledPreloader}
        style={styles.preloader}
        contentFit="contain"
        // The GIF is the point; without this it renders as a still.
        autoplay
        accessibilityRole="progressbar"
        accessibilityLabel={label}
      />
    </View>
  );
}

/** A spinner, for places too small for the preloader — inside a button or a row. */
export function Spinner({ tone = 'primary' }: { tone?: 'primary' | 'onPrimary' }) {
  return <ActivityIndicator color={tone === 'primary' ? colors.primary : colors.onPrimary} />;
}

/**
 * The error state, with a way out of it.
 *
 * `onRetry` is optional because not everything is retryable, but a screen that
 * can only say "something went wrong" and offers nothing to do about it is the
 * state viewers report as "the app is broken".
 */
export function ErrorState({
  message,
  onRetry,
}: {
  message: string;
  onRetry?: (() => void) | undefined;
}) {
  return (
    <View style={styles.centred}>
      <Text style={styles.errorTitle}>{message}</Text>
      {onRetry ? (
        <View style={styles.errorAction}>
          <Button label="Try again" tone="quiet" onPress={onRetry} />
        </View>
      ) : null}
    </View>
  );
}

export function EmptyState({ title, detail }: { title: string; detail: string }) {
  return (
    <View style={styles.centred}>
      <Text style={styles.emptyTitle}>{title}</Text>
      <Text style={styles.emptyDetail}>{detail}</Text>
    </View>
  );
}

/** A dividing line between list rows. */
export function Divider() {
  return <View style={styles.divider} />;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  screenBody: { flex: 1, paddingHorizontal: spacing.lg },
  scrollContent: { flexGrow: 1, paddingVertical: spacing.lg },

  title: {
    ...typography.title,
    color: colors.text,
    marginBottom: spacing.sm,
  },
  heading: {
    ...typography.heading,
    color: colors.text,
    marginBottom: spacing.sm,
  },
  body: { ...typography.body, color: colors.text },
  caption: { ...typography.caption, color: colors.textMuted },

  surface: {
    backgroundColor: colors.surface,
    borderRadius: radius.surface,
    padding: spacing.xl,
  },

  button: {
    height: BUTTON_HEIGHT,
    borderRadius: radius.pill,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: spacing.xl,
  },
  buttonPrimary: { backgroundColor: colors.primary },
  buttonQuiet: {
    backgroundColor: 'transparent',
    borderWidth: 1,
    borderColor: colors.border,
  },
  buttonDanger: {
    backgroundColor: colors.errorBg,
    borderWidth: 1,
    borderColor: colors.errorBorder,
  },
  buttonPressed: { opacity: 0.85 },
  buttonInactive: { opacity: 0.55 },
  buttonLabel: { ...typography.button },
  buttonLabelPrimary: { color: colors.onPrimary },
  buttonLabelQuiet: { color: colors.text },
  buttonLabelDanger: { color: colors.errorText },

  fieldGroup: { marginBottom: spacing.lg },
  fieldLabel: {
    ...typography.caption,
    color: colors.textMuted,
    marginBottom: spacing.xs,
  },
  field: {
    height: FIELD_HEIGHT,
    borderRadius: radius.input,
    backgroundColor: colors.inputBg,
    borderWidth: 1,
    borderColor: 'transparent',
    paddingHorizontal: spacing.lg,
    ...typography.field,
    color: colors.fieldText,
  },
  // The site's focused field takes the primary colour on its border, which is
  // also the visible focus ring the TV build will need in Phase 4.
  fieldFocused: {
    borderColor: colors.borderFocus,
    backgroundColor: colors.inputBgFocus,
  },
  fieldError: { borderColor: colors.errorBorder },
  fieldErrorText: {
    ...typography.caption,
    color: colors.errorText,
    marginTop: spacing.xs,
  },
  hint: {
    ...typography.caption,
    color: colors.textMuted,
    marginTop: spacing.xs,
  },

  alert: {
    borderRadius: radius.alert,
    borderWidth: 1,
    padding: spacing.md,
    marginBottom: spacing.lg,
  },
  alertError: {
    backgroundColor: colors.errorBg,
    borderColor: colors.errorBorder,
  },
  alertOk: { backgroundColor: colors.okBg, borderColor: colors.okBorder },
  alertText: { ...typography.caption },
  alertTextError: { color: colors.errorText },
  alertTextOk: { color: colors.okText },

  centred: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: spacing.xl,
  },
  preloader: { width: 200, height: 133 },
  errorTitle: { ...typography.body, color: colors.text, textAlign: 'center' },
  errorAction: { marginTop: spacing.xl },
  emptyTitle: {
    ...typography.heading,
    color: colors.text,
    textAlign: 'center',
  },
  emptyDetail: {
    ...typography.caption,
    color: colors.textMuted,
    textAlign: 'center',
    marginTop: spacing.sm,
  },

  divider: { height: 1, backgroundColor: colors.divider },
});
