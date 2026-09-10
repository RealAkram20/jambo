import { formatPlanDate, renewalLine } from './format';

/**
 * The two formatters that decide a sentence about somebody's money.
 *
 * They were private to `PlansScreen` and are now shared with the profile
 * menu's Membership card, which is the reason they are worth pinning: a
 * formatter with two callers is a formatter that can be changed for one of
 * them. `formatRuntime` and `formatBannerRuntime` above them are deliberately
 * not covered here — they predate this file and belong to whoever next touches
 * a rail.
 */

describe('formatPlanDate', () => {
  it('writes a real date the way the blade does', () => {
    expect(formatPlanDate('2026-10-12T00:00:00Z')).toContain('2026');
  });

  /*
   * An absent date is a dash and never today's. A lifetime plan has no end,
   * and `new Date(undefined)` is Invalid Date, which some locales will happily
   * render as a string a viewer would read as an expiry.
   */
  it('gives an em dash rather than inventing an expiry', () => {
    expect(formatPlanDate(undefined)).toBe('—');
    expect(formatPlanDate(null)).toBe('—');
    expect(formatPlanDate('')).toBe('—');
    expect(formatPlanDate('not a date')).toBe('—');
  });
});

describe('renewalLine', () => {
  /*
   * **The word is the whole test.** Telling somebody they are about to be
   * billed when their plan is simply running out is the one error on these
   * two screens that costs money, and the website says "Renews"
   * unconditionally — so this is a place the app deliberately disagrees with
   * the surface it was ported from.
   */
  it('says Renews only when the plan will actually be charged again', () => {
    expect(renewalLine('2026-10-12T00:00:00Z', true)).toMatch(/^Renews /);
  });

  it('says Active until when auto-renew is off', () => {
    expect(renewalLine('2026-10-12T00:00:00Z', false)).toMatch(/^Active until /);
  });

  /*
   * Undefined is not "off" in general, but it is here: the server omits
   * `auto_renew` when it does not know, and the safe reading of "I do not know
   * whether you will be charged" is the one that does not promise a charge.
   */
  it('does not promise a charge it cannot confirm', () => {
    expect(renewalLine('2026-10-12T00:00:00Z', undefined)).toMatch(/^Active until /);
  });

  /*
   * The line the card draws is the date as well as the word, so both callers
   * get the same string rather than composing their own.
   */
  it('carries the formatted date, not the raw one', () => {
    const line = renewalLine('2026-10-12T00:00:00Z', true);

    expect(line).toBe(`Renews ${formatPlanDate('2026-10-12T00:00:00Z')}`);
    expect(line).not.toContain('2026-10-12T');
  });

  /* Null, not "Renews —". A line that admits it does not know, in the shape of
     a fact, is worse than no line. */
  it('draws nothing at all when there is no usable date', () => {
    expect(renewalLine(undefined, true)).toBeNull();
    expect(renewalLine(null, false)).toBeNull();
    expect(renewalLine('', true)).toBeNull();
    expect(renewalLine('not a date', true)).toBeNull();
  });
});
