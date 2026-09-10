import { useCallback, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import QRCode from 'react-native-qrcode-svg';

import { confirmTwoFactor, disableTwoFactor, securityKey, startTwoFactor } from './api';
import { RecoveryCodes } from './RecoveryCodes';
import { groupSecret, isCompleteCode, otpauthUri, sanitiseCode } from './otpauth';
import { api } from '../../api/jambo';
import { ApiError, NetworkError } from '../../api/errors';
import { Alert, Button, Caption, Field, Heading, Loading } from '../../ui/components';
import { colors, fonts, radius, spacing, typography } from '../../ui/theme';
import { PasswordField } from '../../ui/PasswordField';
import type { AppScreenProps } from '../../navigation/types';

/**
 * Turning two-factor on.
 *
 * This is the pending half of
 * `resources/views/profile-hub/security.blade.php`: the QR, the manual key
 * beside it, the two numbered instructions, the six-digit confirm field, and
 * Cancel setup — followed by the recovery codes the website lists once the
 * feature is on.
 *
 * **It is a flow, not a switch, and that is the whole reason it has a screen.**
 * Slice 2b built the status display and stopped precisely here. The server
 * makes the same distinction: `POST /account/2fa` mints a pending secret and
 * two-factor is NOT on until `POST /account/2fa/confirm` succeeds. A switch
 * that turned it on and never showed the recovery codes would lock people out
 * of their own accounts.
 *
 * **A pending setup is resumed, never restarted.** `POST /account/2fa` called
 * a second time mints a NEW secret and discards the old one — so somebody who
 * had already added Jambo to their authenticator would find their codes
 * rejected forever. `GET /account/security` returns the pending secret for
 * exactly this case, and that is what this screen opens with.
 *
 * **The QR is kept, and the manual key is made usable.** On a desktop the key
 * is the fallback; on a handset it is the main path, because nobody can scan a
 * code displayed on the device they are holding. So the QR is here for the
 * tablet-and-phone case the website was designed for, and the secret below it
 * is grouped in fours and selectable, which is what somebody typing it into an
 * authenticator on the same phone actually needs.
 */
export function TwoFactorSetupScreen({ navigation }: AppScreenProps<'TwoFactorSetup'>) {
  const queryClient = useQueryClient();

  const security = useQuery({ queryKey: securityKey, queryFn: () => api.security() });
  const profile = useQuery({ queryKey: ['profile'], queryFn: () => api.profile() });

  /*
   * The secret this screen is working with. Starts null and is filled either
   * from the pending setup the server already holds or from the one this
   * screen starts. Never re-read from a refetch: a background refresh landing
   * mid-flow must not replace a secret somebody has just scanned.
   */
  const [startedSecret, setStartedSecret] = useState<string | null>(null);
  const [codes, setCodes] = useState<string[] | null>(null);

  const [code, setCode] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [cancelling, setCancelling] = useState(false);
  const [cancelPassword, setCancelPassword] = useState('');
  const [cancelError, setCancelError] = useState<string | null>(null);

  const begin = useCallback(async () => {
    setBusy(true);
    setError(null);
    try {
      const started = await startTwoFactor();
      setStartedSecret(started.secret);
    } catch (caught) {
      setError(messageFor(caught, 'We could not start two-factor setup.'));
    } finally {
      setBusy(false);
    }
  }, []);

  const confirm = useCallback(async () => {
    setBusy(true);
    setError(null);
    try {
      const fresh = await confirmTwoFactor(code);
      setCodes(fresh);
      await queryClient.invalidateQueries({ queryKey: securityKey });
    } catch (caught) {
      /*
       * Branch on the CODE, never on the message — the spec's own rule.
       * `TWO_FACTOR_INVALID` is a wrong six digits, which is an ordinary thing
       * to do and deserves the server's own wording, not a generic failure.
       */
      setError(messageFor(caught, 'That code could not be checked. Try again in a moment.'));
      setCode('');
    } finally {
      setBusy(false);
    }
  }, [code, queryClient]);

  const cancel = useCallback(async () => {
    setBusy(true);
    setCancelError(null);
    try {
      await disableTwoFactor(cancelPassword);
      await queryClient.invalidateQueries({ queryKey: securityKey });
      navigation.goBack();
    } catch (caught) {
      setCancelError(messageFor(caught, 'We could not cancel the setup.'));
    } finally {
      setBusy(false);
    }
  }, [cancelPassword, queryClient, navigation]);

  if (security.isPending) return <Loading label="Checking your security settings" />;

  const pendingSecret = security.data?.twoFactor?.pending === true
    ? (security.data.twoFactor.secret ?? null)
    : null;
  const secret = startedSecret ?? pendingSecret;
  const alreadyOn = security.data?.twoFactor?.enabled === true;

  // ── step three: the codes ────────────────────────────────────────
  if (codes !== null) {
    return (
      <View style={styles.screen}>
        <ScrollView contentContainerStyle={styles.content}>
          <Alert tone="ok">
            Two-factor authentication is on. You will be asked for a code from your authenticator
            when you sign in.
          </Alert>

          <View style={styles.section}>
            <RecoveryCodes codes={codes} />
          </View>

          <View style={styles.section}>
            <Button label="I have saved my recovery codes" onPress={() => navigation.goBack()} />
          </View>
        </ScrollView>
      </View>
    );
  }

  /*
   * Reached while it is already on — from a stale menu, or a second tap. It
   * says so rather than restarting a setup that would replace a working
   * secret. The codes live on the Security screen, which is where the website
   * keeps them too.
   */
  if (alreadyOn && startedSecret === null) {
    return (
      <View style={styles.screen}>
        <ScrollView contentContainerStyle={styles.content}>
          <Alert tone="ok">Two-factor authentication is already on for this account.</Alert>
          <View style={styles.section}>
            <Button label="Back to security" onPress={() => navigation.goBack()} />
          </View>
        </ScrollView>
      </View>
    );
  }

  // ── step one: nothing started ────────────────────────────────────
  if (secret === null) {
    return (
      <View style={styles.screen}>
        <ScrollView contentContainerStyle={styles.content}>
          <Heading>Two-factor authentication</Heading>

          {error === null ? null : (
            <View style={styles.section}>
              <Alert tone="error">{error}</Alert>
            </View>
          )}

          <View style={styles.section}>
            {/*
              Kept, trimmed. It names a thing the viewer must go and get before
              the flow can finish, which is a fact they cannot see on screen —
              not an explanation of the heading above it.
            */}
            <Text style={styles.body}>
              You will need an authenticator app.
            </Text>
          </View>

          <View style={styles.section}>
            <Button label="Set up two-factor" busy={busy} onPress={() => void begin()} />
          </View>
        </ScrollView>
      </View>
    );
  }

  // ── step two: scan or type, then confirm ─────────────────────────
  const uri = otpauthUri(secret, profile.data?.email);

  return (
    <KeyboardAvoidingView
      style={styles.screen}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Heading>Add Jambo to your authenticator</Heading>

        {uri === null ? null : (
          <View style={styles.qrWrap}>
            {/*
              White, because that is what the website does — `p-3 bg-white
              rounded-3` — and because a QR needs a light ground with dark
              modules to be readable at all. This is the one place in the app
              that is deliberately not the dark surface.
            */}
            <View style={styles.qr}>
              <QRCode value={uri} size={180} backgroundColor="#ffffff" color="#000000" />
            </View>
          </View>
        )}

        <View style={styles.section}>
          <Caption>Or enter this key manually</Caption>
          {/*
            Selectable, so a long press offers Copy through the platform's own
            menu. That is the whole copy affordance and it needs no clipboard
            package — which would be a native module and therefore a prebuild.
          */}
          <Text style={styles.secret} selectable accessibilityLabel={`Setup key ${groupSecret(secret)}`}>
            {groupSecret(secret)}
          </Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.body}>1. Add the key above to your authenticator app.</Text>
          <Text style={styles.body}>2. Enter the six-digit code it shows to confirm.</Text>
        </View>

        {error === null ? null : (
          <View style={styles.section}>
            <Alert tone="error">{error}</Alert>
          </View>
        )}

        <View style={styles.section}>
          <Field
            label="Code"
            value={code}
            onChangeText={(raw) => setCode(sanitiseCode(raw))}
            placeholder="123456"
            keyboardType="number-pad"
            inputMode="numeric"
            autoComplete="one-time-code"
            textContentType="oneTimeCode"
            maxLength={6}
            returnKeyType="done"
            onSubmitEditing={() => {
              if (isCompleteCode(code) && !busy) void confirm();
            }}
          />
          <Button
            label="Confirm"
            busy={busy}
            disabled={!isCompleteCode(code)}
            onPress={() => void confirm()}
          />
        </View>

        {/*
          Cancel setup. **The website asks for nothing here and the API asks
          for the password**, because the website puts this behind
          password-confirmation middleware, which is a session concept with no
          token equivalent. So the control opens a password field rather than
          firing a request that would be refused.
        */}
        <View style={styles.cancelBlock}>
          {cancelling ? (
            <>
              <Caption>Enter your password to cancel this setup.</Caption>
              {cancelError === null ? null : (
                <View style={styles.section}>
                  <Alert tone="error">{cancelError}</Alert>
                </View>
              )}
              <PasswordField
                label="Password"
                value={cancelPassword}
                onChangeText={setCancelPassword}
                autoComplete="current-password"
                textContentType="password"
              />
              <Button
                label="Cancel setup"
                tone="danger"
                busy={busy}
                disabled={cancelPassword === ''}
                onPress={() => void cancel()}
              />
              <View style={styles.keepGoing}>
                <Button
                  label="Keep setting up"
                  tone="quiet"
                  onPress={() => {
                    setCancelling(false);
                    setCancelPassword('');
                    setCancelError(null);
                  }}
                />
              </View>
            </>
          ) : (
            <Button label="Cancel setup" tone="quiet" onPress={() => setCancelling(true)} />
          )}
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

/**
 * A refusal turned into something worth reading.
 *
 * An `ApiError` is an answer and carries the server's own wording, which is
 * written for viewers. A `NetworkError` is not an answer at all and must not
 * be reported as one: telling somebody their code was wrong because a tower
 * handed off mid-request is the failure this distinction exists to prevent.
 */
function messageFor(caught: unknown, fallback: string): string {
  if (caught instanceof NetworkError) {
    return 'Could not reach Jambo. Check your connection and try again.';
  }

  if (caught instanceof ApiError && caught.message !== '') {
    return caught.message;
  }

  return fallback;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg },

  section: { marginTop: spacing.lg, gap: spacing.sm },
  body: { ...typography.body, color: colors.textMuted },

  qrWrap: { alignItems: 'center', marginTop: spacing.xl },
  qr: { backgroundColor: '#ffffff', padding: spacing.md, borderRadius: radius.card },

  secret: {
    ...typography.body,
    fontFamily: Platform.select({ android: 'monospace', default: fonts.medium }),
    fontSize: 18,
    letterSpacing: 1,
    color: colors.primary,
    backgroundColor: colors.inputBg,
    borderRadius: radius.input,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },

  cancelBlock: { marginTop: spacing.xxl, gap: spacing.sm },
  keepGoing: { marginTop: spacing.sm },
});
