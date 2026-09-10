import {
  entryLabel,
  isOutgoing,
  money,
  signedMoney,
  walletDate,
  withdrawState,
  withdrawalLabel,
} from './money';

describe('money', () => {
  it('groups and prefixes the currency, as the website prints it', () => {
    expect(money('12500.00', 'UGX')).toBe('UGX 12,500');
    expect(money('500', 'UGX')).toBe('UGX 500');
  });

  /*
   * The reason this function exists. `Ledger::balanceFor` returns a string
   * whose decimals follow the database driver — MySQL says "15000.00" and
   * SQLite says "15000" — so both must render identically or the app's numbers
   * depend on the connection behind the API.
   */
  it('renders both shapes the server can send for one amount identically', () => {
    expect(money('15000', 'UGX')).toBe(money('15000.00', 'UGX'));
  });

  it('rounds to whole shillings, because the website does', () => {
    expect(money('12500.60', 'UGX')).toBe('UGX 12,501');
  });

  /*
   * The dangerous case. A zero here would read as a real balance of nothing,
   * which is a lie about money rather than a rendering glitch.
   */
  it('gives an em dash for an amount it cannot read, never a zero', () => {
    expect(money(undefined, 'UGX')).toBe('—');
    expect(money(null, 'UGX')).toBe('—');
    expect(money('', 'UGX')).toBe('—');
    expect(money('not money', 'UGX')).toBe('—');
  });

  it('still renders a real zero, which is a different thing', () => {
    expect(money('0', 'UGX')).toBe('UGX 0');
  });
});

describe('signedMoney', () => {
  it('signs both directions, which is the whole content of a ledger row', () => {
    expect(signedMoney('20000.00', 'UGX')).toBe('+ UGX 20,000');
    expect(signedMoney('-3000.00', 'UGX')).toBe('- UGX 3,000');
  });

  it('gives an em dash rather than a signed nothing', () => {
    expect(signedMoney(null, 'UGX')).toBe('—');
  });
});

describe('isOutgoing', () => {
  it('is true only for money that left', () => {
    expect(isOutgoing('-3000.00')).toBe(true);
    expect(isOutgoing('3000.00')).toBe(false);
    expect(isOutgoing('0')).toBe(false);
  });

  it('is false for an amount it cannot read, so nothing is coloured on a guess', () => {
    expect(isOutgoing(undefined)).toBe(false);
    expect(isOutgoing('nonsense')).toBe(false);
  });
});

describe('withdrawState', () => {
  it('allows a withdrawal at or above the minimum', () => {
    expect(withdrawState({ balance: '20000', min_withdrawal: '10000' })).toEqual({ can: true });
    expect(withdrawState({ balance: '10000', min_withdrawal: '10000' })).toEqual({ can: true });
  });

  /* The comparison must be numeric. As strings, "10000" < "9000". */
  it('compares by value, not by string', () => {
    expect(withdrawState({ balance: '10000.00', min_withdrawal: '10000' })).toEqual({ can: true });
    expect(withdrawState({ balance: '9000', min_withdrawal: '10000' }).can).toBe(false);
  });

  it('refuses while one is already in progress, whatever the balance', () => {
    expect(
      withdrawState({ balance: '50000', min_withdrawal: '10000', has_open_withdrawal: true }),
    ).toEqual({ can: false, reason: 'open', minimum: '10000' });
  });

  /* An empty wallet and a wallet below the minimum need different sentences:
     one says earn something, the other says earn more. */
  it('tells an empty wallet apart from one below the minimum', () => {
    expect(withdrawState({ balance: '0', min_withdrawal: '10000' })).toMatchObject({
      can: false,
      reason: 'empty',
    });
    expect(withdrawState({ balance: '5000', min_withdrawal: '10000' })).toMatchObject({
      can: false,
      reason: 'below-minimum',
    });
  });

  it('refuses when the balance cannot be read at all', () => {
    expect(withdrawState({ min_withdrawal: '10000' }).can).toBe(false);
    expect(withdrawState({ balance: 'nonsense', min_withdrawal: '10000' }).can).toBe(false);
  });
});

describe('entryLabel', () => {
  it('uses the website’s own words', () => {
    expect(entryLabel('referral_reward')).toBe('Referral reward');
    expect(entryLabel('spend')).toBe('Subscription');
    expect(entryLabel('hold_release')).toBe('Returned');
    expect(entryLabel('withdrawal_hold')).toBe('Withdrawal');
  });

  /* A ledger type the app has no word for is still money that moved. Dropping
     it would make the running balance stop adding up. */
  it('title-cases an unknown type rather than hiding the row', () => {
    expect(entryLabel('chargeback_reversal')).toBe('Chargeback reversal');
  });

  it('has something to say for a missing type', () => {
    expect(entryLabel(undefined)).toBe('Entry');
    expect(entryLabel('')).toBe('Entry');
  });
});

describe('withdrawalLabel', () => {
  it('uses the website’s badge words', () => {
    expect(withdrawalLabel('requested')).toBe('Requested');
    expect(withdrawalLabel('paid')).toBe('Paid');
    expect(withdrawalLabel('rejected')).toBe('Rejected');
  });
});

describe('walletDate', () => {
  it('reads as a date, not a timestamp', () => {
    expect(walletDate('2026-09-07T10:30:00.000Z')).toBe('Sep 7, 2026');
  });

  it('draws nothing rather than the words Invalid Date', () => {
    expect(walletDate('not a date')).toBe('');
    expect(walletDate(null)).toBe('');
    expect(walletDate('')).toBe('');
  });
});
