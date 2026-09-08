import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Envelope, Lock } from 'phosphor-react-native';

import { ApiError, NetworkError } from '../api/errors';
import { useAuth } from '../auth/AuthProvider';
import { AuthBackground, PlayWatermark } from '../ui/AuthBackground';
import {
  AuthField,
  Checkbox,
  GoogleMark,
  GradientButton,
  LinkText,
  OrDivider,
  QuietButton,
  RevealToggle,
} from '../ui/authComponents';
import { Alert } from '../ui/components';
import { auth, colors, spacing, typography } from '../ui/theme';
import type { AuthScreenProps } from '../navigation/types';

/**
 * The message a viewer reads, chosen by the server's `code`.
 *
 * Never by message text: `docs/api/openapi.yaml` is explicit that codes are
 * the contract and copy is reworded freely. A screen that switches on text
 * breaks on a day nobody touched the app.
 */
function messageFor(error: unknown): string {
  if (error instanceof NetworkError) {
    return 'Could not reach Jambo. Check your connection and try again.';
  }

  if (error instanceof ApiError) {
    switch (error.code) {
      case 'INVALID_CREDENTIALS':
        return 'That email and password do not match an account.';
      case 'ACCOUNT_DEACTIVATED':
        return 'This account has been deactivated. Contact Jambo to reopen it.';
      case 'RATE_LIMITED': {
        // A 429 from Laravel's throttle carries no envelope, so `message` is a
        // framework string. The seconds are the only useful thing in it.
        const seconds = error.retryAfterSeconds;
        return seconds === null
          ? 'Too many attempts. Wait a moment and try again.'
          : `Too many attempts. Try again in ${seconds} seconds.`;
      }
      case 'VALIDATION_FAILED':
        // Shown under the fields themselves; the banner would repeat it.
        return '';
      default:
        return error.message;
    }
  }

  return 'Something went wrong. Please try again.';
}

