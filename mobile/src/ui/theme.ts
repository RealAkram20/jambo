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
 * The home screen's rails, and the cards in them.
 *
 * Every value here is a token read off the rendered site. Two of them are
 * worth knowing the story of:
 *
 * `gap` is NOT `tokens.space.railGap`. That token is `--jambo-rail-gap`, which
 * jambo-header.css scopes to `.jambo-detail-rails` — the wrapper the detail,
 * watch and episode pages carry and the home page does not. Home keeps the
 * vendor's own spacing, which measures 30 at a phone viewport. Reaching for
 * the token that already existed would have made the home screen a third
 * tighter than the website's.
 *
 * `cardsPerView` is Streamit's own `data-mobile` / `data-tab` / `data-slide`
 * attributes from the rail markup. The half card is deliberate: it is the peek
 * that tells a viewer the rail scrolls sideways, and it is the reason a rail
 * must never be sized to a whole number of cards.
 */
export const rail = {
  /** Between the bottom of one rail and the heading of the next. */
  gap: tokens.space.homeRailGap,
  /** Between a rail's heading and its first card. */
  headingGap: tokens.space.homeRailHeadingGap,
  /** The gutter between two cards: the site's slide padding, doubled. */
  cardGap: tokens.space.posterGap,

  /**
   * How many cards span the width, per breakpoint, from the site's own swiper
   * config (`public/frontend/js/swiper.js` reads these off the markup).
   *
   * The width thresholds are Streamit's: 576, 768, 1025, 1500. A 10" tablet
   * and a TV therefore get the layout the website gives that width, which is
   * what makes Phase 4 a configuration rather than a second design.
   */
  cardsPerView: [
    { minWidth: 0, cards: 3.5 },
    { minWidth: 576, cards: 3.5 },
    { minWidth: 768, cards: 4 },
    { minWidth: 1025, cards: 8 },
    { minWidth: 1500, cards: 8 },
  ],

  headingColor: tokens.color.text,
  viewAllColor: tokens.color.viewAll,
  viewAllSize: tokens.font.size.viewAll,
} as const;

/**
 * The poster card.
 *
 * **The site's poster card is the poster, and nothing else.** Its title, year,
 * runtime, watchlist button and "Play now" all live in `.card-description`,
 * which is `opacity: 0; visibility: hidden` until `.iq-card:hover` — so no
 * phone viewer has ever seen them, and no remote could reach them either.
 * Reproducing that as a hover state is impossible; inventing an always-visible
 * version would be inventing styling. The card is therefore the poster, the
 * whole card is the press target, and the title travels as the accessibility
 * label exactly as the site ships it as the image's `alt`.
 */
export const card = {
  /** 0.714 — five by seven, read off the rendered box rather than assumed 2:3. */
  aspect: tokens.size.posterAspect,
  radius: tokens.radius.poster,
  titleSize: tokens.font.size.cardTitle,
  titleWeight: tokens.font.weight.cardTitle,
  titleColor: tokens.color.cardTitle,
  metaSize: tokens.font.size.cardMeta,

  /** The crown that `tier_required` draws. A circle, hence radius = width / 2. */
  badgeSize: tokens.size.premiumBadge,
  badgeRadius: tokens.radius.premiumBadge,
  badgeBg: tokens.color.premiumBadgeBg,
  badgeIcon: tokens.color.premiumBadge,
  badgeIconSize: tokens.size.premiumBadgeIcon,
} as const;

/**
 * The Top 10 rank numeral.
 *
 * Not a coloured glyph: Streamit fills the letterform with a texture image
 * through `background-clip: text`, so its computed `color` is transparent and
 * an app that read that alone would draw nothing. The app reproduces it with
 * `react-native-svg` — already a dependency — masking the same texture file,
 * which is Streamit's own and is copied out of `public/frontend/images/pages/`
 * rather than downloaded.
 */
export const topTen = {
  size: tokens.font.size.topTenNumber,
  weight: tokens.font.weight.topTenNumber,
} as const;

/**
 * Continue Watching, which is the one card on the home screen that is not a
 * poster: Streamit gives it `aspect-ratio: 5/3` and a dark gradient under the
 * still, which is what makes the title and progress strip readable over
 * arbitrary artwork.
 *
 * The gradient is captured as a CSS string and re-expressed here as the stops
 * `expo-linear-gradient` takes. The numbers are the site's, not chosen: black
 * at the bottom, clear at 51.04%, half-black at the top.
 */
