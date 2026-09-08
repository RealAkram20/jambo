import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Envelope } from 'phosphor-react-native';

import { api } from '../api/jambo';
import { ApiError, NetworkError } from '../api/errors';
import { AuthBackground } from '../ui/AuthBackground';
import { AuthField, GradientButton, LinkText } from '../ui/authComponents';
import { Alert } from '../ui/components';
import { auth, colors, spacing, typography } from '../ui/theme';
import type { AuthScreenProps } from '../navigation/types';

export function ForgotPasswordScreen({ navigation }: AuthScreenProps<'ForgotPassword'>) {
  const [email, setEmail] = useState('');
  const [busy, setBusy] = useState(false);
  const [sent, setSent] = useState(false);
  const [banner, setBanner] = useState<string | null>(null);

  const submit = async () => {
    if (busy) return;

    setBusy(true);
    setBanner(null);

    try {
      await api.forgotPassword(email.trim());
      setSent(true);
    } catch (error) {
      /*
       * Only a transport failure is worth reporting here.
       *
       * The endpoint deliberately answers the same whether or not the address
       * has an account, so that it cannot be used to discover which emails are
       * registered. Showing a different message on a refusal would give that
       * away from the client instead — so anything that is not a network
       * failure still shows the neutral confirmation.
       */
      if (error instanceof NetworkError) {
        setBanner('Could not reach Jambo. Check your connection and try again.');
      } else if (error instanceof ApiError && error.code === 'RATE_LIMITED') {
        const seconds = error.retryAfterSeconds;
        setBanner(
          seconds === null
            ? 'Too many attempts. Wait a moment and try again.'
            : `Too many attempts. Try again in ${seconds} seconds.`,
        );
      } else {
        setSent(true);
      }
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={styles.root}>
      <AuthBackground />

      <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={styles.fill}
        >
          <ScrollView
            contentContainerStyle={styles.scroll}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
          >
            <View style={styles.card}>
              <Text accessibilityRole="header" style={styles.greeting}>
                Reset password
              </Text>
              <Text style={styles.subtitle}>
                We&rsquo;ll send a reset link to your email address.
              </Text>

              <View style={styles.form}>
                {banner !== null ? <Alert tone="error">{banner}</Alert> : null}

                {sent ? (
                  <>
                    {/* The same words whatever the server found, for the reason
                        in submit(): the message must not reveal whether the
                        address has an account. */}
                    <Alert tone="ok">
                      If that address has a Jambo account, a reset link is on its way. The link
                      opens on the Jambo website.
                    </Alert>
                    <GradientButton label="Back to sign in" onPress={() => navigation.goBack()} />
                  </>
                ) : (
                  <>
                    <AuthField
                      label="Email"
                      icon={<Envelope size={22} color={colors.placeholder} weight="regular" />}
                      value={email}
                      onChangeText={setEmail}
                      placeholder="you@example.com"
                      autoCapitalize="none"
                      autoComplete="email"
                      autoCorrect={false}
                      keyboardType="email-address"
                      textContentType="emailAddress"
                      returnKeyType="go"
                      onSubmitEditing={() => {
                        if (email.trim() !== '' && !busy) void submit();
                      }}
                      editable={!busy}
                    />

                    <GradientButton
                      label="Send reset link"
                      busy={busy}
                      disabled={email.trim() === '' || busy}
                      onPress={() => void submit()}
                    />
                  </>
                )}
              </View>
            </View>

            <View style={styles.footer}>
              <Text style={styles.footerText}>Remembered it? </Text>
              <LinkText label="Sign in" onPress={() => navigation.goBack()} />
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: colors.background },
  safe: { flex: 1 },
  fill: { flex: 1 },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: spacing.xl,
  },
  card: {
    width: '100%',
    maxWidth: auth.cardMaxWidth,
    backgroundColor: auth.cardBg,
    borderRadius: auth.cardRadius,
    borderWidth: 1,
    borderColor: auth.cardBorder,
    padding: spacing.xxl,
    overflow: 'hidden',
  },
  greeting: { ...typography.greeting, color: colors.fieldText },
  subtitle: {
    fontFamily: 'Roboto_400Regular',
    fontSize: 17,
    lineHeight: 24,
    color: colors.textMuted,
    marginTop: spacing.sm,
  },
  form: { marginTop: spacing.xxl },
  footer: {
    width: '100%',
    maxWidth: auth.cardMaxWidth,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: spacing.xxl,
  },
  footerText: { fontFamily: 'Roboto_400Regular', fontSize: 15, color: colors.textMuted },
});
