import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Envelope, Lock, User } from 'phosphor-react-native';

import { ApiError, NetworkError } from '../api/errors';
import { useAuth } from '../auth/AuthProvider';
import { AuthBackground } from '../ui/AuthBackground';
import { AuthField, GradientButton, LinkText, RevealToggle } from '../ui/authComponents';
import { Alert } from '../ui/components';
import { auth, colors, spacing, typography } from '../ui/theme';
import type { AuthScreenProps } from '../navigation/types';

type Fields = {
  first_name: string;
  last_name: string;
  username: string;
  email: string;
  password: string;
  password_confirmation: string;
};

const EMPTY: Fields = {
  first_name: '',
  last_name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
};

export function RegisterScreen({ navigation }: AuthScreenProps<'Register'>) {
  const { register } = useAuth();

  const [fields, setFields] = useState<Fields>(EMPTY);
  const [reveal, setReveal] = useState(false);
  const [busy, setBusy] = useState(false);
  const [banner, setBanner] = useState<string | null>(null);
  const [errors, setErrors] = useState<Partial<Record<keyof Fields, string>>>({});

  const set = (key: keyof Fields) => (value: string) => setFields((f) => ({ ...f, [key]: value }));

  const submit = async () => {
    if (busy) return;

    setBusy(true);
    setBanner(null);
    setErrors({});

    try {
      await register({
        ...fields,
        first_name: fields.first_name.trim(),
        last_name: fields.last_name.trim(),
        username: fields.username.trim(),
        email: fields.email.trim(),
      });
      // Registered and signed in: the provider switches stacks and this
      // screen unmounts, exactly as the website logs you in on sign-up.
    } catch (error) {
      if (error instanceof ApiError && error.code === 'VALIDATION_FAILED') {
        // The server owns these rules — the same ones the website enforces —
        // so its messages are shown rather than restated here. Duplicating
        // password policy in the client is how the two drift apart.
        const next: Partial<Record<keyof Fields, string>> = {};
        for (const key of Object.keys(EMPTY) as (keyof Fields)[]) {
          const message = error.fieldError(key);
          if (message !== null) next[key] = message;
        }
        setErrors(next);
        setBanner(Object.keys(next).length === 0 ? error.message : null);
      } else if (error instanceof NetworkError) {
        setBanner('Could not reach Jambo. Check your connection and try again.');
      } else if (error instanceof ApiError && error.code === 'RATE_LIMITED') {
        const seconds = error.retryAfterSeconds;
        setBanner(
          seconds === null
            ? 'Too many attempts. Wait a moment and try again.'
            : `Too many attempts. Try again in ${seconds} seconds.`,
        );
      } else {
        setBanner(error instanceof ApiError ? error.message : 'Something went wrong.');
      }
    } finally {
      setBusy(false);
    }
  };

  const complete = Object.values(fields).every((v) => v.trim() !== '');

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
                Create account
              </Text>
              <Text style={styles.subtitle}>Start watching on this device.</Text>

              <View style={styles.form}>
                {banner !== null ? <Alert tone="error">{banner}</Alert> : null}

                <View style={styles.row}>
                  <View style={styles.rowItem}>
                    <AuthField
                      label="First name"
                      icon={<User size={22} color={colors.placeholder} weight="regular" />}
                      value={fields.first_name}
                      onChangeText={set('first_name')}
                      error={errors.first_name ?? null}
                      placeholder="First"
                      autoCapitalize="words"
                      editable={!busy}
                    />
                  </View>
                  <View style={styles.rowItem}>
                    <AuthField
                      label="Last name"
                      icon={<User size={22} color={colors.placeholder} weight="regular" />}
                      value={fields.last_name}
                      onChangeText={set('last_name')}
                      error={errors.last_name ?? null}
                      placeholder="Last"
                      autoCapitalize="words"
                      editable={!busy}
                    />
                  </View>
                </View>

                <AuthField
                  label="Username"
                  icon={<User size={22} color={colors.placeholder} weight="regular" />}
                  value={fields.username}
                  onChangeText={set('username')}
                  error={errors.username ?? null}
                  placeholder="yourname"
                  autoCapitalize="none"
                  autoCorrect={false}
                  editable={!busy}
                />

                <AuthField
                  label="Email"
                  icon={<Envelope size={22} color={colors.placeholder} weight="regular" />}
                  value={fields.email}
                  onChangeText={set('email')}
                  error={errors.email ?? null}
                  placeholder="you@example.com"
                  autoCapitalize="none"
                  autoComplete="email"
                  autoCorrect={false}
                  keyboardType="email-address"
                  textContentType="emailAddress"
                  editable={!busy}
                />

                <View>
                  <AuthField
                    label="Password"
                    icon={<Lock size={22} color={colors.placeholder} weight="regular" />}
                    value={fields.password}
                    onChangeText={set('password')}
                    error={errors.password ?? null}
                    placeholder="Your password"
                    secureTextEntry={!reveal}
                    autoCapitalize="none"
                    autoComplete="new-password"
                    textContentType="newPassword"
                    editable={!busy}
                  />
                  <View style={styles.revealAnchor}>
                    <RevealToggle shown={reveal} onToggle={() => setReveal((r) => !r)} />
                  </View>
                </View>

                <AuthField
                  label="Confirm password"
                  icon={<Lock size={22} color={colors.placeholder} weight="regular" />}
                  value={fields.password_confirmation}
                  onChangeText={set('password_confirmation')}
                  error={errors.password_confirmation ?? null}
                  placeholder="Repeat your password"
                  secureTextEntry={!reveal}
                  autoCapitalize="none"
                  returnKeyType="go"
                  onSubmitEditing={() => {
                    if (complete && !busy) void submit();
                  }}
                  editable={!busy}
                />

                <GradientButton
                  label="Create account"
                  busy={busy}
                  disabled={!complete || busy}
                  onPress={() => void submit()}
                />
              </View>
            </View>

            <View style={styles.footer}>
              <Text style={styles.footerText}>Already have an account? </Text>
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
  form: { marginTop: spacing.xl },
  row: { flexDirection: 'row', gap: spacing.md },
  rowItem: { flex: 1 },
  revealAnchor: { position: 'absolute', right: spacing.lg, top: 45 },
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
