import type { PaymentOrder, SpendTotals } from './api';

/**
 * How an order reads on screen. No React in here, so it can be tested without
 * standing up a device — the same reason `media.ts` and `profileMenu.ts` sit
 * apart from their components.
 *
 * Every function here decides something a viewer reads as a fact about money
 * they have paid, and each has a wrong answer that looks entirely reasonable:
 * a rounded total, a failed charge coloured as a success, a plan name that is
 * really the string "undefined".
 */

/**
 * The Billing summary line, or null for no summary at all.
 *
 * Three states, and the two that return null are the reason this is a function
 * rather than a template string in the screen:
 *
 *  - **Nothing paid yet.** The screen already has an empty state for that; a
 *    line reading "UGX 0.00 across 0 orders" is worse than no line, because
 *    zero money reads as free rather than as absent.
 *  - **More than one currency.** The server sends `spent` and `currency` as
 *    null in that case rather than adding shillings to dollars, and the honest
 *    thing to draw is nothing. The order list itself still shows every charge
 *    in its own currency, so nothing is hidden — only the sum is withheld,
 *    because the sum is the part that cannot be stated truthfully.
 *
 * The figure passes through `formatMoney`, so the summary and the rows below
 * it group and punctuate identically and neither recomputes a digit.
 */
export function spendSummary(totals: SpendTotals | null | undefined): string | null {
  if (totals === null || totals === undefined) return null;

  const orders = totals.orders ?? 0;
  if (orders === 0) return null;

  const spent = totals.spent;
  if (typeof spent !== 'string' || spent.trim() === '') return null;

  const money = formatMoney(spent, totals.currency);
  const charges = orders === 1 ? '1 charge' : `${orders} charges`;

  return `${money} across ${charges}`;
}

/**
 * Money, exactly as the server states it, with the website's grouping.
 *
 * **The digits are never recomputed.** `amount` arrives as a decimal string
 * (`"150000.00"`) and this inserts separators into that string; it does not
 * parse it into a float and print it back. A float round-trip is how a total
 * on an invoice stops matching the figure on a bank statement, and money a
 * client has done arithmetic on is money the viewer cannot check.
 *
 * `billing.blade.php` prints `number_format($order->amount, 2)`, so the app
 * groups in threes and keeps the two decimals the server sent, rather than
 * trimming a trailing `.00` the way the plan ladder does. A plan is a price
 * and an invoice is a receipt; the receipt keeps its cents.
 */
export function formatMoney(
  amount: string | null | undefined,
  currency: string | null | undefined,
): string {
  if (typeof amount !== 'string' || amount.trim() === '') return '—';

  const [whole = '', fraction] = amount.trim().split('.');
  const negative = whole.startsWith('-');
  const digits = negative ? whole.slice(1) : whole;

  // Anything that is not a plain decimal is passed through untouched rather
  // than mangled. The server is the authority on what it sent.
  if (!/^\d+$/.test(digits)) return currencyPrefix(currency) + amount.trim();

  const grouped = digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  const body = (negative ? '-' : '') + grouped + (fraction === undefined ? '' : `.${fraction}`);

  return currencyPrefix(currency) + body;
}

function currencyPrefix(currency: string | null | undefined): string {
  return typeof currency === 'string' && currency !== '' ? `${currency} ` : '';
}

/**
 * The date on a row, or null when there is not one.
 *
 * Null rather than a placeholder, so a caller decides whether to draw a line
 * at all. A row reading "Invalid Date" is worse than a row with no date.
 */
export function formatOrderDate(iso: string | null | undefined): string | null {
  if (typeof iso !== 'string' || iso === '') return null;

  const when = new Date(iso);
  if (Number.isNaN(when.getTime())) return null;

  // The same shape WalletScreen already uses. The app has one date treatment
  // and this is it.
  return when.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

/**
 * Which treatment a status badge takes.
 *
 * **This is the website's rule, ported rather than improved.**
 * `billing.blade.php` is `$order->status === 'completed' ? 'bg-success' :
 * 'bg-warning'` — so a failed or cancelled charge draws as a warning, not as
 * an error. Splitting those out would be a product decision about how Jambo
 * talks to somebody whose payment bounced, and that decision is not this
 * slice's to make.
 */
export function statusTone(status: string | null | undefined): 'ok' | 'warn' {
  return status === 'completed' ? 'ok' : 'warn';
}

/**
 * The status as a word.
 *
 * A word, because the badge's colour is the emphasis and never the message —
 * the same rule the security screen's status rows follow. `ucfirst`, exactly
 * as the blade does it.
 */
export function statusLabel(status: string | null | undefined): string {
  if (typeof status !== 'string' || status === '') return 'Unknown';

  return status.charAt(0).toUpperCase() + status.slice(1);
}

/**
 * What was bought.
 *
 * The em dash is the website's own fallback and it means "this server does
 * not know", not "nothing". An order can point at a payable that is not a
 * plan — the table is polymorphic and is meant to carry rentals later — and
 * inventing a label for that case would be the app asserting something the
 * server did not say.
 */
export function planLabel(order: PaymentOrder): string {
  const name = order.plan?.name;

  return typeof name === 'string' && name !== '' ? name : '—';
}

/**
 * The invoice's line-item subtitle: `Monthly subscription`.
 *
 * `invoice.blade.php` renders `ucfirst($billingPeriod) . ' subscription'` and
 * omits the line entirely when there is no period. Null here is that omission.
 */
export function billingPeriodLabel(order: PaymentOrder): string | null {
  const period = order.plan?.billing_period;
  if (typeof period !== 'string' || period === '') return null;

  return `${period.charAt(0).toUpperCase()}${period.slice(1)} subscription`;
}

/**
 * A nullable server string, or null. Never the word "null" on screen.
 *
 * Payment method and tracking id are both genuinely absent until the gateway
 * reports — a pending order has neither — and the website drops the whole row
 * rather than printing an empty label. So does the app.
 */
export function optionalDetail(value: string | null | undefined): string | null {
  return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
}
