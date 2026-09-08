import { tokens } from './tokens';

/**
 * The app's design system, built on `tokens.ts` — which is generated from the
 * rendered site and must not be edited by hand.
 *
 * The split between the two files is the point. `tokens.ts` is what the
 * website *is*: every value in it was read off a real element and carries the
 * selector it came from. This file is what the app *does with that*: semantic
 * names, the handful of values the site does not express (a spacing scale, a
 * touch minimum), and the two places the app deliberately departs from the
 * site, both named below.
 *
 * A colour typed as a hex literal in a component fails review. If a screen
 * needs a colour that is not here, the question is what it is called, not what
 * it looks like.
 */

export const colors = {
  /** Pure black, and that is what the site renders — not the #0b0d17 the PWA manifest declares. */
  background: tokens.color.bg,
  /** Panels and cards: the sign-in card's translucent charcoal. */
  surface: tokens.color.surface,
  header: tokens.color.header,

  primary: tokens.color.primary,
  onPrimary: tokens.color.onPrimary,
  link: tokens.color.link,

  text: tokens.color.text,
  textMuted: tokens.color.textMuted,
  placeholder: tokens.color.placeholder,
  /** The site sets its form fields in pure white, a step brighter than body text. */
  fieldText: tokens.color.fieldText,

  /**
   * Resting and focused, captured separately.
   *
   * They have to be: the sign-in page autofocuses its email input, so a naive
   * capture reads the *focused* border — the primary blue — and calls it the
   * neutral one. Every quiet outline in the app would then be bright blue and
   * every focused field indistinguishable from a resting one.
   */
  border: tokens.color.border,
  borderFocus: tokens.color.borderFocus,
  /** The hairline on the sign-in card, and the divider between list rows. */
  divider: tokens.color.surfaceBorder,

  inputBg: tokens.color.inputBg,
  inputBgFocus: tokens.color.inputBgFocus,

  /**
   * The error and success states, from the site's own alert styles.
   *
   * **Not `tokens.color.danger`.** That is the site's `--bs-danger` and it is
   * `#545e75`, a slate blue: whoever themed this build overrode Bootstrap's
   * red and nothing on the site uses it for errors. Reaching for the
   * conventionally-named token would have produced an error message in a
   * colour that reads as ordinary text. The values below come from
   * `.jambo-auth-alert`, which is what the website actually shows a viewer
   * when their password is wrong.
   */
  errorBg: tokens.color.errorBg,
  errorText: tokens.color.errorText,
  errorBorder: tokens.color.errorBorder,
  okBg: tokens.color.okBg,
  okText: tokens.color.okText,
  okBorder: tokens.color.okBorder,

  warning: tokens.color.warning,
} as const;

/**
 * The spacing scale.
 *
 * The site is Bootstrap and expresses spacing as utility classes rather than
 * as a scale a script can read, so this is the app's own — anchored to the one
 * rhythm value that *was* captured, `space.railGap` (24), so the app's spacing
 * and the site's rail rhythm are the same number rather than two numbers that
 * happen to look similar.
 */
export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: tokens.space.railGap,
  xxl: 32,
} as const;

export const radius = {
  input: tokens.radius.input,
  card: tokens.radius.card,
  surface: tokens.radius.surface,
  alert: tokens.radius.alert,
  /** The site's buttons are capsules — 600px of radius at a 46px height. */
  pill: tokens.radius.pill,
} as const;

export const fonts = {
  regular: 'Roboto_400Regular',
  medium: 'Roboto_500Medium',
  bold: 'Roboto_700Bold',
  black: 'Roboto_900Black',
} as const;

