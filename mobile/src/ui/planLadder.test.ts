import {
  planCardWidth,
  planLadderSlides,
  planScrollStep,
  planScrollTarget,
  PLANS_ACROSS,
} from './planLadder';

/**
 * The plan ladder's arithmetic.
 *
 * It decides how a viewer sees what they are being asked to pay for, and the
 * wrong answer does not throw — it draws a narrow card in an empty row, which
 * is exactly what shipped. Jambo has one plan in each of its three periods
 * today, so the single-card case is not an edge case here: it is every tab.
 */

/** The emulator's width, and the site's probe width. */
const WIDE = 426.67;
const PHONE = 390;

describe('planCardWidth', () => {
  /*
   * The case Rio reported. One plan took a third of the screen and sat beside
   * two thirds of nothing.
   */
  it('gives a lone plan the whole content width', () => {
    expect(planCardWidth(1, PHONE)).toBe(PHONE - 32);
    expect(planCardWidth(1, WIDE)).toBeCloseTo(WIDE - 32, 5);
  });

  it('splits the row between two', () => {
    const width = planCardWidth(2, PHONE);

    // Two cards and one gap fill the content box, give or take rounding down.
    expect(width * 2 + 8).toBeLessThanOrEqual(PHONE - 32);
    expect(width * 2 + 8).toBeGreaterThan(PHONE - 34);
  });

  it('gives three a third each', () => {
    const width = planCardWidth(3, PHONE);

    expect(width * 3 + 8 * 2).toBeLessThanOrEqual(PHONE - 32);
    expect(width * 3 + 8 * 2).toBeGreaterThan(PHONE - 35);
  });

  /*
   * The whole point of sliding rather than shrinking: a fourth plan must not
   * make the price narrower. "UGX 30,000" is 108pt at this type scale and a
   * quarter of a 390 phone is 87.
   */
  it('never goes narrower than the three-across width, however many there are', () => {
    const three = planCardWidth(3, PHONE);

    expect(planCardWidth(4, PHONE)).toBe(three);
    expect(planCardWidth(9, PHONE)).toBe(three);
  });

  /* An empty catalogue is a real answer from the server; the screen has its
     own empty state, and this must not divide by zero on the way there. */
  it('treats zero as one rather than dividing by it', () => {
    expect(planCardWidth(0, PHONE)).toBe(PHONE - 32);
  });
});

describe('planLadderSlides', () => {
  /* Arrows over a row that already fits are two controls that do nothing. */
  it('draws no arrows while everything fits', () => {
    expect(planLadderSlides(1)).toBe(false);
    expect(planLadderSlides(2)).toBe(false);
    expect(planLadderSlides(PLANS_ACROSS)).toBe(false);
  });

  it('draws them as soon as one card is off screen', () => {
    expect(planLadderSlides(PLANS_ACROSS + 1)).toBe(true);
  });
});

describe('planScrollTarget', () => {
  const COUNT = 5;

  it('moves exactly one card and its gap', () => {
    const step = planScrollStep(COUNT, PHONE);

    expect(planScrollTarget(0, 'right', COUNT, PHONE)).toBe(step);
  });

  /* Rubber-banding on iOS and stopping dead on Android would make one arrow
     feel like two different controls, so both ends are clamped here. */
  it('does not scroll past the start', () => {
    expect(planScrollTarget(0, 'left', COUNT, PHONE)).toBe(0);
  });

  it('does not scroll past the end', () => {
    const step = planScrollStep(COUNT, PHONE);
    const content = COUNT * step - 8;
    const max = content - (PHONE - 32);

    // Already at the end: pressing right again stays put rather than
    // revealing empty space beyond the last card.
    expect(planScrollTarget(max, 'right', COUNT, PHONE)).toBe(max);
    expect(planScrollTarget(max - 1, 'right', COUNT, PHONE)).toBe(max);
  });

  /*
   * A ladder that fits has nothing to scroll, so every target is 0. Forced
   * rather than assumed: the clamp is what produces this, and without it a
   * press would scroll a row whose content is narrower than the screen.
   */
  it('has nowhere to go when the row already fits', () => {
    expect(planScrollTarget(0, 'right', 2, PHONE)).toBe(0);
    expect(planScrollTarget(0, 'right', 3, PHONE)).toBe(0);
  });
});