export function SignInScreen({ navigation }: AuthScreenProps<'SignIn'>) {
  const { signIn, config } = useAuth();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [reveal, setReveal] = useState(false);
  const [remember, setRemember] = useState(true);
  const [busy, setBusy] = useState(false);
  const [banner, setBanner] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{ email?: string; password?: string }>({});

  /*
   * Whether this server can complete a Google sign-in at all.
   *
   * From `GET /app/config`, not from a build flag: the same APK runs against a
   * server that has Google configured and one that does not, and a button that
   * can only fail is worse than no button — the viewer cannot tell whether
   * they typed something wrong or the platform is broken. Undefined (config
   * not loaded yet, or an older server) is treated as off, the safe direction.
   */
  const googleEnabled = config?.features?.google_sign_in === true;

  const submit = async () => {
    if (busy) return;

    setBusy(true);
    setBanner(null);
    setFieldErrors({});

    try {
      const outcome = await signIn(email.trim(), password, { remember });

      if (outcome.kind === 'two-factor') {
        navigation.navigate('TwoFactor', {
          challengeToken: outcome.challengeToken,
          email: email.trim(),
        });
      }
      // On `signedIn` the provider switches stacks; this screen unmounts.
    } catch (error) {
      if (error instanceof ApiError && error.code === 'VALIDATION_FAILED') {
        setFieldErrors({
          ...(error.fieldError('email') !== null ? { email: error.fieldError('email')! } : {}),
          ...(error.fieldError('password') !== null
            ? { password: error.fieldError('password')! }
            : {}),
        });
      }

      const message = messageFor(error);
      if (message !== '') setBanner(message);
    } finally {
      setBusy(false);
    }
  };

  const canSubmit = email.trim() !== '' && password !== '' && !busy;

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
              <View style={styles.watermark} pointerEvents="none">
                <PlayWatermark />
              </View>

              <Text accessibilityRole="header" style={styles.greeting}>
                Welcome back 👋
              </Text>
              <Text style={styles.subtitle}>Sign in to keep watching.</Text>

              <View style={styles.form}>
                {banner !== null ? <Alert tone="error">{banner}</Alert> : null}

                <AuthField
                  label="Email"
                  icon={<Envelope size={22} color={colors.placeholder} weight="regular" />}
                  value={email}
                  onChangeText={setEmail}
                  error={fieldErrors.email ?? null}
                  placeholder="you@example.com"
                  autoCapitalize="none"
                  autoComplete="email"
                  autoCorrect={false}
                  keyboardType="email-address"
                  textContentType="emailAddress"
                  returnKeyType="next"
                  editable={!busy}
                />

                <View>
                  <AuthField
                    label="Password"
                    icon={<Lock size={22} color={colors.placeholder} weight="regular" />}
                    accessory={
                      <LinkText
                        label="Forgot password?"
                        onPress={() => navigation.navigate('ForgotPassword')}
                      />
                    }
                    value={password}
                    onChangeText={setPassword}
                    error={fieldErrors.password ?? null}
                    placeholder="Your password"
                    secureTextEntry={!reveal}
                    autoCapitalize="none"
                    autoComplete="current-password"
                    textContentType="password"
                    returnKeyType="go"
                    onSubmitEditing={() => {
                      if (canSubmit) void submit();
                    }}
                    editable={!busy}
                  />
                  {/* Overlaid on the well rather than placed in the text flow,
                      so the input keeps its full width. */}
                  <View style={styles.revealAnchor}>
                    <RevealToggle shown={reveal} onToggle={() => setReveal((r) => !r)} />
                  </View>
                </View>

                <Checkbox
                  checked={remember}
                  onToggle={() => setRemember((r) => !r)}
                  label="Remember me"
                />

                <GradientButton
                  label="Sign in"
                  busy={busy}
                  disabled={!canSubmit}
                  onPress={() => void submit()}
                />

                {/*
                  The social block appears only when the server can actually
                  complete it. Apple is deliberately absent: this API has no
                  Apple endpoint, no client configuration and nothing in the
                  spec, so the mockup's button would be one that can only fail.
                  Raised with Rio rather than shipped dark.
                */}
                {googleEnabled ? (
                  <>
                    <OrDivider />
                    <View style={styles.socialRow}>
                      <QuietButton
                        label="Google"
                        icon={<GoogleMark />}
                        onPress={() => navigation.navigate('SignIn')}
                      />
                    </View>
                  </>
                ) : null}
              </View>
            </View>

            <View style={styles.footer}>
              <Text style={styles.footerText}>Don&rsquo;t have an account? </Text>
              <LinkText
                label="Create one"
                withArrow
                onPress={() => navigation.navigate('Register')}
              />
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
    // Horizontal centring for the capped card. Without it the card sits hard
    // against the left edge the moment the screen is wider than `cardMaxWidth`
    // — which is every landscape phone and every tablet.
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.xl,
  },

  card: {
    width: '100%',
    maxWidth: auth.cardMaxWidth,
    backgroundColor: auth.cardBg,
    borderRadius: auth.cardRadius,
    borderWidth: 1,
    borderColor: auth.cardBorder,
    padding: spacing.xxl,
    // Not clipped: in the mockup the play mark runs past the card's right
    // edge, and `overflow: hidden` would cut it off at the corner radius.
    overflow: 'visible',
  },
  // Bleeds off the card's top-right, as in the mockup. `overflow: hidden` on
  // the card clips it to the rounded corner rather than letting it float free.
  watermark: { position: 'absolute', top: -8, right: -16 },

  greeting: { ...typography.greeting, color: colors.fieldText },
  subtitle: {
    fontFamily: 'Roboto_400Regular',
    fontSize: 17,
    lineHeight: 24,
    color: colors.textMuted,
    marginTop: spacing.sm,
  },

  form: { marginTop: spacing.xxl },
  revealAnchor: { position: 'absolute', right: spacing.lg, top: 45 },

  socialRow: { flexDirection: 'row', gap: spacing.lg },

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
