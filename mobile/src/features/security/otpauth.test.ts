import { groupSecret, isCompleteCode, otpauthUri, sanitiseCode, TOTP_ISSUER } from './otpauth';

describe('otpauthUri', () => {
  /*
   * The website's QR encodes exactly this string. A viewer who scans on the
   * site and a viewer who scans in the app must end up with one entry in one
   * authenticator, under one name — so this is pinned character for character
   * against what pragmarx/google2fa's Support\QRCode builds.
   */
  it('matches what the website QR encodes', () => {
    expect(otpauthUri('ABCDEFGHIJKLMNOP', 'someone@example.com')).toBe(
      'otpauth://totp/Jambo:someone%40example.com' +
        '?secret=ABCDEFGHIJKLMNOP' +
        '&issuer=Jambo' +
        '&algorithm=SHA1&digits=6&period=30',
    );
  });

  /* rawurlencode escapes `@`, and encodeURIComponent must too, or the label
     splits at the wrong place in some authenticators. */
  it('escapes the at sign in the holder', () => {
    const uri = otpauthUri('SECRET', 'a@b.co') ?? '';
    expect(uri).toContain('Jambo:a%40b.co');
    expect(uri).not.toContain('Jambo:a@b.co');
  });

  it('escapes an issuer with a space in it', () => {
    const uri = otpauthUri('SECRET', 'a@b.co', 'Jambo Films') ?? '';
    expect(uri).toContain('otpauth://totp/Jambo%20Films:');
    expect(uri).toContain('&issuer=Jambo%20Films');
  });

  /* The secret is NOT url-encoded by the server, and base32 needs no
     encoding. Encoding it here would produce a URI the website's own QR
     does not match. */
  it('leaves the secret unencoded', () => {
    expect(otpauthUri('AAAA1111BBBB2222', 'a@b.co')).toContain('?secret=AAAA1111BBBB2222&');
  });

  it('falls back to the issuer when there is no email', () => {
    expect(otpauthUri('SECRET', null)).toContain(`otpauth://totp/${TOTP_ISSUER}:${TOTP_ISSUER}?`);
    expect(otpauthUri('SECRET', '   ')).toContain(`:${TOTP_ISSUER}?`);
  });

  /* A QR drawn from an empty string silently enrols nothing, so there must be
     no QR at all. */
  it('is null without a secret', () => {
    expect(otpauthUri(null, 'a@b.co')).toBeNull();
    expect(otpauthUri(undefined, 'a@b.co')).toBeNull();
    expect(otpauthUri('', 'a@b.co')).toBeNull();
    expect(otpauthUri('   ', 'a@b.co')).toBeNull();
  });
});

describe('groupSecret', () => {
  it('groups in fours for typing by hand', () => {
    expect(groupSecret('ABCDEFGHIJKLMNOP')).toBe('ABCD EFGH IJKL MNOP');
  });

  it('leaves a short tail alone rather than padding it', () => {
    expect(groupSecret('ABCDEFG')).toBe('ABCD EFG');
  });

  it('is an empty string when there is nothing to show', () => {
    expect(groupSecret(null)).toBe('');
    expect(groupSecret(undefined)).toBe('');
    expect(groupSecret('')).toBe('');
  });
});

describe('sanitiseCode', () => {
  /* Pasting from an authenticator's notification is the common case and it
     often carries a space. */
  it('keeps the digits out of a pasted code', () => {
    expect(sanitiseCode('123 456')).toBe('123456');
    expect(sanitiseCode('12-34-56')).toBe('123456');
  });

  it('drops the decimal separator Android offers on a numeric keyboard', () => {
    expect(sanitiseCode('123.456')).toBe('123456');
    expect(sanitiseCode('1,234')).toBe('1234');
  });

  it('never exceeds six digits', () => {
    expect(sanitiseCode('1234567890')).toBe('123456');
  });

  it('handles an empty field', () => {
    expect(sanitiseCode('')).toBe('');
    expect(sanitiseCode('abc')).toBe('');
  });
});

describe('isCompleteCode', () => {
  it('accepts exactly six digits', () => {
    expect(isCompleteCode('123456')).toBe(true);
  });

  it('rejects anything else', () => {
    expect(isCompleteCode('12345')).toBe(false);
    expect(isCompleteCode('1234567')).toBe(false);
    expect(isCompleteCode('12345a')).toBe(false);
    expect(isCompleteCode('')).toBe(false);
  });
});
