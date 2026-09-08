import Constants from 'expo-constants';

/**
 * What version of the app this is, read from the manifest rather than typed.
 *
 * A hardcoded version string is wrong the first time anybody ships and stays
 * wrong silently. This is also the number the update gate compares, so a
 * hardcoded one would eventually lock every viewer out of an app that was
 * perfectly current, or let an obsolete one through.
 */
export function appVersion(): string | null {
  const version = Constants.expoConfig?.version;
  return typeof version === 'string' && version !== '' ? version : null;
}

/**
 * Compares two dotted version strings. -1, 0 or 1, like a comparator.
 *
 * Deliberately not semver-complete: it understands the `1.2.3` shape that
 * `app.json` and `GET /app/config` both use, and nothing else. Missing
 * segments count as zero, so `1.2` and `1.2.0` are equal, and a segment that
 * is not a number is treated as zero rather than throwing — a malformed
 * `min_app_version` from the server must not be able to crash a launch.
 */
export function compareVersions(a: string, b: string): number {
  const parse = (v: string) => v.split('.').map((part) => Number.parseInt(part, 10) || 0);
  const left = parse(a);
  const right = parse(b);
  const length = Math.max(left.length, right.length);

  for (let i = 0; i < length; i++) {
    const l = left[i] ?? 0;
    const r = right[i] ?? 0;
    if (l !== r) return l < r ? -1 : 1;
  }

  return 0;
}

/**
 * Whether this build is below the server's floor.
 *
 * Answers `false` when either version is unreadable. That is the deliberate
 * direction: an app that cannot tell should keep working. Locking somebody out
 * of a paid subscription because a version string did not parse is a worse
 * outcome than letting an old build run one more day.
 */
export function isUpdateRequired(current: string | null, minimum: string | undefined): boolean {
  if (current === null || minimum === undefined || minimum === '') return false;
  return compareVersions(current, minimum) < 0;
}
