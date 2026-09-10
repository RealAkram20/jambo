import { useState } from 'react';
import { StyleSheet, Text, TextInput, View } from 'react-native';

import { colors, fonts, phoneField as t, spacing } from './theme';
import { DEFAULT_COUNTRY, dialCode, formatAsTyped } from './phone';

/**
 * A phone number field: the country's dial code, and a number that formats
 * itself as it is typed.
 *
 * Rio, 2026-09-10: *"for the phone number we should have a country code for
 * the selected country … so when the person is trying in the number we need to
 * format in the way we want the same way we do for visa and credit cards."*
 *
 * **The dial code is shown, not typed.** It comes from the country already
 * chosen elsewhere on the form, sits in the field as a fixed prefix, and
 * cannot be edited or deleted — which is the difference between a code that is
 * always right and one that is a fourth thing to get wrong. Somebody who does
 * paste a full international number is still understood: `+256…` typed into
 * the box is read as international and the prefix steps out of the way.
 *
 * **The value handed to the caller is what was typed, not E.164.** Converting
 * on every keystroke would fight the viewer — a half-finished number has no
 * E.164 form, so the field would be rewriting itself out from under them. The
 * caller converts once, on submit, with `toE164`. This component's only job is
 * that the number is readable while it is being entered.
 */
export function PhoneField({
  label,
  value,
  onChangeText,
  country,
  error,
  hint,
  placeholder,
}: {
  label: string;
  value: string;
  onChangeText: (next: string) => void;
  /** ISO-3166 alpha-2, from the form's country field. Defaults to Uganda. */
  country?: string | null;
  error?: string | null;
  hint?: string;
  placeholder?: string;
}) {
  const [focused, setFocused] = useState(false);

  const iso = typeof country === 'string' && country !== '' ? country : DEFAULT_COUNTRY;
  const code = dialCode(iso);

  /*
   * The prefix disappears once the viewer types their own `+`, because at that
   * point they are giving a full international number and two country codes on
   * one row is a number nobody can read.
   */
  const international = value.trim().startsWith('+');
  const showPrefix = code !== '' && !international;

  return (
    <View style={styles.group}>
      <Text style={styles.label} nativeID={`label-${label}`}>
        {label}
      </Text>

      <View
        style={[
          styles.row,
          focused && styles.rowFocused,
          error !== null && error !== undefined && styles.rowError,
        ]}
      >
        {showPrefix ? (
          <>
            {/*
              Part of the value, not decoration, so a screen reader reads the
              whole number rather than the last nine digits of it.
            */}
            <Text style={styles.prefix} accessibilityLabel={`Country code ${code}`}>
              {code}
            </Text>
            <View style={styles.divider} />
          </>
        ) : null}

        <TextInput
          accessibilityLabel={showPrefix ? `${label}, country code ${code}` : label}
          accessibilityLabelledBy={`label-${label}`}
          value={value}
          // Formatting happens here rather than on blur so the number is
          // readable while it is being typed, which is the whole request.
          onChangeText={(next) => onChangeText(formatAsTyped(next, iso))}
          keyboardType="phone-pad"
          autoComplete="tel"
          textContentType="telephoneNumber"
          placeholder={placeholder}
          placeholderTextColor={colors.placeholder}
          onFocus={() => setFocused(true)}
          onBlur={() => setFocused(false)}
          style={styles.input}
        />
      </View>

      {hint !== undefined && (error === null || error === undefined) ? (
        <Text style={styles.hint}>{hint}</Text>
      ) : null}

      {error !== null && error !== undefined ? (
        <Text accessibilityLiveRegion="polite" style={styles.error}>
          {error}
        </Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  group: { gap: spacing.sm },
  label: { fontFamily: fonts.medium, fontSize: t.labelSize, color: colors.text },

  row: {
    flexDirection: 'row',
    alignItems: 'center',
    height: t.height,
    borderRadius: t.radius,
    borderWidth: 1,
    borderColor: t.border,
    backgroundColor: t.bg,
    paddingHorizontal: spacing.md,
    gap: spacing.sm,
  },
  rowFocused: { borderColor: colors.borderFocus, backgroundColor: t.bgFocus },
  rowError: { borderColor: colors.errorBorder },

  prefix: { fontFamily: fonts.medium, fontSize: t.textSize, color: colors.fieldText },
  divider: { width: 1, height: t.dividerHeight, backgroundColor: t.border },

  input: {
    flex: 1,
    height: '100%',
    fontFamily: fonts.regular,
    fontSize: t.textSize,
    color: colors.fieldText,
    // Android's TextInput carries vertical padding that pushes the text off
    // centre inside a fixed-height row.
    paddingVertical: 0,
  },

  hint: { fontFamily: fonts.regular, fontSize: t.hintSize, color: colors.textMuted },
  error: { fontFamily: fonts.regular, fontSize: t.hintSize, color: colors.errorText },
});
