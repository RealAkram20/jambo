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
 * The home banner, measured off the rendered site at a 390pt viewport.
 *
 * These are not in `tokens.json` because the token export probes a fixed list
 * of selectors and the banner's interior was not on it. They were read the
 * same way — `getComputedStyle` on `/` in a headless browser at 390x844,
 * 2026-09-10 — and every number below is a value the site computes rather
 * than a value anybody chose. The provenance is written next to the ones
 * where it matters, because "why 1.128" has an answer and "why 1.1" would
 * not.
 *
 * Three things the site does here that are easy to get wrong by eye:
 *
 *  - **The banner is taller than it is wide on a phone.** 440 of height for
 *    390 of width. The desktop hero is a widescreen frame; the phone one is
 *    not, and sizing it 16:9 leaves no room for the six stacked blocks the
 *    slide carries.
 *  - **The scrim is two horizontal gradients, not one vertical one.** They
 *    darken the left and right edges and leave the middle of the artwork
 *    alone. A top-to-bottom scrim - the obvious choice, and what the app drew
 *    before - dims the faces and still leaves the headline sitting on
 *    whatever happens to be behind it.
 *  - **The artwork is anchored to the top**, `background-position: 50% 0%`,
 *    because a poster standing in for a missing backdrop puts the title and
 *    the faces in its upper half.
 */
export const banner = {
  /** 440 / 390, the rendered slide. */
  aspect: 440 / 390,
  /** The gutter the content block sits in: x = 16 in a 390 viewport. */
  inset: 16,
  /** `mb-1` on the headline. Four points, and measurably missing without it. */
  titleGap: 4,

  /*
   * `linear-gradient(90.3deg, #000 9.88%, transparent 31.52%)` and
   * `linear-gradient(266.54deg, #000 13.29%, transparent 98.41%)`, drawn as
   * two stacked layers because that is what the site stacks. The sub-degree
   * tilt on each is dropped: at 390pt it is under a pixel of skew, and
   * expressing it would mean off-square start and end points for no visible
   * difference.
   */
  scrimLeftColors: ['rgb(0, 0, 0)', 'rgb(0, 0, 0)', 'rgba(0, 0, 0, 0)', 'rgba(0, 0, 0, 0)'] as const,
  scrimLeftLocations: [0, 0.0988, 0.3152, 1] as const,
  scrimRightColors: ['rgb(0, 0, 0)', 'rgb(0, 0, 0)', 'rgba(0, 0, 0, 0)', 'rgba(0, 0, 0, 0)'] as const,
  scrimRightLocations: [0, 0.1329, 0.9841, 1] as const,

  /** The certification / season badge. Square corners, and deliberately. */
  badgeBg: tokens.color.progressTrack,
  badgeFg: tokens.color.onPrimary,
  badgeFontSize: 12,
  badgePadY: 3,
  badgePadX: 6,

  /**
   * The IMDb mark's box.
   *
   * 32, because `.imdb-img` computes to 32x32 on the site and the artwork
   * letterboxes inside it. It is the tallest thing in the meta row, so it is
   * what makes that row 48 rather than the text in it — which is why the app's
   * row measured 14pt short of the site's while the mark was missing.
   *
   * The five-star row that used to sit beside it is gone from both surfaces.
   * See ADR-0006; it was never a rating.
   */
  imdbSize: 32,

  /** The meta row's own gap, and the padding around it. */
  metaGap: 16,
  metaPadY: 8,
  runtimeFontSize: 16,

  synopsisLines: 3,
  synopsisLineHeight: 24,
  synopsisMarginY: 16,

  /**
   * "Genres:" in primary, the names after it in body text.
   *
   * The line height is Bootstrap's 1.5 at 14px and it is set explicitly
   * rather than left to the platform: RN's default for a 14pt line is around
   * 18, and three of these lines stacked at 18 instead of 21 pulled the whole
   * content block 9pt short of the site's. That was found by measuring both
   * renderings, not by looking at them.
   */
  taxonomyFontSize: 14,
  taxonomyLineHeight: 21,
  taxonomyGap: 4,

  /** `.btn-primary`: 7pt corners, not the app's pill. */
  buttonRadius: 7,
  buttonPadY: 14,
  buttonPadX: 28,
  buttonFontSize: 14,
  /** Bootstrap's 1.5 again, and explicit for the same reason as above. */
  buttonLineHeight: 21,
  buttonTracking: 0.112,
  buttonGap: 8,
  buttonIconSize: 16,
  /** 24 of margin plus 8 of padding above the CTA. */
  buttonMarginTop: 32,

  /** The pagination strip, which replaces the desktop thumbnail rail. */
  dotSize: 10,
  dotGap: 4,
  dotStripHeight: 24,
  dotInactiveOpacity: 0.5,
} as const;

/**
 * The genre tile, measured off the rendered Genres rail at 390pt on
 * 2026-09-10 — `.iq-card-geners` and the `card-genres-grid` partial.
 *
 * A genre owns no artwork, so the site borrows a still from its most recent
 * published title, darkens the left of it and centres the name over the whole
 * tile. Three things are easy to get wrong by eye and all three are measured:
 *
 *  - **It is 5:3, not a poster and not 16:9.** 164 x 98.4 in a 390 viewport.
 *  - **The scrim runs left to right, not top to bottom**, and it never reaches
 *    fully transparent black at the right — it stops at 0 alpha exactly at the
 *    far edge, so the right third of the artwork is untouched.
 *  - **The label is centred in both axes**, which the computed style says with
 *    `display: flex` and `align-items: center` on an absolutely-positioned box
 *    inset to 0. Reading only `position: absolute; top: 0` would put it at the
 *    top of the tile.
 */
export const genreTile = {
  /** 164 / 98.4, the rendered tile. */
  aspect: 164 / 98.4,
  radius: 8,
  /** `linear-gradient(90deg, rgba(0,0,0,.8), rgba(0,0,0,.4) 50%, rgba(0,0,0,0))`. */
  scrimColors: ['rgba(0, 0, 0, 0.8)', 'rgba(0, 0, 0, 0.4)', 'rgba(0, 0, 0, 0)'] as const,
  scrimLocations: [0, 0.5, 1] as const,
  labelSize: 16,
  labelWeight: '500' as const,
  labelLineHeight: 20.8,
  /** The site's own gutter between tiles: a 179pt slide holding a 164pt tile. */
  gap: (179 - 164) / 2,
} as const;

/**
 * The two daily Top 10 banners, measured off the rendered site at 390x844 and
 * again at 430x932 on 2026-09-10.
 *
 * **Every size below is the same number at both widths**, which is the single
 * most useful thing the second capture established: Streamit fixes these in
 * pixels inside the phone breakpoint rather than scaling them with the
 * viewport. So these are constants, not ratios — a `width * 0.16` headline
 * would have been wrong on every phone that is not 390 wide.
 *
 * Three things here are not what an eye would guess, and each was read rather
 * than chosen:
 *
 *  - **The movies banner has no scrim.** Its `.slider--image` does declare a
 *    left-to-right gradient, but the `<img>` is a child of that element and
 *    paints over its own parent's background, so the gradient never reaches a
 *    pixel. The text sits on the raw artwork. That is a fault on the website —
 *    one line, moving the gradient to a `::before` — and it is deliberately
 *    NOT compensated for here, because the app matching a website that gets
 *    fixed later is one change, and two surfaces that quietly disagree is
 *    forever. It is written up in the worklog for Rio.
 *  - **The series banner's scrim is three gradients**, left, right and bottom,
 *    on a real `::before`. Nothing like the hero's two.
 *  - **The two banners do not share an alignment.** The movies one centres
 *    every line (`justify-content-center` below `lg`, 32pt gutters); the
 *    series one is left-aligned in 16pt gutters. They look like a pair and
 *    are not built like one.
 */
