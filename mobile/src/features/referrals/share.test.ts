import type { ReferralDashboard } from '../../api/endpoints';
import { count, money, percent, shareMessage, shareUrlFor, termsLine } from './share';

/**
 * The pure half of Refer & Earn.
 *
 * A malformed share URL is invisible until somebody taps it and lands on a
 * broken page, and a money value formatted wrongly is a lie about money. Both
 * are cheap to pin and neither is caught by rendering the screen.
 */

const dash = (over: Record<string, unknown> = {}): ReferralDashboard =>
  ({
    code: 'JAMBO207',
    share_url: 'https://jambofilms.com/r/JAMBO207',
    currency: 'UGX',
    stats: { balance: '12000', total_earned: '30000', total_referrals: 5, qualified: 3 },
    terms: { reward_percent: '10', discount_percent: '20', min_withdrawal: '50000' },
    ...over,
  }) as unknown as ReferralDashboard;

describe('shareMessage', () => {
  it('shares the link, which carries the referral on its own', () => {
    expect(shareMessage(dash())).toBe(
      'Watch on Jambo Films: https://jambofilms.com/r/JAMBO207',
    );
  });

  it('is null with no link, so no share control is drawn', () => {
    expect(shareMessage(dash({ share_url: null }))).toBeNull();
    expect(shareMessage(dash({ share_url: '   ' }))).toBeNull();
  });
});

describe('shareUrlFor', () => {
  it('sends WhatsApp the message, percent-encoded', () => {
    expect(shareUrlFor('whatsapp', dash())).toBe(
      'https://wa.me/?text=Watch%20on%20Jambo%20Films%3A%20https%3A%2F%2Fjambofilms.com%2Fr%2FJAMBO207',
    );
  });

  it("sends Facebook the URL alone, because its sharer ignores text", () => {
    expect(shareUrlFor('facebook', dash())).toBe(
      'https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fjambofilms.com%2Fr%2FJAMBO207',
    );
  });

  it('sends X the message as intent text', () => {
    expect(shareUrlFor('x', dash())).toContain('https://twitter.com/intent/tweet?text=');
    expect(shareUrlFor('x', dash())).toContain('JAMBO207');
  });

  it('is null for every target when there is no link', () => {
    for (const target of ['whatsapp', 'facebook', 'x'] as const) {
      expect(shareUrlFor(target, dash({ share_url: undefined }))).toBeNull();
    }
  });
});

describe('money', () => {
  it('groups and drops decimals, as the website prints it', () => {
    // refer.blade.php: number_format((float) $value, 0).
    expect(money('UGX', '12000')).toBe('UGX 12,000');
    expect(money('UGX', '21000.00')).toBe('UGX 21,000');
    expect(money('UGX', '150000.50')).toBe('UGX 150,000');
    expect(money('UGX', '999')).toBe('UGX 999');
  });

  it('never parses money to a float, so a large balance keeps every digit', () => {
    // 9007199254740993 is the first integer a double cannot represent.
    expect(money('UGX', '9007199254740993')).toBe('UGX 9,007,199,254,740,993');
  });

  it('hands back anything that is not a plain number rather than mangling it', () => {
    expect(money('UGX', '1e5')).toBe('UGX 1e5');
  });

  it('renders an em dash rather than a zero for an unknown balance', () => {
    // "UGX 0" would tell a viewer they have earned nothing, when the truth is
    // that nobody asked.
    expect(money('UGX', null)).toBe('—');
    expect(money('UGX', undefined)).toBe('—');
    expect(money('UGX', '')).toBe('—');
  });

  it('shows a real zero, because that is a fact', () => {
    expect(money('UGX', '0')).toBe('UGX 0');
  });
});

describe('count', () => {
  it('shows a real zero and an em dash for absent', () => {
    expect(count(0)).toBe('0');
    expect(count(3)).toBe('3');
    expect(count(null)).toBe('—');
    expect(count(undefined)).toBe('—');
  });
});

describe('percent', () => {
  it('trims decimal zeros the way the website does', () => {
    // refer.blade.php: "10.50" → "10.5".
    expect(percent('10.50')).toBe('10.5%');
    expect(percent('10.00')).toBe('10%');
  });

  it('never eats an integer zero', () => {
    // The web helper only trims when there is a '.', and so does this: a
    // plain "10" must not become "1%".
    expect(percent('10')).toBe('10%');
    expect(percent('100')).toBe('100%');
    expect(percent(20)).toBe('20%');
  });

  it('is null when there is nothing to show', () => {
    expect(percent(null)).toBeNull();
    expect(percent(undefined)).toBeNull();
    expect(percent('')).toBeNull();
  });
});

describe('termsLine', () => {
  it('carries both real percentages, which is why it survives the copy cut', () => {
    expect(termsLine(dash())).toBe('They save 20%. You earn 10% of what they pay.');
  });

  it('says only the half it has', () => {
    expect(termsLine(dash({ terms: { reward_percent: '10' } }))).toBe(
      'You earn 10% of what they pay.',
    );
    expect(termsLine(dash({ terms: { discount_percent: '20' } }))).toBe(
      'They save 20% on their first payment.',
    );
  });

  it('is null with no terms, so no sentence with a gap in it is drawn', () => {
    expect(termsLine(dash({ terms: undefined }))).toBeNull();
    expect(termsLine(dash({ terms: {} }))).toBeNull();
  });
});
