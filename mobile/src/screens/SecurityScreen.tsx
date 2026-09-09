import { useCallback, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';

import { api } from '../api/jambo';
import { ApiError, NetworkError } from '../api/errors';
import { colors, fonts, radius, spacing, typography } from '../ui/theme';
import { Alert, Button, Caption, Divider, ErrorState, Heading, Loading } from '../ui/components';
import type { AppScreenProps } from '../navigation/types';

/**
 * What protects this account, and what does not.
 *
 * Read plus one action. The one action is resending the verification email,
 * which is the only thing on this screen the API can complete end to end from
 * a handset today.
 *
 * **Enrolling in two-factor is shown as state, not offered as a button.** The
 * endpoints exist (`POST /account/2fa`, `/2fa/confirm`,
 * `/2fa/recovery-codes`), but enrolment is a flow, not a toggle: it returns a
 * secret and an otpauth URI that the viewer has to get into an authenticator,
 * then confirms with a code, then must be shown recovery codes exactly once
 * and told to save them. Half of that — a switch that turns 2FA on and never
 * shows the recovery codes — locks people out of their own accounts. It earns
 * its own slice.
 *
 * **Changing a password is likewise absent** rather than half-built: the
 * endpoint is there, the screen is a form with its own validation and its own
 * "you are about to be signed out everywhere" question, and it belongs with
 * the profile-editing slice.
 */
export function SecurityScreen(_: AppScreenProps<'Security'>) {
  const [sent, setSent] = useState(false);
  const [sendError, setSendError] = useState<string | null>(null);
  const [sending, setSending] = useState(false);

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['security'],
    queryFn: () => api.security(),
  });

  const resend = useCallback(async () => {
    setSending(true);
    setSendError(null);
    try {
      await api.resendVerificationEmail();
      setSent(true);
    } catch (caught) {
      setSendError(
        caught instanceof NetworkError
          ? 'Could not reach Jambo. Check your connection and try again.'
          : caught instanceof ApiError
            ? caught.message
            : 'We could not send the email. Try again in a moment.',
      );
    } finally {
      setSending(false);
    }
  }, []);

  if (isPending) return <Loading label="Loading your security settings" />;

  if (isError) {
    return (
      <View style={styles.screen}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'We could not load your security settings.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </View>
    );
  }

  const twoFactorOn = data.twoFactor?.enabled === true;

  return (
    <View style={styles.screen}>
      <ScrollView contentContainerStyle={styles.content}>
        <Heading>Two-factor authentication</Heading>
        <View style={styles.card}>
          <StatusRow
            label="Status"
            value={twoFactorOn ? 'On' : 'Off'}
            tone={twoFactorOn ? 'ok' : 'muted'}
          />
          <Divider />
          <Caption>
            {twoFactorOn
              ? 'You are asked for a code from your authenticator app when you sign in.'
              : 'Turn this on from the Jambo website. Setting it up needs your authenticator app and your recovery codes, which are shown once.'}
          </Caption>
        </View>

        <View style={styles.section}>
          <Heading>Email</Heading>
          <View style={styles.card}>
            <StatusRow
              label="Verified"
              value={data.emailVerified ? 'Yes' : 'Not yet'}
              tone={data.emailVerified ? 'ok' : 'warn'}
            />
          </View>

          {data.emailVerified ? null : (
            <View style={styles.action}>
              {sendError !== null ? <Alert tone="error">{sendError}</Alert> : null}
              {sent ? (
                <Alert tone="ok">
                  Sent. Check your inbox, and your spam folder if it is not there.
                </Alert>
              ) : (
                <Button
                  label="Resend verification email"
                  tone="quiet"
                  busy={sending}
                  onPress={() => void resend()}
                />
              )}
            </View>
          )}
        </View>

        <View style={styles.section}>
          <Heading>Google sign-in</Heading>
          <View style={styles.card}>
            {/*
              This reports whether the *server* has Google configured, not
              whether this account is linked — the flag is `google_enabled` on
              the security resource and that is what it means. Saying "not
              linked" would be a different and unchecked claim.
            */}
            <StatusRow
              label="Available"
              value={data.googleEnabled ? 'Yes' : 'Not configured'}
              tone={data.googleEnabled ? 'ok' : 'muted'}
            />
          </View>
        </View>

        <Caption>
          Passwords and two-factor setup are changed on the Jambo website.
        </Caption>
      </ScrollView>
    </View>
  );
}

function StatusRow({
  label,
  value,
  tone,
}: {
  label: string;
  value: string;
  tone: 'ok' | 'warn' | 'muted';
}) {
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      {/*
        The value is a word, so the state never depends on the colour. Colour
        is the emphasis, not the message.
      */}
      <Text
        style={[
          styles.rowValue,
          tone === 'ok' && styles.ok,
          tone === 'warn' && styles.warn,
        ]}
      >
        {value}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { padding: spacing.lg },
  section: { marginTop: spacing.xl },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.surface,
    padding: spacing.xl,
    gap: spacing.sm,
  },
  action: { marginTop: spacing.lg },

  row: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md },
  rowLabel: { ...typography.body, color: colors.textMuted },
  rowValue: { ...typography.body, fontFamily: fonts.medium, color: colors.text },
  ok: { color: colors.okText },
  warn: { color: colors.warning },
});