export const rankedBanner = {
  /**
   * The "Top 10" plate, and the line beside it. Identical on both banners.
   *
   * ⚠️ `labelColor` is the body text colour and that is not a mistake. The
   * blades put `.text-gold` on this line, and **no stylesheet in the
   * repository defines `.text-gold`** — grepped across every `.css` and
   * `.scss`, including the built bundles. So the site renders it in inherited
   * body grey, and this matches the site. If Rio wants it gold, that is one
   * rule on the website and one token here, in that order.
   */
  plateSize: 60,
  plateRadius: 8,
  labelSize: 18,
  labelLineHeight: 27,
  labelWeight: '700' as const,
  labelColor: colors.text,
  /** The gap between the plate and the line, on both. */
  plateGap: 16,

  /**
   * The veil over the artwork, on both banners.
   *
   * **A measured floor, not a taste.** Rio, 2026-09-10, over a screenshot of
   * the movies slide on a phone: "the texts here are not visible enough work
   * around the opacity to have it clear". Each of the ten movie backdrops and
   * three series backdrops was composited the way its slide composites it,
   * the luminance of the band the text occupies was read, and this is the
   * smallest alpha that still clears WCAG AA — 4.5:1 — for the 16pt synopsis
   * in `colors.text` on the brightest artwork in the catalogue. The worst
   * movie needed 0.586 and the worst series 0.607.
   *
   * Flat rather than a gradient because the movies banner's content is
   * centred and 326 of its 390 wide: there is no edge for a gradient to hide
   * in. The series banner keeps its own three gradients on top of this, which
   * is why it is still darker at its left edge than in its middle.
   *
   * The website draws the same 0.6 from the same arithmetic — the rule is in
   * `public/frontend/css/jambo-header.css` — so the two surfaces are one
   * decision rather than two that drift.
   */
  scrim: 'rgba(0, 0, 0, 0.6)',

  /**
   * A `.movie-tag` list: text, then a dot, then the next text.
   *
   * The three numbers are em-based on the site — `0.375em`, `0.5625em`,
   * `1.725em` — which is why the movies banner's dot is 5.25 at 14pt and the
   * series banner's is 6.75 at 18pt. Kept as ratios for the same reason: two
   * lists, one rule.
   */
  dotRatio: 0.375,
  dotRightRatio: 0.5625,
  dotGapRatio: 1.725,

  /** The Top 10 Movies of the Day slider. */
  movies: {
    /** `.verticle-slider`'s own `section-padding-bottom`. */
    sectionGap: 24,

    /**
     * How tall a slide is, as a floor rather than a fixed height.
     *
     * The site declares no height here at all: 530.375 is simply what the six
     * stacked blocks add up to, which is why it measured the same at 390 and
     * at 430. It varies by one thing — a headline is clamped to two lines and
     * a short one takes one, worth 81 — and swiper then levels every slide to
     * the tallest in the wrapper.
     *
     * A floor reproduces that without pretending to know the number: every
     * slide is at least the height the common case computes to, and a slide
     * whose content genuinely needs more (a narrow phone wrapping the genre
     * chips onto a second row) grows, with the rest of the row growing with
     * it.
     */
    minHeight: 530.375,
    /** `.description` padding. Wider than the hero's 16. */
    inset: 32,
    /** `mb-3` under the rank row. */
    plateRowGap: 16,

    /** The genre chips: `.genres-list li a`, 12.25/600 with the site's tracking. */
    genreSize: 12.25,
    genreLineHeight: 18.375,
    genreWeight: '600' as const,
    genreTracking: 0.875,
    /** `gap-1` between chips, `mb-2 pb-1` under the row. */
    genreGap: 4,
    genreRowGap: 12,
    /** The separator is the primary blue on this list. */
    genreDotColor: colors.primary,

    /**
     * The headline. 62.46 is `.iq-title a`'s computed size — the `h2` itself
     * reads 40, and drawing that would have been 22pt short of the site.
     * White and untextured, unlike the series banner's.
     */
    titleSize: 62.46,
    titleLineHeight: 81.1875,
    titleWeight: '500' as const,
    titleLines: 2,

    /** IMDb mark, clock and runtime: the hero's row, centred. */
    metaGap: 16,
    metaPadY: 8,
    clockSize: 14,
    runtimeSize: 16,
    runtimeLineHeight: 24,

    synopsisSize: 16,
    synopsisLineHeight: 24,
    synopsisLines: 3,
    synopsisMarginTop: 8,
    synopsisMarginBottom: 16,

    /**
     * The prev/next circles, which are this banner's whole navigation on a
     * phone: the thumbnail column beside it computes to 0x0 below `lg`. 24pt
     * with a 1pt white border, 4pt in from each edge.
     */
    arrowSize: 24,
    arrowInset: 4,
    arrowBorder: 1,
    arrowGlyph: 14,
  },

  /** The Top 10 Series of the Day tab slider. */
  series: {
    /** `.swiper-card`'s margin below the pagination strip. */
    sectionGap: 30,
    /** The slide is a fixed height and its content is centred inside it. */
    height: 480,
    inset: 16,
    /** `mb-4` under the rank row — deeper than the movies banner's `mb-3`. */
    plateRowGap: 24,

    /** `.texture-text`, 800 weight, `line-height: normal` measured at 30. */
    titleSize: 24.984,
    titleLineHeight: 30,
    titleWeight: 800,
    titleMarginBottom: 8,

    synopsisSize: 14,
    synopsisLineHeight: 21,
    synopsisLines: 3,

    /** "August 2023 · 2 Seasons". `mt-3 mb-40` around it. */
    metaSize: 18,
    metaLineHeight: 27,
    metaGap: 8,
    metaMarginTop: 16,
    metaMarginBottom: 40,
    /** White here, where the movies banner's genre dot is blue. */
    metaDotColor: colors.text,

    /**
     * `.tab-slider-banner-images::before`, three layers in one pseudo, drawn
     * here as three stacked gradients in the order the site declares them.
     */
    scrimLeftColors: ['rgb(0, 0, 0)', 'rgba(0, 0, 0, 0)', 'rgba(0, 0, 0, 0)'] as const,
    scrimLeftLocations: [0, 0.3852, 1] as const,
    scrimRightColors: ['rgba(0, 0, 0, 0.5)', 'rgba(0, 0, 0, 0)', 'rgba(0, 0, 0, 0)'] as const,
    scrimRightLocations: [0.006, 0.299, 1] as const,
    scrimBottomColors: ['rgba(0, 0, 0, 0)', 'rgba(0, 0, 0, 0.7)'] as const,
    scrimBottomLocations: [0, 1] as const,

    /**
     * Swiper's `dynamicBullets: true`, which is why these are not the hero's
     * equal dots: the active bullet is full size and the rest shrink with
     * distance from it. 10 → 6.6 → 3.3 is what the page measures.
     */
    dotSize: 10,
    dotGap: 8,
    dotStripHeight: 24,
    dotStripMarginTop: 16,
    dotInactiveOpacity: 0.5,
    dotScaleNear: 0.66,
    dotScaleFar: 0.33,
  },
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
 * Rows and cards, for every screen that has them.
 *
 * **One set of numbers, because Rio asked for the UI to be uniform** and it
 * was not: the profile menu drew 52dp rows, the profile details drew 18dp of
 * padding, the streaming settings drew 12dp, and the country picker drew 48dp
 * — four screens each inventing a rhythm. They now all read from here, so the
 * app has one row height and one card, and changing either is one edit rather
 * than four.
 *
 * `ui/list.tsx` is the component that spends these. A screen should reach for
 * that rather than for these values directly; the tokens are here because the
 * design system is the one place a raw number may live, not because screens
 * should read them.
 */
export const list = {
  /** Clears the 48dp platform minimum on its own, with room for two lines. */
  rowMinHeight: 56,
  rowPaddingV: spacing.md,
  rowPaddingH: spacing.lg,
  /** Between an icon and its label, and between a label and its value. */
  rowGap: spacing.md,
  iconSize: 20,
  iconColor: 'rgba(255, 255, 255, 0.55)',

  chevronSize: 16,
  chevronColor: 'rgba(255, 255, 255, 0.35)',

  /** The card a group of rows sits in. */
  cardRadius: radius.surface,
  cardGap: spacing.xl,

  labelSize: 15,
  valueSize: 15,
  detailSize: 13,

  /** A section heading above a card, or above a group of menu rows. Held here
   *  rather than in each caller because it was two identical literal pairs. */
  sectionSize: 11,
  sectionTracking: 1.4,
} as const;

/**
 * A bottom sheet, wherever one appears.
 *
 * Two screens draw one — the Watchlist's sort control and Refer & Earn's code
 * editor — and they are the same object, so the values live once. The ground
 * is the profile menu's `#0B0F17` rather than `colors.background`: a sheet is
 * a panel over a page, and the site's pure black reads as a hole rather than
 * as a raised surface.
 */
export const sheet = {
  scrim: 'rgba(0, 0, 0, 0.55)',
  bg: '#0B0F17',
  radius: 20,
  handle: 'rgba(255, 255, 255, 0.22)',

  /**
   * How much of the display a content sheet may fill before it scrolls.
   *
   * A sheet hugs its content, and a sheet whose content is taller than the
   * screen does not hug it — it grows off the top and takes its own last rows
   * with it. Found when the invoice became a sheet: its footnote landed at
   * y=2835 on a display 2856 tall, which the view tree reports as an inverted
   * rectangle rather than as an error.
   *
   * The remaining fifth is what makes it read as a panel over the page rather
   * than as a screen, which is the whole distinction between the two.
   * `fullHeight` sheets ignore this: a payment page IS the screen.
   */
  maxHeightRatio: 0.72,
} as const;

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
/**
 * The phone field: a fixed dial code beside a number that formats as it types.
 *
 * Added 2026-09-10 for Rio's phone-number request. **Every value here is the
 * `auth` block's**, because the sign-in form's input is the input this app
 * already has and a form field that is nearly the same as the sign-in one is
 * exactly the drift the asset rule exists to stop. If the sign-in field
 * changes, this changes with it, because there is one set of numbers.
 */
export const phoneField = {
  height: auth.inputHeight,
  radius: auth.inputRadius,
  border: auth.inputBorder,
  bg: auth.inputBg,
  bgFocus: colors.inputBgFocus,

  labelSize: 13,
  textSize: tokens.font.size.field,
  hintSize: tokens.font.size.caption,
  /** The rule between the code and the number, short of the field's full height. */
  dividerHeight: 20,
} as const;

export const profileMenu = {
  /** The reading column, capped so a 10" tablet gets a menu rather than a
   *  wall of 22dp icons on one side of a very wide row. Same reasoning as
   *  `auth.cardMaxWidth`. */
  maxWidth: 520,

  /** The screen's own ground. Deliberately not `colors.background`: the site's
   *  pure black is right behind poster artwork and too hard behind a column of
   *  small text, which is all this screen is. */
  background: '#0B0F17',

  /**
   * The row metrics are `list`'s, not this block's own.
   *
   * They were 52dp tall with a 22dp icon while the rest of the app drew 56 and
   * 20, which is one of the four rhythms Rio asked to be made uniform. What
   * stays local is only what is genuinely this screen's: the pill radius and
   * the gap between rows, which exist because these rows are separated pills
   * rather than divided rows inside a card.
   */
  rowHeight: list.rowMinHeight,
  iconSize: list.iconSize,
  chevronSize: list.chevronSize,
  chevronColor: list.chevronColor,

  rowRadius: 12,
  rowGap: 2,

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

  /**
   * The Membership card. Blue-tinted, with the site's own gold crown on it, so
   * both brand marks survive the recolour from the mockup's pink.
   *
   * These were the tier pill's three values and they still are. The pill sat
   * in the identity block saying the plan's name; the card below it now says
   * the plan's name, the date it runs to, and opens Membership. Keeping the
   * pill would have been the same fact twice, so the pill went and its surface
   * came here rather than a second blue being invented for the card.
   *
   * `tierText` on `tierBg` over the menu's own ground measures 9.6:1.
   */
  tierBg: 'rgba(26, 152, 255, 0.16)',
  tierBorder: 'rgba(26, 152, 255, 0.45)',
  tierText: '#8FCBFF',

  /** The unread count on the Notifications row. */
  badgeBg: auth.buttonFrom,
  badgeText: '#ffffff',
  badgeSize: 20,

  /**
   * The group headings — ACCOUNT, VIEWING, MONEY.
   *
   * `list`'s, not this block's own, for the reason the row metrics are: the
   * same heading is drawn over every card in the app and there is no reason
   * the menu's should be able to drift away from it.
   */
  sectionSize: list.sectionSize,
  sectionTracking: list.sectionTracking,

  /** The Membership card's corner. Larger than a row's, because it is a card
   *  sitting among pills and needs to read as a different kind of thing. */
  cardRadius: 14,
} as const;

/**
 * The profile screen's banner was here.
 *
 * Deleted 2026-09-10 with the screen it described — `ProfileScreen`, the
 * read-only half of the profile, which the audit's §4.1 folded into the
 * editor. It held a brand gradient, a scrim, a tagline and a 120dp avatar.
 *
 * **The form deliberately did not inherit it.** §8 of
 * `docs/plans/account-area-audit.md` names giving a form a hero as the mistake
 * this drift invites: a 190dp headline above two fields pushes the fields
 * under the keyboard. If a profile ever wants a banner again, it wants a
 * screen that is not a form.
 */



/**
 * The player.
 *
 * Read off the site's real watch page, which needed the token exporter to grow
 * a sign-in step: `/watch/{slug}` is behind login plus a subscription, so the
 * four public pages the export used until now could not see it. The controls
 * are `@videojs/html`'s minimal skin, styled by
 * `public/frontend/css/player.css`, and the settings popover is Jambo's own
 * (`public/frontend/js/jambo-settings-menu.js`) — which is where the site
 * keeps its Data Saver toggle, so the app's quality menu has a real design to
 * match rather than one invented for it.
 *
 * **Almost none of these values exist as text in that stylesheet.** It is
 * written in `currentColor` and relative colour:
 *
 *   .media-slider__track { background-color: oklch(from currentColor l c h / 0.2); }
 *
 * which only a browser that has cascaded the page can resolve. Reading the CSS
 * would give "20% of whatever is inherited"; the export renders the colour to
 * a one-pixel canvas and reads the pixel back, so these are the numbers the
 * site's compositor actually produces.
 */
export const player = {
  /*
   * THE CONTROL BAR HAS NO BACKGROUND, and the tokens are how that was
   * settled rather than guessed. `color.playerControlsBg` captured
   * `transparent` and `effect.playerControlsBackdrop` captured `none`, while
   * `effect.playerOverlay` captured a real gradient. So the darkening behind
   * the controls is a scrim on the overlay, not a translucent bar and not a
   * blur.
   *
   * That answers the question §6.4 of the plan says must be decided by
   * evidence: **LinearGradient, not `expo-blur`.** `expo-blur` would have been
   * a native dependency added to imitate something the website does not do.
   */
  scrimFrom: 'rgba(0, 0, 0, 0)',
  scrimVia: 'rgba(0, 0, 0, 0.5)',
  scrimTo: 'rgba(0, 0, 0, 0.7)',
  /** Where the mid stop sits, from `linear-gradient(... 0.5) 120px, ...)`. */
  scrimMidPx: 120,

  controlsFg: tokens.color.playerControlsFg,
  controlsPadX: tokens.space.playerControlsX,
  controlsPadY: tokens.space.playerControlsY,

  /** A control-bar button: 38dp icon box, 16dp corners. */
  buttonSize: tokens.size.playerIconButton,
  buttonRadius: tokens.radius.playerButton,
  buttonPadX: tokens.space.playerButtonX,
  buttonPadY: tokens.space.playerButtonY,
  /*
   * A control-bar button has NO plate behind it. `.media-button--subtle`
   * declares `background: transparent` and fills to 10% white only on hover
   * or focus, so the icons sit straight on the scrim as they do on the site.
   * Captured rather than typed, because transparent here is a decision and
   * not an omission — and the focus affordance comes from `Focusable`'s ring.
   */
  buttonBg: tokens.color.playerButtonBg,
  buttonPrimaryBg: tokens.color.playerButtonPrimaryBg,
  buttonPrimaryFg: tokens.color.playerButtonPrimaryFg,

  /*
   * The seek bar. `trackHeight` is the 3dp line the viewer sees and
   * `hitArea` is the 32dp row they can actually grab — the site draws both,
   * and an app that used the 3dp figure for the touch target would ship a
   * scrubber nobody can hit. `screen`'s 48dp minimum still applies on top.
   */
  trackHeight: tokens.size.playerTrackHeight,
  trackRadius: tokens.radius.playerTrack,
  trackBg: tokens.color.playerTrack,
  trackFill: tokens.color.playerTrackFill,
  trackBuffer: tokens.color.playerTrackBuffer,
  thumbSize: tokens.size.playerThumb,
  thumbColor: tokens.color.playerThumb,
  hitArea: tokens.size.playerSliderHitArea,

  timeSize: tokens.font.size.playerTime,
  timeColor: tokens.color.playerTime,
  /*
   * The duration's colour in the WIDE layout. player.css dims it to 60% only
   * inside `@container media-root (width > 42rem)`; below that the time group
   * collapses and it renders solid. The app's player is fullscreen, so it is
   * past that width whenever it is landscape — `timeMutedFromWidth` is that
   * same 42rem, and the player picks its colour the way the container query
   * does rather than picking one and being wrong half the time.
   */
  timeMutedColor: tokens.color.playerTimeMuted,
  timeMutedFromWidth: 672,

  bufferingColor: tokens.color.playerBuffering,

  /** The settings popover: quality, playback speed. */
  menuBg: tokens.color.playerMenuBg,
  menuFg: tokens.color.playerMenuFg,
  menuBorder: tokens.color.playerMenuBorder,
  menuRadius: tokens.radius.playerMenu,
  menuRowPadX: tokens.space.playerMenuRowX,
  menuRowPadY: tokens.space.playerMenuRowY,
  menuLabelSize: tokens.font.size.playerMenuLabel,
  menuValueColor: tokens.color.playerMenuValue,
} as const;

/**
 * The Watchlist screen.
 *
 * From Rio's mockup of 2026-09-09, **re-coloured to the brand**. The mockup is
 * crimson — a magenta gradient on the selected filter tab, a pink Edit pill,
 * a pink play mark — and Jambo is `#1a98ff`. This is the same call, for the
 * same reason, as `profileMenu` above: the accent here is `auth.buttonFrom`
 * and `auth.buttonTo`, which is the *same* pair the sign-in button, the
 * profile menu's active row and the profile banner already draw. There is one
 * gradient in this app, so the Watchlist cannot drift away from the button.
 *
 * What is genuinely this screen's, and so lives here rather than in `list` or
 * `card`: the segmented filter, the sort control, and the marks that sit on
 * top of a poster. Everything else — the poster's own radius, the grid's
 * gutter, the type scale — is read from the blocks above, because a second
 * poster radius that happened to match would only match until one changed.
 */
export const watchlist = {
  /** The screen title row, and the Edit pill in it. */
  headerTitleSize: 20,

  /**
   * Edit is an outlined pill at rest and a filled one while editing.
   *
   * The state is not carried by colour alone: the label itself changes to
   * "Done", which is what a screen reader and a colour-blind viewer both read.
   */
  editBorder: 'rgba(26, 152, 255, 0.55)',
  editText: '#8FCBFF',
  editRadius: radius.pill,
  editPadX: spacing.lg,
  editHeight: 34,

  /** The Movies / TV Shows / All segmented control. */
  segmentTrack: 'rgba(255, 255, 255, 0.06)',
  segmentRadius: 12,
  segmentHeight: 44,
  segmentPad: 4,
  segmentIdleText: 'rgba(255, 255, 255, 0.62)',
  segmentActiveText: colors.onPrimary,
  segmentActiveFrom: auth.buttonFrom,
  segmentActiveTo: auth.buttonTo,

  /** The sentence under the tabs, and the "6 Items" count beside the sort. */
  blurbColor: 'rgba(255, 255, 255, 0.58)',
  blurbSize: 13,
  countSize: 14,

  /** The sort control: a quiet outlined pill, not a second accent. */
  sortBg: 'rgba(255, 255, 255, 0.05)',
  sortBorder: 'rgba(255, 255, 255, 0.10)',
  sortText: colors.text,
  sortRadius: radius.pill,
  sortHeight: 36,
  sortIconSize: 15,

  /**
   * The play mark on a poster.
   *
   * A filled circle rather than the site's outlined one, because it sits on
   * arbitrary artwork here: an outline over a bright still is invisible, and
   * the mockup fills it for the same reason. The disc is white at 92% with a
   * dark glyph, which clears 3:1 against every poster in the catalogue rather
   * than against the ones that happen to be dark.
   */
  playSize: 34,
  playBg: 'rgba(255, 255, 255, 0.92)',
  playFg: '#08111F',
  playInset: spacing.sm,

  /** The "TV Series" chip a series card carries, top-right of its poster. */
  chipBg: 'rgba(8, 17, 31, 0.82)',
  chipText: colors.text,
  chipSize: 10,
  chipRadius: 6,
  chipPadX: spacing.sm,
  chipPadY: 3,

  /** Title and meta under the poster. */
  titleSize: 14,
  metaSize: 12,
  metaColor: 'rgba(255, 255, 255, 0.55)',

  /**
   * The card's own control, which is the WEBSITE's watchlist toggle.
   *
   * `widgets/watchlist-detail-card.blade.php` and `card-style.blade.php` both
   * draw a `btn-secondary` circle carrying a tick on a saved title, tooltip
   * "Remove from watchlist". This is that button: a quiet round plate, not the
   * brand accent, because on the watchlist every card is already saved and
   * six blue circles would read as six calls to action.
   *
   * It replaced a three-dot menu, which is a control the web app does not
   * have anywhere. See the note on `WatchlistCard`.
   */
  savedSize: 30,
  savedIconSize: 15,
  savedBg: 'rgba(255, 255, 255, 0.08)',
  savedBorder: 'rgba(255, 255, 255, 0.14)',
  savedIcon: 'rgba(255, 255, 255, 0.88)',

  /**
   * Edit mode's selection mark, and the wash over an unselected poster.
   *
   * The wash is what makes "selected" legible at a glance on a grid of bright
   * posters — without it the only difference between two cards is a 24dp
   * circle in one corner, which is not a state anybody reads while scrolling.
   */
  selectSize: 24,
  /*
   * `auth.buttonTo`, the DEEP end of the brand gradient, not the light end the
   * rest of this block uses. Measured, not chosen: a white tick on
   * `auth.buttonFrom` (#2C9BFF) is 2.90:1, just under the 3:1 WCAG asks of a
   * meaningful graphic, and on #0A63E8 it is 5.30:1. It is still the same pair
   * of brand values, so nothing drifts.
   */
  selectBg: auth.buttonTo,
  /*
   * The unselected mark was a 0.55 white hairline on a near-black disc and it
   * disappeared into a bright poster — the state read as a smudge rather than
   * as an empty checkbox. Two steps brighter and half a pixel thicker.
   */
  selectIdleBg: 'rgba(8, 17, 31, 0.55)',
  selectIdleBorder: 'rgba(255, 255, 255, 0.92)',
  selectIdleBorderWidth: 2,
  unselectedWash: 'rgba(0, 0, 0, 0.45)',

  /** The bar that appears while editing, over the tab bar. */
  actionBarBg: '#0B0F17',
  actionBarBorder: 'rgba(255, 255, 255, 0.08)',

  /**
   * The bottom sheet's chrome, from the shared `sheet` block below.
   *
   * Referenced rather than restated: the Watchlist's sort sheet and Refer &
   * Earn's edit sheet are the same object, and a second scrim that happened to
   * match would only match until one of them changed.
   */
  scrim: sheet.scrim,
  sheetBg: sheet.bg,
  sheetRadius: sheet.radius,
  sheetHandle: sheet.handle,

  /*
   * Two reds, because one cannot do both jobs.
   *
   * `danger` is the destructive row's TEXT, on the sheet's near-black ground:
   * 6.91:1, comfortably readable. `dangerFill` is the Remove button's FILL,
   * under white text. The first cut used `danger` for both and the button was
   * white on #FF6B6B — **2.78:1, against the 4.5:1 WCAG asks of body text**,
   * which is the one control on this screen that must not be misread. The
   * deeper red measures 5.67:1 and still reads unmistakably as danger.
   */
  danger: '#FF6B6B',
  dangerFill: '#C4292E',
} as const;

/**
 * The confirmation dialog, and the chrome it shares with the bottom sheet.
 *
 * **Added 2026-09-10 because Rio saw an Android system dialog.** Signing a
 * device out opened `Alert.alert`: a grey slab with teal capitalised buttons,
 * in the middle of a blue product, ignoring every token in this file. His
 * instruction was a universal design, enforced. See `ui/overlay.tsx`.
 *
 * Almost nothing here is new. The ground, the radius and the scrim are the
 * `sheet` block above, so a dialog and a sheet are visibly the same object; the
 * confirm button is `colors.primary`; the border is the sign-in card's own
 * hairline. **Only the destructive pair needed deciding, and it was not
 * decided here** — `watchlist.dangerFill` is a red that was already measured at
 * 5.67:1 under white after a first cut failed at 2.78:1, and reusing it means
 * the app has one destructive red rather than two that nearly match.
 */
export const dialog = {
  /** A question is read in a column. Capped for the same reason `auth` is. */
  maxWidth: 420,
  radius: sheet.radius,
  padding: spacing.xl,
  border: auth.quietBorder,

  titleSize: 18,
  messageSize: 14,

  buttonHeight: MIN_TOUCH_TARGET,
  buttonRadius: 12,
  buttonTextSize: 14,

  /**
   * Not a new red. `watchlist.dangerFill` under `colors.onPrimary` is 5.67:1,
   * measured, and it is already the fill of every destructive button in the
   * app. A second one would drift the first time either changed.
   */
  destructiveBg: watchlist.dangerFill,
  destructiveFg: colors.onPrimary,
} as const;

/**
 * The bar that appears over the bottom of a list while rows are selected.
 *
 * **Not a new design.** The Watchlist has had a selection bar since it gained
 * its edit mode, and Rio asked for the same gesture on the inbox on
 * 2026-09-10: *"we can have the long press event like long press to select and
 * we can select multiple."* Two bars that merely resembled each other would
 * drift the first time either was touched, so this names the Watchlist's own
 * values and `ui/SelectionBar.tsx` draws both.
 *
 * The Watchlist screen still carries its private copy. Converting it is a
 * commit of its own — this project's rule is that existing flat code is
 * converted deliberately, never as a side effect of a feature.
 */
export const selection = {
  bg: watchlist.actionBarBg,
  border: watchlist.actionBarBorder,
  radius: watchlist.editRadius,
  /** Every control in the bar is a real target, not a 32px chip. */
  buttonHeight: MIN_TOUCH_TARGET,
  textSize: 14,
  /** The app's one destructive red, measured at 5.67:1 under white. */
  destructiveBg: watchlist.dangerFill,
  destructiveFg: colors.onPrimary,
  /** A count nobody can read is a count that gets a row deleted by accident. */
  countColor: colors.text,
} as const;

/**
 * The profile hub's own components, captured off `/{username}/billing`.
 *
 * **Rio's ruling, 2026-09-09:** the app reuses the webapp's assets at their
 * exact design, behaviour and size, and an inspirational screen never replaces
 * one. This block exists because the first cut of the billing screen broke
 * that: its status badge was approximated from the semantic `ok` and `warning`
 * colours and drew a hollow outline, where the site paints a solid fill. It
 * looked reasonable and it was not the product.
 *
 * Every value here comes from a probe in `design/export-tokens.mjs` against
 * the rendered, signed-in page. The hub card's CSS lives in a `<style>` block
 * inside `profile-hub/_layout.blade.php`, so none of it exists on a public
 * page and none of it could be read without signing in.
 */
export const hub = {
  cardBg: tokens.color.hubCardBg,
  cardBorder: tokens.color.hubCardBorder,
  cardRadius: tokens.radius.hubCard,
  cardPadding: tokens.space.hubCardPadding,
  titleSize: tokens.font.size.hubCardTitle,
  subtitleSize: tokens.font.size.hubCardSubtitle,
  subtitleColor: tokens.color.hubCardSubtitle,

  /** The invoice's `table.table-dark`. */
  tableFg: tokens.color.hubTableFg,
  tableSize: tokens.font.size.hubTable,
  tableBorder: tokens.color.hubTableBorder,
  tableCellY: tokens.space.hubTableCellY,
  tableCellX: tokens.space.hubTableCellX,
  headColor: tokens.color.hubTableHead,
  headSize: tokens.font.size.hubTableHead,
} as const;

/**
 * The status badge, as the site paints it.
 *
 * `billing.blade.php` and `invoice.blade.php` both render
 * `<span class="badge bg-success|bg-warning">`, and `membership.blade.php`
 * adds `bg-primary` for auto-renew. Solid fills, 12px, weight 700, 3px radius,
 * 3px by 6px padding — all measured rather than chosen.
 *
 * 🔴 **One value deviates, and it is the only one.** The site draws
 * `bg-warning` as **white on #ffd81c**, which measures **1.39:1** — WCAG asks
 * 4.5:1 for text this size, so the site's own Pending badge is effectively
 * unreadable. Black on the same yellow is 15.09:1. Rio's call, 2026-09-09:
 * keep the site's fill and darken the text, and report the contrast defect for
 * a separate website fix so the two surfaces converge rather than drift.
 *
 * The two other foregrounds are the site's own and are kept: white on #27ae60
 * is 2.87:1 and white on #1a98ff is 3.01:1. Both are under AA and neither is
 * illegible, and neither is this slice's to re-decide.
 */
export const badge = {
  size: tokens.font.size.badge,
  weight: tokens.font.weight.badge,
  radius: tokens.radius.badge,
  padY: tokens.space.badgeY,
  padX: tokens.space.badgeX,

  okBg: tokens.color.badgeOkBg,
  okFg: tokens.color.badgeOkFg,

  warnBg: tokens.color.badgeWarnBg,
  /** NOT `tokens.color.badgeWarnFg`. See above — the captured value is 1.39:1. */
  warnFg: '#1A1A1A',

  primaryBg: tokens.color.badgePrimaryBg,
  primaryFg: tokens.color.badgePrimaryFg,
} as const;

/**
 * The notification inbox, and the delivery preferences behind its gear.
 *
 * Captured from `/{username}/notifications` — the hub's own inbox — which is
 * the same screen on the website. The row, its unread state, the 40x40 leading
 * square and the preference rows are all read from there rather than measured
 * off Rio's mockup, per his asset ruling of 2026-09-09: design, behaviour and
 * size come from the web product. He was asked specifically about the
 * thumbnail, because the mockup draws a larger one, and held the site's 40.
 *
 * **The unread tint needed no ruling at all.** The mockup marks unread in
 * crimson, and the app has translated that to the brand blue three times now
 * on the strength of the brand rather than the code. This time the site
 * answered it directly: `.jambo-hub-inbox__row.is-unread` is already
 * `rgba(26, 152, 255, 0.07)` over a `rgba(26, 152, 255, 0.18)` border. Both
 * values below are that CSS, captured.
 *
 * 🔴 **Every one of the five icon-tile tones fails WCAG AA on the website, and
 * only the glyph colour is deviated here.** The tile fills are Bootstrap's
 * `-subtle` pastels and they are fine: each is 14.8:1 or better against the
 * black row, so the tile reads as a tile. The `-emphasis` foreground the site
 * pairs them with is the broken half, in all five tones:
 *
 * | Tone | Site's pair | Contrast | AA needs |
 * |---|---|---|---|
 * | Warning | `#ffe877` on `#fff7d2` | **1.14** | 4.5 |
 * | Success | `#7dcea0` on `#d4efdf` | 1.54 | 4.5 |
 * | Info | `#66afff` on `#cce4ff` | 1.76 | 4.5 |
 * | Danger | `#989eac` on `#dddfe3` | 2.01 | 4.5 |
 * | Primary | `#ef6b72` on `#faced0` | 2.11 | 4.5 |
 *
 * A yellow glyph on pale yellow at 1.14:1 is not a subtle failure; it is an
 * invisible icon. This is the same defect and the same fix as the Pending
 * badge above — **keep the site's fill, fix the foreground, record the
 * deviation where it can be undone, and report the website defect separately**
 * so the two surfaces converge instead of drifting.
 *
 * The replacement ink is `colors.background`, which is not a new colour: it is
 * the row's own ground, so the glyph is a knockout of the surface behind it.
 * That lands between 14.8:1 and 19.5:1 depending on the tone, and it means no
 * raw value was invented to fix a raw value that was wrong.
 *
 * The chips are the one part of this screen the website has no component for —
 * its inbox is a flat list with no filter — so they wear the app's own
 * existing filter language from `watchlist` above, at the site's hairline and
 * the site's button gradient. Two filter controls in one product should not be
 * two different objects.
 */
export const notifications = {
  /* ── the row ─────────────────────────────────────────────────────── */
  rowRadius: tokens.radius.inboxRow,
  rowPadY: tokens.space.inboxRowY,
  rowPadX: tokens.space.inboxRowX,
  rowGap: tokens.space.inboxRowGap,
  rowBorderWidth: tokens.size.inboxRowBorder,
  rowBorder: tokens.color.inboxRowBorder,
  /** `transparent` on the site: the row inherits the card it sits in. */
  rowBg: tokens.color.inboxRowBg,

  unreadBg: tokens.color.inboxUnreadBg,
  unreadBorder: tokens.color.inboxUnreadBorder,
  /** The unread mark itself. The site does not draw one; the mockup does. */
  dotSize: 8,

  /* ── the leading square, in both its forms ───────────────────────── */
  tileSize: tokens.size.inboxTile,
  tileRadius: tokens.radius.inboxTile,
  tileIconSize: tokens.font.size.inboxTileIcon,
  imageSize: tokens.size.inboxImage,
  imageRadius: tokens.radius.inboxImage,

  /**
   * The five tones, fills captured and glyphs deviated. See the table above:
   * every `fg` here would be the site's `-emphasis` value if that value were
   * legible, and the day the website is fixed this is the one place to undo.
   */
  tones: {
    primary: { bg: tokens.color.inboxTonePrimaryBg, fg: tokens.color.bg },
    success: { bg: tokens.color.inboxToneSuccessBg, fg: tokens.color.bg },
    warning: { bg: tokens.color.inboxToneWarningBg, fg: tokens.color.bg },
    danger: { bg: tokens.color.inboxToneDangerBg, fg: tokens.color.bg },
    info: { bg: tokens.color.inboxToneInfoBg, fg: tokens.color.bg },
  },

  /**
   * The row's text, captured.
   *
   * The first cut of this row guessed a 15px title and a 12px timestamp. The
   * site's answers are 16 at 600, and 14 for both the message and the time —
   * and the timestamp being the same size as the message is a decision rather
   * than an oversight, so it is kept.
   */
  titleSize: tokens.font.size.inboxTitle,
  /**
   * The site's title is 600. The app ships four Roboto faces — 400, 500, 700,
   * 900 — so the nearest it can draw is `fonts.medium`, and the row uses that.
   * A fifth face for one line of one screen is a font file on every handset,
   * which is a worse trade than one weight step. `tokens.font.weight.inboxTitle`
   * records what the site actually does, so the day a semibold is loaded for
   * another reason this is a one-line change rather than a rediscovery.
   */
  titleColor: tokens.color.inboxTitle,
  messageSize: tokens.font.size.inboxMessage,
  messageColor: tokens.color.inboxMessage,
  timeSize: tokens.font.size.inboxTime,
  timeColor: tokens.color.inboxTime,

  /**
   * The two header buttons the website's inbox carries — "Mark all as read"
   * as a filled primary, "Delete all" as an outlined danger — reusing the
   * chips' own geometry below so the header reads as one row of controls.
   *
   * The red is not a new one. `watchlist.danger` is the app's destructive
   * TEXT red, already measured at 6.91:1 on this ground; the deeper
   * `dangerFill` is for white-on-red buttons and would be unreadable as an
   * outline's label.
   */
  actionDanger: watchlist.danger,

  /* ── the day headings, which the website's flat list has no need of ─ */
  sectionSize: tokens.font.size.hubCardTitle,
  sectionColor: tokens.color.text,

  /* ── the chips ───────────────────────────────────────────────────── */
  chipHeight: 34,
  chipRadius: radius.pill,
  chipPadX: 14,
  chipGap: 8,
  chipBorder: tokens.color.inboxRowBorder,
  chipIdleText: 'rgba(255, 255, 255, 0.62)',
  chipActiveText: colors.onPrimary,
  chipActiveFrom: auth.buttonFrom,
  chipActiveTo: auth.buttonTo,

  /*
   * The settings screen behind the gear has no block of its own on purpose.
   *
   * It is built from `ListCard` and `ListRow`, the same two components the
   * streaming-preferences screen uses, because two settings screens in one app
   * should be the same object. The website's `.jambo-pref-row` was captured
   * first and then deliberately not used: following it would have given this
   * app a second kind of settings row that exists on one screen, which is the
   * duplication the asset ruling is meant to prevent rather than cause.
   * Translating a layout is allowed; growing a second component is not.
   */
} as const;

/**
 * Refer & Earn.
 *
 * From Rio's mockup of 2026-09-09, **re-coloured to the brand** — the same
 * call, for the same reason, as `profileMenu` and `watchlist` above. The
 * mockup is crimson throughout: a pink headline, a pink code, a pink Copy
 * button, red step icons. Jambo is `#1a98ff`, and the accent here is
 * `auth.buttonFrom` / `auth.buttonTo`, the pair the sign-in button, the
 * profile menu's active row, the profile banner and the Watchlist's filter
 * tabs already draw. One gradient in the app, so this screen cannot drift.
 *
 * The gift mark is the website's own: `refer.blade.php` renders
 * `ph-fill ph-gift` at `var(--bs-primary)`, so the app draws the same Phosphor
 * glyph in the same role rather than sourcing an illustration the site has
 * never had.
 */
export const referrals = {
  /** The reading column, capped as `auth.cardMaxWidth` and `profileMenu` are. */
  maxWidth: 520,

  /** The hero. A tinted panel rather than an image: there is no artwork for
   *  this screen anywhere in the product, and a stock illustration would be
   *  the only invented thing on it. */
  heroBg: 'rgba(26, 152, 255, 0.10)',
  heroBorder: 'rgba(26, 152, 255, 0.22)',
  heroRadius: radius.surface,

  /** "SHARE THE ENTERTAINMENT" above the headline. */
  eyebrowSize: 10,
  eyebrowTracking: 2.6,
  eyebrowColor: '#8FCBFF',

  /** "Invite Friends" in white, "Earn Rewards" in the brand. */
  headlineSize: 26,
  headlineLineHeight: 32,
  headlineAccent: auth.buttonFrom,

  /** The gift glyph, at the size the mockup draws it. */
  giftSize: 64,
  giftColor: auth.buttonFrom,

  /** Cards: the code block, Share via, How It Works, Your Rewards. */
  cardBg: 'rgba(255, 255, 255, 0.04)',
  cardBorder: 'rgba(255, 255, 255, 0.08)',
  cardRadius: radius.surface,
  cardPad: spacing.lg,
  cardGap: spacing.lg,

  /** A card's own small heading. */
  cardTitleSize: 14,
  labelSize: 12,
  labelColor: 'rgba(255, 255, 255, 0.55)',

  /**
   * The code itself: the largest thing on the screen, because it is the point
   * of it. Tracking is what makes a string of capitals and digits readable
   * aloud, which is the other way this code travels.
   */
  codeSize: 28,
  codeTracking: 1.6,
  codeColor: auth.buttonFrom,

  /** The two copy controls the mockup draws: a quiet icon, and a filled pill. */
  copyIconSize: 40,
  copyIconRadius: 10,
  copyIconBg: 'rgba(255, 255, 255, 0.06)',
  copyIconBorder: 'rgba(255, 255, 255, 0.10)',
  copyButtonHeight: 40,
  copyButtonRadius: radius.pill,
  copyFrom: auth.buttonFrom,
  copyTo: auth.buttonTo,

  /** Confirmation after a copy. The site shows "Link copied" in green. */
  copiedColor: tokens.color.okText,

  /** The code editor's sheet, from the shared block. */
  scrim: sheet.scrim,
  sheetBg: sheet.bg,
  sheetRadius: sheet.radius,

  /**
   * A share destination: a round plate with a logo, and its name beneath.
   *
   * Each carries its own brand colour, which is the one place on this screen
   * that is deliberately NOT Jambo blue — a WhatsApp button that is not green
   * is harder to find, and these marks are how a viewer picks the right one
   * without reading. They are the platforms' own colours, used as
   * identification rather than as decoration.
   */
  shareSize: 52,
  shareIconSize: 26,
  shareLabelSize: 11,
  shareGap: spacing.md,
  whatsapp: '#25D366',
  facebook: '#1877F2',
  x: '#000000',
  xBorder: 'rgba(255, 255, 255, 0.22)',
  neutralShareBg: 'rgba(255, 255, 255, 0.08)',

  /** How It Works: three steps and the arrows between them. */
  stepSize: 44,
  stepBg: 'rgba(26, 152, 255, 0.14)',
  stepIconSize: 22,
  stepIcon: auth.buttonFrom,
  stepTitleSize: 12,
  stepDetailSize: 11,
  arrowColor: 'rgba(255, 255, 255, 0.30)',

  /** Your Rewards: two figures, side by side, with a rule between them. */
  rewardIconSize: 26,
  rewardValueSize: 18,
  rewardLabelSize: 11,
  rewardDivider: 'rgba(255, 255, 255, 0.10)',
} as const;

/**
 * The wallet: a balance card, two actions, and the ledger as rows.
 *
 * **The website's wallet is a card and two tables**, so there is no captured
 * wallet row to wear — translating a desktop table into phone rows is the one
 * thing §4a explicitly allows. What the rows must not do is invent their own
 * treatment, so they take the site's list-row values, which were captured from
 * its notification inbox: the same 10px radius, the same hairline, the same
 * 13.6px padding and the same 40x40 leading square. Two lists in one product
 * should be the same object even when one of them started life as a table.
 *
 * The balance card's gradient is `auth.buttonFrom` / `auth.buttonTo` — the
 * sign-in button's own pair, and by now the app's single accent. Rio's mockup
 * draws it in crimson; that is the fourth screen to ask, and the fourth to be
 * answered with the brand blue rather than a second accent.
 */
export const wallet = {
  /* ── the balance card ────────────────────────────────────────────── */
  cardRadius: 20,
  cardPad: spacing.xl,
  cardFrom: auth.buttonFrom,
  cardTo: auth.buttonTo,
  cardLabelSize: 13,
  /** Big enough to be the first thing read, and it is the point of the screen. */
  balanceSize: 34,
  /** The wallet mark the website's own card draws, at the size it draws it. */
  cardMarkSize: 64,

  /* ── the two primary actions ─────────────────────────────────────── */
  actionHeight: BUTTON_HEIGHT,
  actionRadius: radius.pill,
  actionGap: spacing.md,

  /* ── the quick-action tile ───────────────────────────────────────── */
  tileRadius: tokens.radius.inboxRow,
  tileBorder: tokens.color.inboxRowBorder,
  tilePad: spacing.lg,
  tileIconSize: 22,
  tileLabelSize: 13,

  /* ── a ledger row, and a withdrawal row ──────────────────────────── */
  rowRadius: tokens.radius.inboxRow,
  rowPad: tokens.space.inboxRowY,
  rowGap: tokens.space.inboxRowGap,
  rowBorder: tokens.color.inboxRowBorder,
  rowBorderWidth: tokens.size.inboxRowBorder,
  markSize: tokens.size.inboxTile,
  markRadius: tokens.radius.inboxTile,
  markIconSize: tokens.font.size.inboxTileIcon,
  titleSize: tokens.font.size.inboxTitle,
  titleColor: tokens.color.inboxTitle,
  metaSize: tokens.font.size.inboxMessage,
  metaColor: tokens.color.inboxMessage,

  /**
   * Money in and money out.
   *
   * `incoming` is the site's own success green from its alert styles, not
   * Bootstrap's `--bs-success` — the same reason `colors.errorText` exists.
   * `outgoing` is plain body text rather than a red: on this screen almost
   * every row is a spend, and colouring the ordinary case as a warning makes
   * the one row that matters harder to find, not easier.
   */
  incoming: tokens.color.okText,
  outgoing: tokens.color.inboxTitle,

  /* ── the withdrawal sheet ────────────────────────────────────────── */
  sheetRadius: 24,
  sheetScrim: 'rgba(0, 0, 0, 0.6)',
} as const;

/**
 * Membership: the plan ladder, its period tabs and the current-plan strip.
 *
 * **Every value here was read off `/pricing`, not off Rio's mockup.** His
 * asset ruling of 2026-09-09 makes the web product the source for design,
 * behaviour and size, and the pricing card is a component that already exists
 * there — `.pricing-plan-wrapper`, `.plan-main-price`, `.pricing-plan-discount`
 * and `.jambo-period-tabs` are the selectors, and each token below carries the
 * one it came from in `tokens.json`.
 *
 * **The mockup is crimson and the brand is blue.** That is the third time this
 * substitution has been made — the profile menu and the notification inbox
 * were the first two — and it is the same answer: Rio asked for the layout in
 * Jambo's colours. The blue here is the site's own `--bs-primary`, arriving
 * through the ribbon, the active tab and the primary button rather than being
 * chosen.
 *
 * 🔴 **One foreground deviates, and it is a real failure rather than a
 * preference.** The site draws its "Most popular" ribbon as `#d1d0cf` on
 * `#1a98ff`, which measures **1.95:1** — a label you have to hunt for on the
 * one card the page is trying to sell. White on the same fill is 3.01:1, so
 * the deviation below is the smaller of the two available corrections: it
 * fixes the failure without re-deciding white-on-blue, which is the pair the
 * whole app already wears and which `badge` above deliberately left alone as
 * "not this slice's to re-decide". The website defect is reported separately.
 *
 * The card's own text is fine and is kept exactly: `#d1d0cf` on `#141314` is
 * 12.03:1 for the name, the price and every feature line.
 */
export const plans = {
  /* ── the card ────────────────────────────────────────────────────── */
  cardBg: tokens.color.planCardBg,
  cardBorder: tokens.color.planCardBorder,
  cardBorderWidth: tokens.size.planCardBorder,
  cardRadius: tokens.radius.planCard,

  /*
   * The site's card has no padding of its own — its children carry it — so
   * the captured 0 is correct and useless. The app's card is a single view,
   * so it takes the spacing scale instead. This is the "translating a layout
   * is allowed" half of §4a: the numbers that describe the component are the
   * site's, the number that describes the phone's gutter is ours.
   */
  cardPad: spacing.lg,

  nameSize: tokens.font.size.planName,
  nameWeight: tokens.font.weight.planName,
  nameColor: tokens.color.planName,

  priceSize: tokens.font.size.planPrice,
  /*
   * 400, and that is the site's answer rather than an oversight: its price is
   * large and light, where the mockup draws it heavy. Size carries the
   * emphasis, weight does not.
   */
  priceWeight: tokens.font.weight.planPrice,
  priceColor: tokens.color.planPrice,

  periodSize: tokens.font.size.planPeriod,
  periodColor: tokens.color.planPeriod,

  featureSize: tokens.font.size.planFeature,
  featureWeight: tokens.font.weight.planFeature,
  featureColor: tokens.color.planFeature,
  featureIconSize: tokens.size.planFeatureIcon,
  featureIconColor: tokens.color.planFeatureIcon,

  /* ── the "Most popular" ribbon ───────────────────────────────────── */
  ribbonBg: tokens.color.planRibbonBg,
  /** NOT `tokens.color.planRibbonFg`. See above — the captured pair is 1.95:1. */
  ribbonFg: tokens.color.onPrimary,
  ribbonSize: tokens.font.size.planRibbon,
  ribbonPadY: tokens.space.planRibbonY,

  /* ── the call to action ──────────────────────────────────────────── */
  ctaBg: tokens.color.planCtaBg,
  ctaFg: tokens.color.planCtaFg,
  ctaRadius: tokens.radius.planCta,
  ctaSize: tokens.font.size.planCta,
  ctaWeight: tokens.font.weight.planCta,
  /** The outline variant: same blue, drawn as ink and border instead of fill. */
  ctaQuietFg: tokens.color.planCtaQuietFg,
  ctaQuietBorder: tokens.color.planCtaQuietBorder,

  /* ── the billing-period tabs ─────────────────────────────────────── */
  tabBg: tokens.color.periodTabBg,
  tabFg: tokens.color.periodTabFg,
  tabActiveBg: tokens.color.periodTabActiveBg,
  tabActiveFg: tokens.color.periodTabActiveFg,
  tabRadius: tokens.radius.periodTab,
  tabSize: tokens.font.size.periodTab,
  tabWeight: tokens.font.weight.periodTab,
  tabPadY: tokens.space.periodTabY,
  tabPadX: tokens.space.periodTabX,

  /* ── the current-plan strip, above the tabs on the site too ──────── */
  stripBg: tokens.color.planStripBg,
  stripBorder: tokens.color.planStripBorder,
  stripRadius: tokens.radius.planStrip,
  stripPad: tokens.space.planStrip,
} as const;

/**
 * The devices screen: a hero, a usage meter, and a row per device.
 *
 * The row wears the site's list-row treatment, the same one the notification
 * inbox and the wallet use — 10px radius, the site hairline, a 40x40 leading
 * square. The website's own devices page draws its rows from
 * `.jambo-device-row`, whose icon tile is the same `bg-primary-subtle` pair
 * measured at 2.11:1 in §6b, so the tile here is the app's quiet fill with the
 * brand glyph rather than that pair. Same decision as the inbox, same reason.
 *
 * The hero's gradient is `auth.buttonFrom` / `auth.buttonTo`. Fifth screen,
 * same answer: the mockup draws crimson and the brand is blue.
 */
export const devices = {
  heroRadius: 20,
  heroPad: spacing.xl,
  heroFrom: auth.buttonFrom,
  heroTo: auth.buttonTo,
  heroTitleSize: 22,
  heroMarkSize: 56,

  /** The meter. A bar with a label, not a bar with a number over it. */
  meterRadius: tokens.radius.inboxRow,
  meterBorder: tokens.color.inboxRowBorder,
  meterPad: spacing.lg,
  meterTrack: 'rgba(255, 255, 255, 0.10)',
  meterHeight: 8,
  meterFill: colors.primary,
  /** At the cap, the bar says so in a second way than its length. */
  meterFull: tokens.color.warning,

  rowRadius: tokens.radius.inboxRow,
  rowPad: tokens.space.inboxRowY,
  rowGap: tokens.space.inboxRowGap,
  rowBorder: tokens.color.inboxRowBorder,
  rowBorderWidth: tokens.size.inboxRowBorder,
  markSize: tokens.size.inboxTile,
  markRadius: tokens.radius.inboxTile,
  markIconSize: tokens.font.size.inboxTileIcon,
  titleSize: tokens.font.size.inboxTitle,
  titleColor: tokens.color.inboxTitle,
  metaSize: tokens.font.size.inboxMessage,
  metaColor: tokens.color.inboxMessage,

  /**
   * The "This device" mark.
   *
   * The website badges it `bg-success`, which is the solid green already
   * captured for the billing badges — so this is that fill and that
   * foreground, not a new pair. Its 2.87:1 was measured and ruled not this
   * slice's to re-decide, in the same note as the rest.
   */
  currentBg: badge.okBg,
  currentFg: badge.okFg,
  currentSize: 10,
} as const;

/**
 * The header, both rows, captured off the rendered site.
 *
 * Rio, 2026-09-10: *"let us use the exact header as it is on our web app."*
 * The app had a logo, a search glyph and a generic account circle. The site
 * has a notification bell with an unread badge, the viewer's own avatar, and a
 * second row of genre chips — and it has had all three for as long as the app
 * has existed. This block is that header's real numbers rather than an
 * approximation of the screenshot.
 *
 * **Two probe defects were found capturing this**, both worth knowing because
 * both produced plausible values:
 *
 * 1. `.jambo-genre-chip` matched the FIRST chip in the bar, which is "All",
 *    which is *active* on the home page. The resting chip captured as white on
 *    black and the active state came back identical to it — every genre chip
 *    in the app would have been drawn as the selected one. The probe asks for
 *    `:not(.active)` now. Same trap as the sign-in field's focused border.
 * 2. `border-bottom-color` on the genres bar read `#d1d0cf`, which is the text
 *    colour: CSS defaults a border colour to `currentColor` when no border is
 *    set. The widths were captured too and both are **0**, so neither the bar
 *    nor the chip draws a border. A colour alone cannot tell a hairline from a
 *    phantom.
 *
 * 🔴 **The unread badge is white on `#1a98ff` at 3.01:1**, which is under AA.
 * It is the tenth entry in the tally recorded in `~/.claude/skills/screen`
 * §6b, it is the pair the whole app already wears, and the `badge` block above
 * already ruled it not a slice's to re-decide unilaterally. Kept, measured,
 * and named — the profile menu's own unread pill is the same pair, so the two
 * cannot disagree.
 *
 * The other three pairs were measured and pass comfortably: the resting chip
 * at 12.03:1, the active chip at 21:1, and the header icon at 14.35:1.
 */
export const header = {
  /**
   * The brand mark: a height, and a width that follows the logo's own shape.
   *
   * The app had `{ width: 108, height: 32 }` typed into a stylesheet, and Rio
   * spotted the result — the brand is a wide wordmark, so `contain` fitted it
   * to that box by WIDTH and it landed around 16dp tall, shorter than the
   * website's own. **A fixed box can only ever be right for one logo's
   * aspect**, and the logo is admin-uploadable.
   *
   * `logoWidth` is not a width to draw at. It is the captured site pair, used
   * only to seed the ratio before the real image reports its own, so the
   * header does not reflow from nothing on every launch.
   */
  /**
   * 30, not the captured 24. **A deliberate deviation, at Rio's request.**
   *
   * `tokens.size.headerLogoHeight` is what the site renders — 24 — and it is
   * still the value this is measured against, so the day the website's header
   * changes this is the one line to reconsider.
   *
   * He asked for "a little bit more" after seeing 24 on a device, and 30 is
   * inside the site's own range rather than a number invented for the app:
   * `.jambo-header__logo img` declares `height: 36px` and only renders shorter
   * because a browser's cramped mobile header constrains it. A native header
   * is not a 390px browser window, so the app sits between the rule and the
   * constraint.
   */
  logoHeight: 30,
  logoWidth: tokens.size.headerLogoWidth,

  iconSize: tokens.font.size.headerIcon,
  iconBox: tokens.size.headerIcon,
  iconColor: tokens.color.headerIcon,

  badgeBg: tokens.color.headerBadgeBg,
  badgeFg: tokens.color.headerBadgeFg,
  badgeSize: tokens.font.size.headerBadge,
  badgeHeight: tokens.size.headerBadge,
  badgeRadius: radius.pill,

  avatarSize: tokens.size.headerAvatar,

  /** Row two. No border: the captured width is 0 — see the note above. */
  barBg: tokens.color.genresBarBg,
  barPadY: tokens.space.genresBarY,
  chipGap: spacing.sm,

  chipBg: tokens.color.genreChipBg,
  chipFg: tokens.color.genreChipFg,
  chipRadius: tokens.radius.genreChip,
  chipTextSize: tokens.font.size.genreChip,
  chipPadY: tokens.space.genreChipY,
  chipPadX: tokens.space.genreChipX,
  chipActiveBg: tokens.color.genreChipActiveBg,
  chipActiveFg: tokens.color.genreChipActiveFg,
} as const;
