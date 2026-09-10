import { StyleSheet, Text, View } from 'react-native';

import { Check } from 'phosphor-react-native';

import { colors, radius, spacing, typography } from './theme';
import { type PasswordStrength, passwordStrength, STRENGTH_SEGMENTS } from './passwordStrength';

/**
 * Drawn from the scorer's own ceiling rather than a literal four, so the bar
 * can never have more boxes than the scoring can fill — or fewer, which would
 * silently discard the top of the scale.
 */
const SEGMENTS = Array.from({ length: STRENGTH_SEGMENTS }, (_, index) => index);

/**
 * Four segments, a word, and one piece of advice.
 *
 * Ported from Kangaru with the palette swapped, on Rio's instruction of
 * 2026-09-10 to reuse the one we already built rather than write another.
 *
 * **The word is not decoration.** The `screen` skill forbids meaning carried
 * by colour alone, and a bar that goes from red to green with no label is
 * exactly that — unreadable to a colour-blind viewer and to anyone glancing at
 * a phone in Ugandan daylight.
 *
 * Renders nothing at all for an empty field: a meter reading "Too short"
 * against a box nobody has typed in yet is a scolding, not a guide.
 */
export function PasswordMeter({ password }: { password: string }) {
  const strength = passwordStrength(password);

  if (password === '') {
    return null;
  }

  /*
   * The rule that caps the score, when one does.
   *
   * A dictionary hit is the only case where the sentence underneath is the
   * *reason* for the verdict rather than a nudge, and Rio's `Jambo2026@N`
   * showed why that has to look different: four green ticks over a one-segment
   * bar, with the explanation set in the same quiet grey as every other hint,
   * read as a contradiction. Now the broken rule is in the checklist and its
   * sentence carries the weight.
   */
  const broken = strength.requirements.find((rule) => rule.key === 'guessable' && !rule.met);

  return (
    <View style={styles.block}>
      <View style={styles.row}>
        <View style={styles.track} accessibilityElementsHidden importantForAccessibility="no">
          {SEGMENTS.map((segment) => (
            <View
              key={segment}
              style={[
                styles.segment,
                segment < strength.score && { backgroundColor: fillFor(strength) },
              ]}
            />
          ))}
        </View>

        {/*
          The bar is hidden from the screen reader and this carries it, so it
          says the count as well as the word — "2 of 4" is the thing the sighted
          viewer can see and the blind one otherwise could not.
        */}
        <Text
          style={[styles.label, { color: fillFor(strength) }]}
          numberOfLines={1}
          accessibilityLabel={`Password strength: ${strength.label}, ${strength.score} of ${STRENGTH_SEGMENTS}.`}
        >
          {strength.label}
        </Text>
      </View>

      {/*
        The scale, in the open. A bar with a hidden standard grades a viewer
        against a rule nobody told them — which is how a password holding a
        capital, a number and a symbol at the stated minimum came to read
        "Fair" and be asked for four more characters, with no way to learn what
        the last two segments wanted.
      */}
      <View style={styles.checklist}>
        {strength.requirements.map((requirement) => (
          <Requirement key={requirement.key} label={requirement.label} met={requirement.met} />
        ))}
      </View>

      {/*
        `polite`, never assertive: this changes on every keystroke, and an
        assertive region would interrupt the typing it is describing.
      */}
      {strength.hint !== null && (
        <Text
          style={[styles.hint, broken !== undefined && styles.hintBroken]}
          accessibilityLiveRegion="polite"
        >
          {strength.hint}
        </Text>
      )}
    </View>
  );
}

/**
 * One rule, and whether it is met.
 *
 * **The word carries it, never the tick alone.** `docs/screen-rules.md` §6
 * again: a row of green and grey glyphs is meaning in colour, and the
 * announcement says "Met" or "Not yet" in as many words so a screen reader
 * hears the state rather than the decoration.
 */
function Requirement({ label, met }: { label: string; met: boolean }) {
  return (
    <View style={styles.requirement} accessible accessibilityLabel={`${met ? 'Met' : 'Not yet'}: ${label}`}>
      {met ? (
        <View style={styles.tick}>
          <Check size={10} color={colors.background} weight="bold" />
        </View>
      ) : (
        <View style={styles.dot} />
      )}

      <Text style={[styles.requirementLabel, met && styles.requirementMet]} numberOfLines={1}>
        {label}
      </Text>
    </View>
  );
}

/**
 * Tokens only — a raw hex at a call site fails review.
 *
 * Jambo's palette has no `success` or `danger`: its alert colours are
 * `okText` and `errorText`, captured from the site's own alert styles, and
 * `warning` is the site's amber. All four are measured against black at 12:1
 * or better, so the word is legible before the colour says anything.
 */
function fillFor(strength: PasswordStrength): string {
  if (strength.level === 'strong') return colors.okText;
  if (strength.level === 'good') return colors.primary;
  if (strength.level === 'fair') return colors.warning;

  return colors.errorText;
}

const styles = StyleSheet.create({
  block: {
    marginTop: spacing.xs,
    marginBottom: spacing.md,
    gap: spacing.xs,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
  },
  track: {
    flex: 1,
    flexDirection: 'row',
    gap: spacing.xs,
  },
  segment: {
    flex: 1,
    height: 4,
    borderRadius: radius.pill,
    backgroundColor: colors.inputBg,
  },
  label: {
    ...typography.caption,
    // Fixed width so the segments do not resize as the word changes length —
    // a track that jumps between "Weak" and "Strong" reads as a glitch.
    width: 56,
    textAlign: 'right',
  },
  hint: {
    ...typography.caption,
    color: colors.textMuted,
  },
  /* The site's own alert red. Measured, not assumed: 11.12:1 on black. */
  hintBroken: { color: colors.errorText },
  checklist: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    // Two per row on a 360dp handset, four on nothing this app runs on. The
    // wrap is the layout rather than a fixed grid, so a longer label in
    // translation reflows instead of truncating a rule.
    columnGap: spacing.md,
    rowGap: spacing.xs,
    marginTop: spacing.xs,
  },
  requirement: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.xs + 1,
  },
  tick: {
    width: 14,
    height: 14,
    borderRadius: radius.pill,
    backgroundColor: colors.okText,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dot: {
    width: 14,
    height: 14,
    borderRadius: radius.pill,
    // A ring, not a filled circle: an unmet rule is an outline waiting to be
    // completed, and a solid grey dot reads as a bullet point instead.
    borderWidth: 1.5,
    borderColor: colors.border,
  },
  requirementLabel: {
    ...typography.caption,
    color: colors.textMuted,
  },
  requirementMet: {
    color: colors.text,
  },
});
