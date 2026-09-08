import { useWindowDimensions } from 'react-native';

import { card, rail, spacing, watching } from './theme';

/**
 * How wide a poster is, on whatever this is running on.
 *
 * **This is the auth-card lesson generalised.** Slice 2a shipped a sign-in
 * card with no width cap; rotated, it stretched across the whole screen and
 * pushed its own submit button off the bottom. Rails have the same failure in
 * a different shape: a card sized in fixed dp is three-and-a-half across a
 * phone, one and a bit across a rotated phone, and a postage stamp on a TV.
 *
 * The site already answers this. Streamit's rail markup carries
 * `data-mobile-sm`, `data-mobile`, `data-tab`, `data-laptop` and `data-slide`,
 * and `public/frontend/js/swiper.js` feeds them to swiper at the breakpoints
 * 0 / 576 / 768 / 1025 / 1500 with `spaceBetween: 0`. So the app asks the same
 * question the website asks — *how many cards fit this width* — and gets the
 * website's answer. A 10" tablet and a TV are then widths, not layouts, which
 * is what makes Phase 4 a configuration change.
 *
 * The model was checked against the rendered site rather than assumed:
 * at a 390 viewport the probe measured a slide of 102.281px, and
 * `(390 - 2 × 16) / 3.5 = 102.28`. The poster inside it is the slide less the
 * gutter, `102.28 - 14.02 = 88.26`.
 */

/**
 * A guard, not a design decision.
 *
 * Every breakpoint above already caps the card by dividing the width, so this
 * only bites on a window the site's own breakpoints never contemplated — a
 * freeform desktop window, a foldable half-open. Without it such a width draws
 * three enormous posters and no rail; with it the rail keeps its rhythm and
 * simply shows more cards.
 */
const MAX_POSTER_WIDTH = 280;

/**
 * The share of the screen's height one poster may occupy.
 *
 * The website's ladder is a function of width alone, because a browser window
 * 844px wide is also several hundred px tall. A **landscape phone is 844 wide
 * and 390 tall**, and the ladder happily hands it the tablet layout: four
 * cards at 189dp wide, which is 265dp tall. With a heading above and a tab bar
 * below, that is the entire viewport spent on one rail — the rotation failure
 * the auth card had, in rail form.
 *
 * So the width ladder is the starting point and the height is a constraint on
 * it: if a poster would be taller than this, the rail shows more cards instead
 * of bigger ones. At 45% roughly one and a half rails are visible, which is
 * what tells a viewer the screen scrolls. The cap only ever bites in
 * landscape on a phone; a tablet, a TV and any portrait device are decided by
 * the site's own breakpoints, untouched.
 */
const MAX_POSTER_HEIGHT_RATIO = 0.45;

export type RailMetrics = {
  /** Horizontal padding from the screen edge to the first card. */
  inset: number;
  /** The full slide, poster plus its share of the gutter on both sides. */
  slideWidth: number;
  /** The drawn poster. */
  posterWidth: number;
  posterHeight: number;
  /** Half the gutter, applied as padding on each side of a card. */
  cardPadding: number;
  /** The 5:3 Continue Watching still, which is wider than a poster. */
  stillWidth: number;
  stillHeight: number;
  cardsPerView: number;
  width: number;
  landscape: boolean;
};

/** The site's breakpoint ladder, applied to a width in dp. */
export function cardsPerViewAt(width: number): number {
  let cards: number = rail.cardsPerView[0]?.cards ?? 3.5;
  for (const step of rail.cardsPerView) {
    if (width >= step.minWidth) cards = step.cards;
  }
  return cards;
}

export function railMetricsFor(width: number, height: number): RailMetrics {
  const inset = spacing.lg;
  const available = Math.max(0, width - inset * 2);

  let cardsPerView = cardsPerViewAt(width);
  let slideWidth = available / cardsPerView;

  /*
   * Two caps, both applied by adding whole cards rather than by shrinking one.
   * Adding a whole card preserves the fractional part, so the half-card peek
   * that tells a viewer the rail scrolls survives every adjustment.
   *
   * The width cap guards a window the site's ladder never contemplated. The
   * height cap is what makes a rotated phone work — see the constant above.
   */
  const maxHeight = height > 0 ? height * MAX_POSTER_HEIGHT_RATIO : Infinity;
  const tooWide = (w: number) => w - rail.cardGap > MAX_POSTER_WIDTH;
  const tooTall = (w: number) => (w - rail.cardGap) / card.aspect > maxHeight;

  while ((tooWide(slideWidth) || tooTall(slideWidth)) && cardsPerView < 24) {
    cardsPerView += 1;
    slideWidth = available / cardsPerView;
  }

  const posterWidth = Math.max(1, slideWidth - rail.cardGap);

  /*
   * `card.aspect` is width ÷ height (0.714), so height is width ÷ aspect.
   * Rounded to whole dp: a fractional height on a row of cards leaves a
   * one-pixel seam under some of them and not others, which reads as
   * misalignment rather than as antialiasing.
   */
  const posterHeight = Math.round(posterWidth / card.aspect);

  /*
   * Continue Watching is 5:3 landscape, so at the same cards-per-view it would
   * be far shorter than a poster and the rail would look broken. It gets its
   * own width: one and a half poster slides, which puts roughly two stills
   * plus a peek on a phone — the proportion the website's own rail has.
   */
  const stillWidth = Math.max(1, slideWidth * 1.5 - rail.cardGap);
  const stillHeight = Math.round(stillWidth / watching.aspect);

  return {
    inset,
    slideWidth,
    posterWidth,
    posterHeight,
    cardPadding: rail.cardGap / 2,
    stillWidth,
    stillHeight,
    cardsPerView,
    width,
    landscape: width > height,
  };
}

export function useRailMetrics(): RailMetrics {
  const { width, height } = useWindowDimensions();
  return railMetricsFor(width, height);
}