export const watching = {
  aspect: tokens.size.watchingAspect,
  radius: tokens.radius.watchingCard,
  scrimColors: ['rgba(0, 0, 0, 0.5)', 'rgba(0, 0, 0, 0)', 'rgb(0, 0, 0)'] as const,
  scrimLocations: [0.01, 0.4896, 1] as const,
  progressTrack: tokens.color.progressTrack,
  progressBar: tokens.color.progressBar,
  progressHeight: tokens.size.progressBarHeight,

  /**
   * The remove button. `continue-watch-card.blade.php` sets it inline at
   * `width: 2em; height: 2em` — 32 at the site's 16px root — with a comment
   * saying it was made "slightly larger than the CSS default so it's an
   * easier tap/click target". That reasoning applies at least as much on a
   * handset, so the size is kept rather than rounded to something tidier.
   */
  removeSize: 32,
} as const;

/**
 * The bottom tab bar, which is the website's `.jambo-mobile-nav` — the fixed
 * four-tab bar it already renders below 992px. The app does not invent a
 * navigation shape; it ports the one the site has.
 */
export const tabBar = {
  background: tokens.color.tabBarBg,
  border: tokens.color.tabBarBorder,
  padding: tokens.space.tabBarPadding,
  idle: tokens.color.tabIdle,
  active: tokens.color.tabActive,
  iconSize: tokens.size.tabIcon,
  labelSize: tokens.font.size.tabLabel,
} as const;

export const hero = {
  titleSize: tokens.font.size.heroHeadline,
  titleWeight: tokens.font.weight.heroHeadline,
  tracking: tokens.size.heroTracking,
} as const;

/**
 * The focus ring, for a remote and for a keyboard.
 *
 * The site's own focused control takes the primary colour on its border
 * (`colors.borderFocus`, read from a focused sign-in field), so the app's
 * focus state is the site's focus state rather than a new idea. It is drawn on
 * every interactive element from this slice onward, phone build included:
 * ADR-0002 wants one codebase, and a focus state that only exists in the TV
 * variant is a focus state nobody tests until Phase 4.
 */
export const focus = {
  color: colors.borderFocus,
  width: 2,
  /** How far the ring sits outside the thing it rings. */
  offset: 2,
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

/**
 * The profile menu — the screen behind the header's account icon.
 *
 * From Rio's mockup of 2026-09-09, re-coloured. The mockup is crimson: its
 * selected row is a magenta gradient and its tier pill is pink. Jambo is
 * `#1a98ff`, so every one of those surfaces is expressed here in terms of the
 * brand instead, and the two values that carry the accent are `auth.buttonFrom`
 * and `auth.buttonTo` — the *same* gradient the sign-in button already draws,
 * not a second blue that happens to look similar. The menu therefore cannot
 * drift away from the button, because there is only one pair of values.
 *
 * **It is a screen, not a drawer**, by Rio's correction on the same day. The
 * background is opaque and fills the display; there is no panel width, no
 * scrim and no slide, because there is nothing behind it to see through to.
 */
export const profileMenu = {
  /** The reading column, capped so a 10" tablet gets a menu rather than a
   *  wall of 22dp icons on one side of a very wide row. Same reasoning as
   *  `auth.cardMaxWidth`. */
  maxWidth: 520,

  /** The screen's own ground. Deliberately not `colors.background`: the site's
   *  pure black is right behind poster artwork and too hard behind a column of
   *  small text, which is all this screen is. */
  background: '#0B0F17',

  /** A row: 52 drawn, which clears the 48 platform minimum on its own. */
  rowHeight: 52,
  rowRadius: 12,
  rowGap: 2,
  iconSize: 22,
  /** The chevron is a hint, not a control, so it sits below the label. */
  chevronSize: 16,
  chevronColor: 'rgba(255, 255, 255, 0.35)',

  /** The row a viewer is already on. The gradient is the sign-in button's. */
  activeFrom: auth.buttonFrom,
  activeTo: auth.buttonTo,

  /**
   * The identity block's avatar, and the pencil that will edit it.
   *
   * 84 rather than the 68 this was first built at. Measured off Rio's mockup
   * against the phone frame in it, the circle is about a fifth of the screen's
   * width; at 68 on a 427dp-wide handset it was nearer an eighth, and the
   * block read as a list row rather than as the thing the screen is about.
   */
  avatarSize: 84,
  avatarRing: 'rgba(255, 255, 255, 0.12)',
  editBadgeSize: 24,

  /** The tier pill. Blue-tinted, with the site's own gold crown on it, so both
   *  brand marks survive the recolour from the mockup's pink. */
  tierBg: 'rgba(26, 152, 255, 0.16)',
  tierBorder: 'rgba(26, 152, 255, 0.45)',
  tierText: '#8FCBFF',

  /** The unread count on the Notifications row. */
  badgeBg: auth.buttonFrom,
  badgeText: '#ffffff',
  badgeSize: 20,

  /** "ACCOUNT" above the sign-out row. */
  sectionSize: 11,
  sectionTracking: 1.4,
} as const;
