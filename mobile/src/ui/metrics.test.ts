import { cardsPerViewAt, railMetricsFor } from './metrics';

/**
 * The phone case is pinned against a measurement, not against arithmetic the
 * test repeats from the implementation: `design/export-tokens.mjs` probed a
 * real `.swiper-slide` on jambofilms.com at a 390×844 viewport and read
 * 102.281px. If the app's model of the site's layout ever stops agreeing with
 * the site, this is where it shows.
 */
describe('railMetricsFor', () => {
  it('reproduces the width the website actually renders on a phone', () => {
    const m = railMetricsFor(390, 844);

    expect(m.cardsPerView).toBe(3.5);
    expect(m.slideWidth).toBeCloseTo(102.28, 1);
    // The poster inside the slide: 102.28 less the 14.02 gutter.
    expect(m.posterWidth).toBeCloseTo(88.26, 1);
  });

  it('keeps the half-card peek, because that is what says the rail scrolls', () => {
    // A whole number of cards across would look like a grid that happens to
    // end at the screen edge. The half is deliberate design, not a rounding.
    expect(cardsPerViewAt(390) % 1).toBeCloseTo(0.5, 5);
  });

  it('shows more cards when the phone is rotated, not a rail that fills the screen', () => {
    const portrait = railMetricsFor(390, 844);
    const landscape = railMetricsFor(844, 390);

    expect(landscape.landscape).toBe(true);
    expect(landscape.cardsPerView).toBeGreaterThan(portrait.cardsPerView);

    /*
     * The website's ladder is width-only, and on its own it hands a landscape
     * phone the tablet layout: 4 cards at 189dp wide, 265dp tall on a screen
     * 390dp tall. One rail, plus a heading and a tab bar, is then the whole
     * viewport — which is slice 2a's auth-card failure wearing a different
     * hat. A poster must leave room for the rail below it to peek.
     */
    expect(landscape.posterHeight).toBeLessThanOrEqual(390 * 0.45);
    expect(landscape.cardsPerView).toBeGreaterThan(4);
  });

  it('leaves the portrait and tablet layouts to the site breakpoints', () => {
    // The height cap must bite only where the ladder goes wrong. A phone held
    // upright and a 10" tablet are the website's own answer, untouched.
    expect(railMetricsFor(390, 844).cardsPerView).toBe(3.5);
    expect(railMetricsFor(800, 1280).cardsPerView).toBe(4);
    expect(railMetricsFor(1280, 720).cardsPerView).toBe(8);
  });

  it('follows the site breakpoints a tablet and a TV land on', () => {
    expect(cardsPerViewAt(360)).toBe(3.5); // small phone
    expect(cardsPerViewAt(390)).toBe(3.5); // phone
    expect(cardsPerViewAt(800)).toBe(4); // 10" tablet portrait
    expect(cardsPerViewAt(1280)).toBe(8); // TV / desktop
    expect(cardsPerViewAt(1920)).toBe(8);
  });

  it('never draws a poster wider than the guard, however wide the window', () => {
    // A 4K TV reports ~960dp, but a freeform desktop window or a foldable can
    // report widths the site's ladder never contemplated.
    for (const width of [1280, 1920, 2560, 3840]) {
      const m = railMetricsFor(width, 1080);
      expect(m.posterWidth).toBeLessThanOrEqual(280);
      expect(m.cardsPerView).toBeGreaterThanOrEqual(8);
    }
  });

  it('gives Continue Watching a wider box, because 5:3 is not a poster', () => {
    const m = railMetricsFor(390, 844);

    expect(m.stillWidth).toBeGreaterThan(m.posterWidth);
    // Landscape: wider than it is tall. A poster is the other way round.
    expect(m.stillWidth).toBeGreaterThan(m.stillHeight);
    expect(m.posterHeight).toBeGreaterThan(m.posterWidth);
  });

  it('survives a degenerate width without producing a negative box', () => {
    // Reported during a rotation frame, and on a foldable mid-fold.
    const m = railMetricsFor(0, 0);

    expect(m.posterWidth).toBeGreaterThan(0);
    expect(m.posterHeight).toBeGreaterThan(0);
    expect(m.stillWidth).toBeGreaterThan(0);
  });
});
