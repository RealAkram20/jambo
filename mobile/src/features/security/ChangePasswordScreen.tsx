import { useCallback, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { useQueryClient } from '@tanstack/react-query';

import { changePassword } from './api';
import { ApiError, NetworkError } from '../../api/errors';
import { Alert, Button, Caption, Heading } from '../../ui/components';
import { PasswordField } from '../../ui/PasswordField';
import { colors, spacing } from '../../ui/theme';
import type { AppScreenProps } from '../../navigation/types';

/**
 * Change the account password.
 *
 * This is the Password card at the top of
 * `resources/views/profile-hub/security.blade.php`: three fields — current,
 * new, confirm — and one "Update password" button. The website renders it
 * inline on the security page; the app gives it a screen, which is the shape
 * `SecurityScreen`'s own docblock had already scoped for it, because it is a
 * form with its own validation and its own consequence to state.
 *
 * **The consequence is stated before the button, not after it.**
 * `PasswordController::change` revokes every device and every token on the
 * account except the one making the call. A password change that silently
 * signs a television out is a support ticket, so the screen says so up front.
 * The website does not say it, because on the website it is not true in the
 * same way — this is the app being honest about the API it is calling rather
 * than a deviation from the design.
 *
 * **No query feeds this form.** Nothing here is seeded from a server value, so
 * the trap that has bitten this project twice — a background refetch landing
 * mid-typing and discarding the edit — cannot apply. It is worth saying out
 * loud in a file full of `useState`, because the next person to add a
 * "current email" line to this screen needs to know why it is not there.
 */
export function ChangePasswordScreen({ navigation }: AppScreenProps<'ChangePassword'>) {
  const queryClient = useQueryClient();

  const [current, setCurrent] = useState('');
  const [next, setNext] = useState('');
  const [confirmation, setConfirmation] = useState('');

  const [busy, setBusy] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);
  /*
   * Three named states rather than a keyed record. The record read back as
   * `string | null | undefined`, and the app compiles with
   * `exactOptionalPropertyTypes` — so an explicitly undefined `error` prop is
   * a type error rather than an absent one. Three fields, three states.
   */
  const [currentError, setCurrentError] = useState<string | null>(null);
  const [nextError, setNextError] = useState<string | null>(null);
  const [confirmationError, setConfirmationError] = useState<string | null>(null);
  const [done, setDone] = useState(false);

  const submit = useCallback(async () => {
    setBusy(true);
    setFormError(null);
    setCurrentError(null);
    setNextError(null);
    setConfirmationError(null);

    try {
      await changePassword({
        currentPassword: current,
        password: next,
        passwordConfirmation: confirmation,
      });

      /*
       * The other devices are gone, so anything cached about them is a lie.
       * Security carries the account's state and Devices is the list that just
       * shrank; both are refetched rather than left to go stale until somebody
       * pulls to refresh.
       */
      await queryClient.invalidateQueries({ queryKey: ['security'] });
      await queryClient.invalidateQueries({ queryKey: ['devices'] });

      setDone(true);
      setCurrent('');
      setNext('');
      setConfirmation('');
    } catch (caught) {
      if (caught instanceof NetworkError) {
        setFormError('Could not reach Jambo. Check your connection and try again.');
      } else if (caught instanceof ApiError) {
        /*
         * Field errors go under the field they belong to. The server names
         * `current_password` separately from `password`, which is the whole
         * value of showing them individually: "That password is not correct"
         * under the first box is a different instruction from "too short"
         * under the second.
         */
        const onCurrent = caught.fieldError('current_password');
        const onNext = caught.fieldError('password');
        const onConfirmation = caught.fieldError('password_confirmation');

        setCurrentError(onCurrent);
        setNextError(onNext);
        setConfirmationError(onConfirmation);

        const anyField = onCurrent ?? onNext ?? onConfirmation;

        // Only show the banner when nothing landed on a field, so one problem
        // is never reported twice on one screen.
        setFormError(anyField === null ? caught.message : null);
      } else {
        setFormError('We could not change your password. Try again in a moment.');
      }
    } finally {
      setBusy(false);
    }
  }, [current, next, confirmation, queryClient]);

  /*
   * A courtesy, not a check. The server owns the password rules — it applies
   * Laravel's `Password::defaults()`, which this client deliberately does not
   * try to mirror, because a client-side copy of a server rule is a copy that
   * goes stale silently. All this does is refuse to send an obviously empty
   * form.
   */
  const canSubmit = current !== '' && next !== '' && confirmation !== '' && !busy;

  if (done) {
    return (
      <View style={styles.screen}>
        <ScrollView contentContainerStyle={styles.content}>
          <Alert tone="ok">
            Password changed. Your other devices have been signed out and will need the new
            password.
          </Alert>
          <View style={styles.action}>
            <Button label="Done" onPress={() => navigation.goBack()} />
          </View>
        </ScrollView>
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.screen}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Heading>Password</Heading>
        <Caption>
          Changing your password signs you out everywhere else. This device stays signed in.
        </Caption>

        {formError === null ? null : (
          <View style={styles.banner}>
            <Alert tone="error">{formError}</Alert>
          </View>
        )}

        <View style={styles.form}>
          <PasswordField
            label="Current password"
            value={current}
            onChangeText={setCurrent}
            error={currentError}
            autoComplete="current-password"
            textContentType="password"
            returnKeyType="next"
          />
          {/*
            The one field on this screen that grades. A bar under "Current
            password" would score a password the viewer cannot change from that
            box, and under "Confirm" it would score the same string twice.
          */}
          <PasswordField
            label="New password"
            meter
            value={next}
            onChangeText={setNext}
            error={nextError}
            autoComplete="new-password"
            textContentType="newPassword"
            returnKeyType="next"
          />
          <PasswordField
            label="Confirm new password"
            value={confirmation}
            onChangeText={setConfirmation}
            error={confirmationError}
            autoComplete="new-password"
            textContentType="newPassword"
            returnKeyType="done"
            onSubmitEditing={() => {
              if (canSubmit) void submit();
            }}
          />
        </View>

        <Button
          label="Update password"
          busy={busy}
          disabled={!canSubmit}
          onPress={() => void submit()}
        />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg },

  banner: { marginTop: spacing.lg },
  form: { marginTop: spacing.lg, marginBottom: spacing.xl, gap: spacing.md },
  action: { marginTop: spacing.xl },
});
