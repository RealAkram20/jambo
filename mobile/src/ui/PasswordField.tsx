import { useState } from 'react';
import { StyleSheet, View, type TextInputProps } from 'react-native';

import { RevealToggle } from './authComponents';
import { Field } from './components';
import { PasswordMeter } from './PasswordMeter';
import { FIELD_HEIGHT, spacing } from './theme';

/**
 * A password box that can be read, and optionally graded.
 *
 * **Rio, 2026-09-10: "we need to have the hide and unhide for all password
 * fields including in the auth."** Sign-in and Register had a reveal; the six
 * fields behind them did not — change password's three, two-factor's
 * confirmation, and the two password prompts on Security. Somebody typing a
 * long password into an unreadable box on a phone keyboard has no way to find
 * their own typo, and the only recovery is to clear it and start again.
 *
 * **One component rather than a seventh hand-rolled overlay.** The two auth
 * screens each positioned their own `RevealToggle` over their own field, which
 * is the shape jambo-49 found six times over in bottom sheets on the same day:
 * things that look alike because they share tokens, while the markup — the hit
 * area, the vertical offset, the accessibility state — is solved separately
 * and correctly a different number of times. `RevealToggle` itself is theirs
 * and is imported, not copied.
 *
 * **The meter is opt-in and belongs on exactly one kind of field.** A strength
 * bar under "Current password" grades a password the viewer cannot change from
 * that box, and under "Confirm password" it grades the same string twice. It
 * goes under the box where a NEW password is chosen and nowhere else.
 */
export function PasswordField({
  label,
  meter = false,
  value,
  ...input
}: Omit<TextInputProps, 'secureTextEntry'> & {
  label: string;
  hint?: string;
  error?: string | null;
  /** Draw the strength bar and its checklist. New passwords only. */
  meter?: boolean;
  value: string;
}) {
  const [shown, setShown] = useState(false);

  return (
    <View>
      <View>
        <Field
          label={label}
          value={value}
          secureTextEntry={!shown}
          autoCapitalize="none"
          autoCorrect={false}
          {...input}
        />
        {/*
          Overlaid on the well rather than placed in the text flow, so the
          input keeps its full width — the same treatment the sign-in screen
          already used, at the same offset.
        */}
        <View style={styles.anchor}>
          <RevealToggle shown={shown} onToggle={() => setShown((was) => !was)} />
        </View>
      </View>

      {meter ? <PasswordMeter password={value} /> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  /*
   * Measured from the field's own height rather than the 45 the sign-in screen
   * hard-codes: the label sits above the box, so the toggle's centre is the
   * label's line plus half the well. A literal would be right for one label
   * size and wrong the moment the type scale moves.
   */
  anchor: {
    position: 'absolute',
    right: spacing.lg,
    top: spacing.lg + spacing.sm + FIELD_HEIGHT / 2 - 11,
  },
});
