import { Platform, StyleSheet, Text, View } from 'react-native';

import { Caption } from '../../ui/components';
import { colors, fonts, radius, spacing, typography } from '../../ui/theme';

/**
 * The recovery codes, as the website lists them.
 *
 * `security.blade.php` renders them in a monospace block on a dark ground
 * under the caption *"Use one of these if you lose your authenticator. Each
 * code works once."*, and warns in amber when the batch is empty. All three
 * are here.
 *
 * **Monospace and selectable**, because these are the only string in the app a
 * viewer is expected to copy out and keep. Selection gives Copy through the
 * platform's own long-press menu, which is the whole affordance and costs no
 * clipboard package.
 *
 * Shared by the enrolment flow and the security screen rather than written
 * twice: the same eight codes shown in two places must look like the same
 * eight codes.
 */
export function RecoveryCodes({ codes }: { codes: string[] }) {
  return (
    <View style={styles.wrap}>
      <Caption>Recovery codes</Caption>
      <Text style={styles.blurb}>
        Use one of these if you lose your authenticator. Each code works once.
      </Text>

      {codes.length === 0 ? (
        /*
          The website's own amber warning. An empty batch is not a neutral
          state: it means the account has spent every code and one lost phone
          away from being locked out.
        */
        <Text accessibilityLiveRegion="polite" style={styles.empty}>
          No recovery codes left. Generate a new batch.
        </Text>
      ) : (
        <View style={styles.block}>
          {codes.map((code) => (
            <Text key={code} style={styles.code} selectable>
              {code}
            </Text>
          ))}
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: spacing.sm },
  blurb: { ...typography.caption, color: colors.textMuted },

  block: {
    backgroundColor: colors.inputBg,
    borderRadius: radius.surface,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.divider,
    padding: spacing.lg,
    gap: 6,
  },
  code: {
    fontFamily: Platform.select({ android: 'monospace', default: fonts.medium }),
    fontSize: 15,
    lineHeight: 22,
    letterSpacing: 0.5,
    color: colors.text,
  },

  empty: { ...typography.caption, color: colors.warning },
});
