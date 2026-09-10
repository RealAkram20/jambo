import {
  billingPeriodLabel,
  formatMoney,
  formatOrderDate,
  optionalDetail,
  planLabel,
  statusLabel,
  statusTone,
} from './format';
import type { PaymentOrder } from './api';

describe('formatMoney', () => {
  it('groups in threes and keeps the cents the server sent', () => {
    expect(formatMoney('150000.00', 'UGX')).toBe('UGX 150,000.00');
    expect(formatMoney('10000.00', 'UGX')).toBe('UGX 10,000.00');
    expect(formatMoney('2500.00', 'UGX')).toBe('UGX 2,500.00');
  });

  it('does not group a figure that does not need it', () => {
    expect(formatMoney('500.00', 'UGX')).toBe('UGX 500.00');
  });

  /*
   * The digits must survive untouched.
   *
   * **The value here is chosen to be past `Number.MAX_SAFE_INTEGER`**, because
   * that is the only place the two implementations differ and therefore the
   * only place a test can tell them apart. Written first with a merely large
   * figure, the mutation harness replaced the string grouping with
   * `Number(digits).toLocaleString()` and every test stayed green — the claim
   * in the docblock was true and untested. `99999999999999999` becomes
   * `100000000000000000` the moment it passes through a float.
   *
   * No Jambo plan costs this. The point is that the function is not permitted
   * to do arithmetic on money at all, and a guard nothing can break is not a
   * guard.
   */
  it('never recomputes the digits', () => {
    expect(formatMoney('99999999999999999.99', 'UGX')).toBe('UGX 99,999,999,999,999,999.99');
    expect(formatMoney('12345678901234.56', 'UGX')).toBe('UGX 12,345,678,901,234.56');
    expect(formatMoney('0.05', 'UGX')).toBe('UGX 0.05');
  });

  it('keeps a figure with no decimal part as the server sent it', () => {
    expect(formatMoney('7000', 'UGX')).toBe('UGX 7,000');
  });

  it('handles a refund', () => {
    expect(formatMoney('-1500.00', 'UGX')).toBe('UGX -1,500.00');
  });

  it('renders an em dash for a figure nobody has', () => {
    expect(formatMoney(undefined, 'UGX')).toBe('—');
    expect(formatMoney(null, 'UGX')).toBe('—');
    expect(formatMoney('', 'UGX')).toBe('—');
  });

  it('omits the currency when the server did not name one', () => {
    expect(formatMoney('1000.00', undefined)).toBe('1,000.00');
    expect(formatMoney('1000.00', '')).toBe('1,000.00');
  });

  /* Something unexpected is passed through, not mangled into a wrong number. */
  it('passes an unparseable amount through untouched', () => {
    expect(formatMoney('about 5,000', 'UGX')).toBe('UGX about 5,000');
  });
});

describe('formatOrderDate', () => {
  it('renders a date', () => {
    expect(formatOrderDate('2026-07-09T14:44:50+00:00')).not.toBeNull();
  });

  it('is null rather than "Invalid Date"', () => {
    expect(formatOrderDate('not a date')).toBeNull();
    expect(formatOrderDate('')).toBeNull();
    expect(formatOrderDate(null)).toBeNull();
    expect(formatOrderDate(undefined)).toBeNull();
  });
});

describe('statusTone', () => {
  /* The website's rule, ported: only `completed` is a success. */
  it('treats only a completed charge as a success', () => {
    expect(statusTone('completed')).toBe('ok');
  });

  it('treats everything else as a warning, failures included', () => {
    expect(statusTone('pending')).toBe('warn');
    expect(statusTone('failed')).toBe('warn');
    expect(statusTone('cancelled')).toBe('warn');
    expect(statusTone(undefined)).toBe('warn');
    expect(statusTone('COMPLETED')).toBe('warn');
  });
});

describe('statusLabel', () => {
  it('capitalises the server word', () => {
    expect(statusLabel('completed')).toBe('Completed');
    expect(statusLabel('pending')).toBe('Pending');
  });

  it('never renders an empty badge', () => {
    expect(statusLabel('')).toBe('Unknown');
    expect(statusLabel(undefined)).toBe('Unknown');
  });
});

describe('planLabel and billingPeriodLabel', () => {
  const withPlan = (plan: PaymentOrder['plan']): PaymentOrder =>
    plan === undefined ? { reference: 'R' } : { reference: 'R', plan };

  it('names the plan that was bought', () => {
    expect(planLabel(withPlan({ name: 'Basic Yearly', billing_period: 'yearly' }))).toBe(
      'Basic Yearly',
    );
    expect(billingPeriodLabel(withPlan({ name: 'Basic Yearly', billing_period: 'yearly' }))).toBe(
      'Yearly subscription',
    );
  });

  /*
   * An order can point at a payable that is not a plan. The em dash is the
   * website's own fallback and means "this server does not know".
   */
  it('falls back to an em dash when the order is not a plan purchase', () => {
    expect(planLabel(withPlan(null))).toBe('—');
    expect(planLabel(withPlan(undefined))).toBe('—');
    expect(planLabel(withPlan({ name: '', billing_period: 'monthly' }))).toBe('—');
  });

  it('omits the period line rather than inventing one', () => {
    expect(billingPeriodLabel(withPlan(null))).toBeNull();
    expect(billingPeriodLabel(withPlan({ name: 'Day Pass', billing_period: null }))).toBeNull();
    expect(billingPeriodLabel(withPlan({ name: 'Day Pass', billing_period: '' }))).toBeNull();
  });
});

describe('optionalDetail', () => {
  it('keeps a real value', () => {
    expect(optionalDetail('PSP-9017-77BX')).toBe('PSP-9017-77BX');
  });

  /* A pending order has neither, and neither may reach the screen as text. */
  it('is null for anything absent', () => {
    expect(optionalDetail(null)).toBeNull();
    expect(optionalDetail(undefined)).toBeNull();
    expect(optionalDetail('')).toBeNull();
    expect(optionalDetail('   ')).toBeNull();
  });
});