export const typography = {
  /** The sign-in greeting. Black rather than Bold: at 34pt the two read as
   *  different designs, not different weights. */
  greeting: { fontFamily: fonts.black, fontSize: 34, lineHeight: 42 },
  title: { fontFamily: fonts.bold, fontSize: 24, lineHeight: 32 },
  heading: { fontFamily: fonts.medium, fontSize: 18, lineHeight: 24 },
  body: {
    fontFamily: fonts.regular,
    fontSize: tokens.font.size.body,
    lineHeight: tokens.font.lineHeight.body,
  },
  field: {
    fontFamily: fonts.regular,
    fontSize: tokens.font.size.field,
    lineHeight: 20,
  },
  caption: {
    fontFamily: fonts.regular,
    fontSize: tokens.font.size.caption,
    lineHeight: 20,
  },
  button: {
    fontFamily: fonts.medium,
    fontSize: tokens.font.size.field,
    lineHeight: 20,
  },
  /** Rail headings, for slice 2b — captured now so the value is not invented later. */
  railHeading: {
    fontFamily: fonts.medium,
    fontSize: tokens.font.size.railHeading,
    lineHeight: 22,
  },
} as const;

/**
 * The sign-in screen's own surface treatment.
 *
 * These come from Rio's mockup (2026-09-09) rather than from `tokens.ts`, and
 * the split matters: `tokens.ts` is what the *website* renders and is
 * regenerated from it, while these describe a screen the website has no
 * equivalent of — a glassy card over an ambient glow. Keeping them here rather
 * than as literals in the component is the same rule as everywhere else: the
 * design system is the one place a raw value may live.
 *
 * The blue itself is still `colors.primary`, so this screen cannot drift away
 * from the site's brand colour even though its surfaces are its own.
 */
export const auth = {
  /** The card: a deep navy-black, lifted off the page background. */
  /**
   * The auth card never grows past this, however wide the screen is.
   *
   * Without a cap the card takes the full width of whatever it is on, and a
   * rotated phone or a 10" tablet turns a sign-in form into a single line of
   * inputs 2800px across — with the button pushed off the bottom, because the
   * card grows sideways while the viewport loses height. A form is read and
   * filled in a column; the column has a comfortable width and it does not
   * change because the device turned.
   */
  cardMaxWidth: 440,

  cardBg: 'rgba(9, 14, 24, 0.72)',
  cardBorder: 'rgba(56, 132, 255, 0.28)',
  cardRadius: 24,

  /** Inputs sit slightly lighter than the card so they read as wells. */
  inputBg: 'rgba(255, 255, 255, 0.04)',
  inputBorder: 'rgba(255, 255, 255, 0.09)',
  inputRadius: 12,
  inputHeight: 56,

  /** The focused field takes the brand blue and a glow, as the mockup shows. */
  focusGlow: 'rgba(26, 152, 255, 0.35)',

  /** The primary button's gradient, light to deep, left to right. */
  buttonFrom: '#2C9BFF',
  buttonTo: '#0A63E8',
  buttonRadius: 12,
  buttonHeight: 56,

  /** Secondary surfaces: the social buttons and the divider rule. */
  quietBg: 'rgba(255, 255, 255, 0.03)',
  quietBorder: 'rgba(255, 255, 255, 0.09)',
  rule: 'rgba(255, 255, 255, 0.12)',

  /** Ambient glow strengths for AuthBackground. */
  glowStrong: 0.42,
  glowSoft: 0.24,

  /** The play watermark over the card corner. */
  watermarkFill: 0.3,
  watermarkStroke: 0.28,
} as const;

/**
 * The heights the site draws, and the target the platform requires.
 *
 * The site's field is 44dp and its button 46dp. Android's accessibility
 * minimum is 48. Rather than resolve that by making the app look different
 * from the website — which the project's ground rules forbid — controls are
 * *drawn* at the site's height and given a touch target padded out to 48 with
 * `hitSlop`. The design is unchanged and the target is reachable, which is the
 * only version of this where nobody loses.
 */
export const FIELD_HEIGHT = tokens.size.inputHeight;
export const BUTTON_HEIGHT = tokens.size.buttonHeight;
export const MIN_TOUCH_TARGET = 48;

/** Vertical hitSlop that lifts a control of `height` to the platform minimum. */
export function touchPadding(height: number): number {
  return Math.max(0, Math.ceil((MIN_TOUCH_TARGET - height) / 2));
}
