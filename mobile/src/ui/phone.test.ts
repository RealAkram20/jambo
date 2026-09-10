import { dialCode, formatAsTyped, formatForDisplay, isMobile, toE164 } from './phone';

/**
 * Rio's fifteen examples, 2026-09-10, are the specification for this file.
 *
 * Every one of them is the same Ugandan mobile line written a different way,
 * and the requirement he gave is that the app reads all of them. So they are
 * asserted literally rather than paraphrased into "a few representative
 * cases" — the list IS the contract, and a case dropped here is a format a
 * viewer can type that the app will refuse.
 */
const E164 = '+256742078673';

describe("every format Rio listed resolves to one number", () => {
  const international = [
    '+256742078673',
    '+256 742 078 673',
    '+256-742-078-673',
    '+256 (742) 078-673',
    '256742078673',
    '256 742 078 673',
    '00256742078673',
    '00 256 742 078 673',
  ];

  const local = [
    '0742078673',
    '0742 078 673',
    '0742-078-673',
    '(0742) 078 673',
    '742078673',
    '742 078 673',
    '742-078-673',
  ];

  /* The ones he called out as accidental — a half-spaced paste, mostly. */
  const accidental = [
    '+256 742078673',
    '+256-742078673',
    '0742 078673',
    '0742078 673',
    '0742-078673',
  ];

  it.each(international)('reads the international form %s', (input) => {
    expect(toE164(input)).toBe(E164);
  });

  it.each(local)('reads the local form %s', (input) => {
    expect(toE164(input)).toBe(E164);
  });

  it.each(accidental)('reads the accidental form %s', (input) => {
    expect(toE164(input)).toBe(E164);
  });
});

describe('toE164', () => {
  it('returns null rather than an empty string for nothing', () => {
    expect(toE164('')).toBeNull();
    expect(toE164('   ')).toBeNull();
  });

  /*
   * Null is not "no phone typed", it is "that is not a phone number". The
   * caller must not store either one as a number.
   */
  it('refuses a number that is not a real line', () => {
    expect(toE164('0123456789')).toBeNull();
    expect(toE164('12345')).toBeNull();
    expect(toE164('not a number')).toBeNull();
  });

  /*
   * Kenya rather than a European example, because Kenya is a market Jambo
   * plausibly reaches and the default is Uganda — so this asserts the country
   * argument is actually used rather than ignored.
   *
   * The first draft of this test used `07700 900123`, and libphonenumber was
   * right to refuse it: that range is reserved by Ofcom for use in fiction, so
   * it is a valid-looking number that is not a number. A good reminder that
   * "looks like a phone number" and "is one" are different questions, which is
   * the whole reason the library is here.
   */
  it('reads a number for a country other than the default', () => {
    expect(toE164('0712345678', 'KE')).toBe('+254712345678');
    expect(toE164('0755123456', 'TZ')).toBe('+255755123456');
  });
});

describe('isMobile, which is what a payout depends on', () => {
  it('accepts a real Ugandan mobile line', () => {
    expect(isMobile('0742078673')).toBe(true);
    expect(isMobile('+256742078673')).toBe(true);
  });

  /*
   * The case the whole check exists for. A landline is a valid phone number
   * and mobile money cannot be sent to it, so "valid" is not the question.
   */
  it('refuses a Ugandan landline', () => {
    expect(toE164('0414230000')).toBe('+256414230000');
    expect(isMobile('0414230000')).toBe(false);
  });

  it('refuses nonsense', () => {
    expect(isMobile('0123456789')).toBe(false);
    expect(isMobile('')).toBe(false);
  });
});

describe('formatAsTyped', () => {
  /*
   * Rio's grouping, which is NOT the library's. `AsYouType('UG')` gives
   * `0742 078673` — four then six, the official convention — and his examples
   * are threes. His examples are the specification.
   */
  it('groups a Ugandan local number the way Rio wrote it', () => {
    expect(formatAsTyped('0742078673')).toBe('0742 078 673');
  });

  it('groups a Ugandan international number the way Rio wrote it', () => {
    expect(formatAsTyped('+256742078673')).toBe('+256 742 078 673');
  });

  /* Progressive, like a card field: every keystroke returns a whole value. */
  it('formats while the number is still being typed', () => {
    expect(formatAsTyped('074')).toBe('074');
    expect(formatAsTyped('0742')).toBe('0742');
    expect(formatAsTyped('07420')).toBe('0742 0');
    expect(formatAsTyped('0742078')).toBe('0742 078');
    expect(formatAsTyped('+2567')).toBe('+256 7');
  });

  /**
   * The rule that matters most in this file.
   *
   * A formatter that drops a character is a formatter that loses a phone
   * number, and nobody finds out until somebody cannot be reached. Whatever
   * the spacing, the digits must survive.
   */
  it('never adds, drops or reorders a digit', () => {
    for (const input of ['0742078673', '+256742078673', '07', '0742078673999', '256']) {
      expect(formatAsTyped(input).replace(/\D/g, '')).toBe(input.replace(/\D/g, ''));
    }
  });

  it('leaves a country with no stated grouping to the library', () => {
    // Not asserted against a literal: the point is that it is the library's
    // answer for GB, not Uganda's threes imposed on it.
    expect(formatAsTyped('07700900123', 'GB')).not.toBe('0770 090 012 3');
  });

  it('survives an empty field and a lone plus', () => {
    expect(formatAsTyped('')).toBe('');
    expect(formatAsTyped('+')).toBe('+');
  });
});

describe('formatForDisplay', () => {
  it('shows a stored number in the international grouping Rio wrote', () => {
    expect(formatForDisplay(E164)).toBe('+256 742 078 673');
  });

  /*
   * A number stored before this file existed, in whatever shape somebody typed
   * it. Showing it raw is right: it is the only number that account has, and
   * blanking it would read as "no phone".
   */
  it('shows an unparseable stored value rather than hiding it', () => {
    expect(formatForDisplay('0742-078-673 ext 4')).toBe('0742-078-673 ext 4');
  });

  it('shows nothing for nothing', () => {
    expect(formatForDisplay(null)).toBe('');
    expect(formatForDisplay('')).toBe('');
  });
});

describe('dialCode', () => {
  it('gives the code for the selected country', () => {
    expect(dialCode('UG')).toBe('+256');
    expect(dialCode('ug')).toBe('+256');
    expect(dialCode('KE')).toBe('+254');
    expect(dialCode('GB')).toBe('+44');
  });

  /* The picker offers every ISO code; a few have no calling code of their own. */
  it('gives nothing rather than a guess for a country it cannot answer', () => {
    expect(dialCode(null)).toBe('');
    expect(dialCode('')).toBe('');
    expect(dialCode('ZZ')).toBe('');
  });
});
