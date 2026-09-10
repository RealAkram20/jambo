import type { Wallet } from '../../api/endpoints';

/** One ledger row, as the wallet endpoint returns it. */
export type Entry = NonNullable<Wallet['entries']>[number];

/** One withdrawal request, as the wallet endpoint returns it. */
export type Withdrawal = NonNullable<Wallet['withdrawals']>[number];

/**
 * Money as a viewer reads it: "UGX 12,500".
 *
 * **The server's decimal formatting is not reliable and must not be trusted.**
 * `Ledger::balanceFor` returns a string whose shape follows the database
 * driver — MySQL answers "15000.00" where SQLite answers "15000" — so anything
 * that slices, pads or compares these strings is passing by luck of the
 * connection. That was found by four tests failing on it, not by reading the
 * code. Parse once, format here.
 *
 * Whole units, no decimals, because that is what the website does:
 * `number_format((float) $balance, 0)` on every amount its wallet page prints.
 * Uganda shillings are not quoted in cents.
 *
 * An unparseable amount returns an em dash rather than "NaN" or a zero. A zero
 * is the dangerous one: `UGX 0` reads as a real balance of nothing, and this
 * function must never turn "we do not know" into "you have none".
 */
export function money(amount: string | number | null | undefined, currency: string): string {
  const value = typeof amount === 'number' ? amount : Number.parseFloat(amount ?? '');

  if (!Number.isFinite(value)) return '—';

  const whole = Math.round(Math.abs(value));
  const grouped = whole.toLocaleString('en-US');

  return `${value < 0 ? '-' : ''}${currency} ${grouped}`;
}

/**
 * The same, with an explicit sign, for a ledger row.
 *
 * The website prints a leading "+" on anything that is not negative and lets
 * the minus sign speak for itself. Money moving in and money moving out are
 * the only two things this list says, so the sign is the content.
 */
export function signedMoney(amount: string | null | undefined, currency: string): string {
  const value = Number.parseFloat(amount ?? '');

  if (!Number.isFinite(value)) return '—';

  return value >= 0 ? `+ ${money(value, currency)}` : `- ${money(Math.abs(value), currency)}`;
}

/** Whether a ledger row took money out. Drives the colour and nothing else. */
export function isOutgoing(amount: string | null | undefined): boolean {
  const value = Number.parseFloat(amount ?? '');

  return Number.isFinite(value) && value < 0;
}

/**
 * Can this viewer ask for a withdrawal at all?
 *
 * Three reasons it may be refused, and the screen says which. A disabled
 * button that does not explain itself is the control people report as broken —
 * and here the explanation is a real constraint rather than a hint, which is
 * what earns it the words.
 *
 * The comparison is numeric on purpose. See `money` above: comparing these as
 * strings would make "15000" and "15000.00" different numbers.
 */
export type WithdrawState =
  | { can: true }
  | { can: false; reason: 'open' | 'below-minimum' | 'empty'; minimum: string };

export function withdrawState(wallet: {
  balance?: string | undefined;
  min_withdrawal?: string | undefined;
  has_open_withdrawal?: boolean | undefined;
}): WithdrawState {
  const minimum = wallet.min_withdrawal ?? '0';

  if (wallet.has_open_withdrawal === true) {
    return { can: false, reason: 'open', minimum };
  }

  const balance = Number.parseFloat(wallet.balance ?? '');
  const min = Number.parseFloat(minimum);

  if (!Number.isFinite(balance) || balance <= 0) {
    return { can: false, reason: 'empty', minimum };
  }

  if (Number.isFinite(min) && balance < min) {
    return { can: false, reason: 'below-minimum', minimum };
  }

  return { can: true };
}

/**
 * What a ledger row is called.
 *
 * The website badges each type with a word, and these are those words. Two are
 * worth noticing: `spend` is called "Subscription" because that is the only
 * thing a Jambo wallet can be spent on, and `hold_release` is called "Returned"
 * because from the viewer's side that is what a rejected withdrawal does.
 *
 * An unknown type is title-cased rather than dropped. A ledger row the app has
 * no word for is still money that moved, and hiding it would make the balance
 * stop adding up.
 */
const ENTRY_LABELS: Readonly<Record<string, string>> = {
  referral_reward: 'Referral reward',
  statement_credit: 'Statement credit',
  performance_credit: 'Performance credit',
  refund: 'Refund',
  spend: 'Subscription',
  withdrawal_hold: 'Withdrawal',
  hold_release: 'Returned',
  adjustment: 'Adjustment',
};

export function entryLabel(type: string | null | undefined): string {
  if (typeof type !== 'string' || type === '') return 'Entry';

  const known = ENTRY_LABELS[type];
  if (known !== undefined) return known;

  const words = type.replaceAll('_', ' ');
  return words.charAt(0).toUpperCase() + words.slice(1);
}

/** The words the website badges a withdrawal with. */
const WITHDRAWAL_LABELS: Readonly<Record<string, string>> = {
  requested: 'Requested',
  approved: 'Approved',
  paid: 'Paid',
  rejected: 'Rejected',
};

export function withdrawalLabel(status: string | null | undefined): string {
  if (typeof status !== 'string' || status === '') return 'Requested';

  return WITHDRAWAL_LABELS[status] ?? status;
}

/**
 * A date as the wallet lists it: "Sep 7, 2026".
 *
 * The website prints the time too, because a desktop table has the room. A
 * phone row has a title, a memo and an amount competing for one line, and the
 * hour of a referral reward has never been the thing anybody came to find.
 */
export function walletDate(iso: string | null | undefined): string {
  if (typeof iso !== 'string' || iso === '') return '';

  const when = new Date(iso);
  if (Number.isNaN(when.getTime())) return '';

  return when.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
