import {
  avatarFileName,
  detail,
  displayName,
  imageMimeType,
  memberSince,
  NOT_SET,
} from './profileFields';

/**
 * Each of these turns a possibly-absent value into something printed on
 * somebody's own profile, and each has a wrong answer that looks perfectly
 * reasonable on screen. That is what the cases below are chosen for — not the
 * happy path, which never broke.
 */

describe('memberSince', () => {
  it('renders the month and year the mockup asks for', () => {
    expect(memberSince('2025-01-14T09:30:00+00:00')).toBe('January 2025');
  });

  /*
   * The reason this is hand-rolled at all. Hermes ships different ICU data per
   * build, so `toLocaleDateString` can produce "Invalid Date" on a handset
   * while looking correct on a desk.
   */
  it('never renders Invalid Date', () => {
    expect(memberSince('not a date')).toBe(NOT_SET);
    expect(memberSince('')).toBe(NOT_SET);
  });

  it('renders the dash for an absent join date rather than an empty row', () => {
    expect(memberSince(null)).toBe(NOT_SET);
    expect(memberSince(undefined)).toBe(NOT_SET);
  });
});

describe('detail', () => {
  it('shows a real value', () => {
    expect(detail('akram@example.com')).toBe('akram@example.com');
  });

  /* The one that matters. A phone stored as spaces is not a phone number, and
     printing it leaves a label with nothing beside it, which reads as broken
     rather than as empty. */
  it('treats blank and whitespace as absent', () => {
    expect(detail('')).toBe(NOT_SET);
    expect(detail('   ')).toBe(NOT_SET);
  });

  it('shows the dash for null and undefined', () => {
    expect(detail(null)).toBe(NOT_SET);
    expect(detail(undefined)).toBe(NOT_SET);
  });

  it('trims, so a stray space does not shift the value off the row', () => {
    expect(detail('  +256 700 123 456  ')).toBe('+256 700 123 456');
  });
});

describe('displayName', () => {
  it('prefers the full name', () => {
    expect(displayName('Miiro', 'Akram', 'realakram207')).toBe('Miiro Akram');
  });

  it('falls back to the username, as the website does', () => {
    expect(displayName(null, null, 'realakram207')).toBe('realakram207');
    expect(displayName('  ', '  ', 'realakram207')).toBe('realakram207');
  });

  it('copes with only one half of the name', () => {
    expect(displayName('Miiro', null, 'realakram207')).toBe('Miiro');
    expect(displayName(null, 'Akram', 'realakram207')).toBe('Akram');
  });

  /* Empty, not "Unknown". A placeholder where somebody's own name goes is
     worse than a gap. */
  it('returns empty when it has nothing, rather than inventing a name', () => {
    expect(displayName(null, null, null)).toBe('');
    expect(displayName('', '', '')).toBe('');
  });
});

describe('imageMimeType', () => {
  it('trusts a reported image type', () => {
    expect(imageMimeType('file:///tmp/x.jpg', 'image/png')).toBe('image/png');
  });

  /*
   * The picker sometimes reports a bare "image" or nothing at all, and a
   * multipart part with no content type arrives as application/octet-stream —
   * which the endpoint's `mimes` rule rejects on a perfectly good photo.
   */
  it('ignores a reported type that is not an image type', () => {
    expect(imageMimeType('file:///tmp/x.png', 'image')).toBe('image/png');
    expect(imageMimeType('file:///tmp/x.png', null)).toBe('image/png');
    expect(imageMimeType('file:///tmp/x.png', undefined)).toBe('image/png');
  });

  it('reads the extension when nothing is reported', () => {
    expect(imageMimeType('file:///tmp/a.webp')).toBe('image/webp');
    expect(imageMimeType('file:///tmp/a.gif')).toBe('image/gif');
    expect(imageMimeType('file:///tmp/a.JPEG')).toBe('image/jpeg');
  });

  /* A cached or content:// URI can carry a query string, and ".jpg?w=200" is
     not an extension. */
  it('ignores a query string on the uri', () => {
    expect(imageMimeType('file:///tmp/a.png?width=200')).toBe('image/png');
  });

  it('falls back to jpeg, which is what a phone camera produces', () => {
    expect(imageMimeType('content://media/external/images/media/42')).toBe('image/jpeg');
    expect(imageMimeType('')).toBe('image/jpeg');
  });
});

describe('avatarFileName', () => {
  /* Laravel reads the extension as well as the detected type, so a part named
     "upload" with no extension is refused on a valid image. */
  it('always carries an extension', () => {
    expect(avatarFileName('image/jpeg')).toBe('avatar.jpg');
    expect(avatarFileName('image/png')).toBe('avatar.png');
    expect(avatarFileName('image/webp')).toBe('avatar.webp');
    expect(avatarFileName('image/gif')).toBe('avatar.gif');
  });

  it('names jpeg as jpg, which is the extension the rule expects', () => {
    expect(avatarFileName('image/jpeg')).not.toContain('jpeg');
  });
});
