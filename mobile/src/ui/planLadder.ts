/**
 * How wide a plan card is, and whether the ladder needs arrows.
 *
 * Pure, and apart from `PlansScreen` for the reason `profileMenu.ts` and
 * `metrics.ts` are: importing the screen pulls in the auth provider, and this
 * is arithmetic that decides what a viewer sees about what they are being
 * asked to pay for.
 *
 * **The bug this exists to fix.** The width was a module constant — always a
 * third of the screen, whatever the catalogue held — so a period with one plan
 * drew a narrow card against the left of an otherwise empty row. Jambo has
 * exactly one plan in each of its three periods today, so that was every tab.
 * Rio, 2026-09-11: *"the card when alone it expands to one big, and so if we
 * have two or three... if more than 3 let's have a left and right slider"*.
 *
 * **It is not the website's layout, and that is deliberate.** The site's
 * `col-xl-4 col-md-6` collapses to one card per ROW on a phone, stacked
 * vertically — three plans there is three full-width cards you scroll past.
 * That works on a page you scrolled to; it does not work on a tab you switch
 * between, where the point is comparing what each period costs. So the app
 * fits what it can across and slides the rest, and the single-plan case lands
 * on the same big card the website draws.
 */

/** The screen's own gutter, and the gap between two cards. */
const INSET = 16;
const GAP = 8;

/**
 * The most cards to fit across before the ladder starts sliding.
 *
 * Three, because a third of a 390pt phone is 118pt and the price line inside
 * it is already 108 of that. A fourth would put "UGX 30,000" on two lines.
 */
export const PLANS_ACROSS = 3;

/**
 * One card's width, given how many are in this period.
 *
 * Alone it takes the whole content width, which is what makes it read as an
 * offer rather than as the survivor of a row that failed to load. Two split
 * it, three take a third, and any more keep the third and scroll — so the
 * card never gets narrower than the price it has to print.
 */
export function planCardWidth(count: number, screenWidth: number): number {
  const content = screenWidth - INSET * 2;

  if (count <= 1) return content;
  if (count === 2) return Math.floor((content - GAP) / 2);

  return Math.floor((content - GAP * (PLANS_ACROSS - 1)) / PLANS_ACROSS);
}

/**
 * Whether to draw the left and right controls.
 *
 * Only when there is something off screen. Arrows over a row that already
 * fits are two controls that do nothing, which is the same fault as the
 * Google button that navigated to its own screen.
 */
export function planLadderSlides(count: number): boolean {
  return count > PLANS_ACROSS;
}

/**
 * How far one press of an arrow moves the ladder.
 *
 * A whole card plus its gap, so a press always lands a card square against
 * the gutter rather than halfway. The same reasoning as the rail's snap.
 */
export function planScrollStep(count: number, screenWidth: number): number {
  return planCardWidth(count, screenWidth) + GAP;
}

/**
 * The offset an arrow press should scroll to, clamped to the real ends.
 *
 * Clamped here rather than left to the platform because the two ends behave
 * differently otherwise: scrolling past the start rubber-bands on iOS and
 * stops dead on Android, so the arrow would feel like a different control on
 * each. `max` is derived from the content rather than guessed.
 */
export function planScrollTarget(
  current: number,
  direction: 'left' | 'right',
  count: number,
  screenWidth: number,
): number {
  const step = planScrollStep(count, screenWidth);
  const content = count * step - GAP;
  const max = Math.max(0, content - (screenWidth - INSET * 2));
  const next = direction === 'right' ? current + step : current - step;

  return Math.min(Math.max(next, 0), max);
}
