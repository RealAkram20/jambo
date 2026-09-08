import { compareVersions, isUpdateRequired } from './version';

describe('compareVersions', () => {
  it('orders by segment, not by string', () => {
    // The string comparison this replaces gets this exact case wrong: '10' < '9'
    // alphabetically, so a lexical compare would lock every viewer out at 1.10.0.
    expect(compareVersions('1.10.0', '1.9.0')).toBe(1);
    expect(compareVersions('1.9.0', '1.10.0')).toBe(-1);
  });

  it('treats missing segments as zero', () => {
    expect(compareVersions('1.2', '1.2.0')).toBe(0);
    expect(compareVersions('1.2', '1.2.1')).toBe(-1);
  });

  it('does not throw on a segment that is not a number', () => {
    expect(compareVersions('1.0.0-beta', '1.0.0')).toBe(0);
  });
});

describe('isUpdateRequired', () => {
  it('is true only below the floor', () => {
    expect(isUpdateRequired('1.0.0', '1.1.0')).toBe(true);
    expect(isUpdateRequired('1.1.0', '1.1.0')).toBe(false);
    expect(isUpdateRequired('1.2.0', '1.1.0')).toBe(false);
  });

  /*
   * The direction of the failure is the decision here. An app that cannot read
   * either version keeps working. Locking somebody out of a subscription they
   * have paid for, because a version string did not parse or the server sent
   * nothing, is a worse outcome than an old build running one more day.
   */
  it('lets the app run when either version is unreadable', () => {
    expect(isUpdateRequired(null, '1.1.0')).toBe(false);
    expect(isUpdateRequired('1.0.0', undefined)).toBe(false);
    expect(isUpdateRequired('1.0.0', '')).toBe(false);
  });
});
