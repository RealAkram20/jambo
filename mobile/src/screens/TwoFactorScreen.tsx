import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { ApiError, NetworkError } from '../api/errors';
import { useAuth } from '../auth/AuthProvider';
import { Alert, Button, Caption, Field, Screen, Surface, Title } from '../ui/components';
import { colors, spacing, typography } from '../ui/theme';
import type { AuthScreenProps } from '../navigation/types';

export function TwoFactorScreen({ route, navigation }: AuthScreenProps<'TwoFactor'>) {
  const { challengeToken, email } = route.params;
  const { completeTwoFactor } = useAuth();

  const [useRecoveryCode, setUseRecoveryCode] = useState(false);
  const [value, setValue] = useState('');
  const [busy, setBusy] = useState(false);
  const [banner, setBanner] = useState<string | null>(null);

  const submit = async () => {
    if (busy) return;

    setBusy(true);
    setBanner(null);

    try {
      await completeTwoFactor(
        challengeToken,
        useRecoveryCode ? { recovery_code: value.trim() } : { code: value.trim() },
      );
      // Signed in: the provider switches stacks and this screen unmounts.
    } catch (error) {
      if (error instanceof NetworkError) {
        setBanner('Could not reach Jambo. Check your connection and try again.');
      } else if (error instanceof ApiError && error.code === 'TWO_FACTOR_CHALLENGE_EXPIRED') {
        /*
         * The challenge is dead, so this screen cannot succeed no matter what
         * is typed into it. Sending the viewer back to the password screen is
         * the only honest option — leaving them on a form that can only fail
         * is how somebody types the same code four times.
         */
        setBanner(null);
        navigation.navigate('SignIn');
      } else if (error instanceof ApiError && error.code === 'TWO_FACTOR_INVALID') {
        // A wrong code does not consume the challenge (openapi.yaml), so the
        // viewer stays here and tries again — which is why the field is
        // cleared rather than the screen being replaced.
        setValue('');
        setBanner(
          useRecoveryCode
            ? 'That recovery code was not recognised.'
            : 'That code was not right. Check your authenticator app and try again.',
        );
      } else {
        setBanner(error instanceof ApiError ? error.message : 'Something went wrong.');
      }
    } finally {
      setBusy(false);
    }
  };

  const canSubmit = value.trim() !== '' && !busy;

  return (
    <Screen scroll>
      <View style={styles.centre}>
        <Surface>
          <Title>Two-factor</Title>
          {/* Absent for a Google sign-in: the address is inside a token
              only the server can read. A line reading "Signing in as
              undefined" is worse than no line. */}
          {email === undefined || email === '' ? null : (
            <Caption>{`Signing in as ${email}.`}</Caption>
          )}

          <View style={styles.form}>
            {banner !== null ? <Alert tone="error">{banner}</Alert> : null}

            {useRecoveryCode ? (
              <Field
                label="Recovery code"
                value={value}
                onChangeText={setValue}
                hint="One of the codes saved when two-factor was switched on."
                autoCapitalize="none"
                autoCorrect={false}
                editable={!busy}
                returnKeyType="go"
                onSubmitEditing={() => {
                  if (canSubmit) void submit();
                }}
              />
            ) : (
              <Field
                label="Six-digit code"
                value={value}
                onChangeText={setValue}
                hint="From your authenticator app."
                keyboardType="number-pad"
                autoComplete="one-time-code"
                textContentType="oneTimeCode"
                maxLength={6}
                editable={!busy}
                returnKeyType="go"
                onSubmitEditing={() => {
                  if (canSubmit) void submit();
                }}
              />
            )}

            <Button
              label="Continue"
              busy={busy}
              disabled={!canSubmit}
              onPress={() => void submit()}
            />

            <Pressable
              accessibilityRole="button"
              onPress={() => {
                setUseRecoveryCode((previous) => !previous);
                setValue('');
                setBanner(null);
              }}
              hitSlop={12}
              style={styles.switchMode}
            >
              <Text style={styles.switchLabel}>
                {useRecoveryCode ? 'Use a code from my app' : 'Use a recovery code instead'}
              </Text>
            </Pressable>
          </View>
        </Surface>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  centre: { flex: 1, justifyContent: 'center' },
  form: { marginTop: spacing.xl },
  switchMode: { marginTop: spacing.lg, alignItems: 'center' },
  switchLabel: { ...typography.caption, color: colors.link },
});
