import type { Device } from '../../api/endpoints';

/**
 * The line under a device's name.
 *
 * Rio's mockup draws a city on every row. `location` is it when the server
 * could resolve one — and **null is the normal answer**, because a private
 * address has no location and the geolocation database is not in the
 * repository, so a server without one answers null for every device.
 *
 * The fallback is the raw address, which is exactly what the website's own
 * devices page has always shown. Failing that, an app install describes itself
 * by model and version, which is the only other thing that distinguishes one
 * phone from another in a list.
 *
 * **Nothing here guesses.** A wrong city on a security screen is worse than no
 * city: the line exists so somebody can say "I have never been there" and act
 * on it.
 */
export function whereFrom(device: Device): string | null {
  const location = device.location;
  if (typeof location === 'string' && location !== '') return location;

  const ip = device.ip_address;
  if (typeof ip === 'string' && ip !== '') return ip;

  /*
   * `typeof === 'string'` on the version, not a check against `undefined`.
   *
   * The first cut asked `app_version === undefined` and rendered **"vnull"**
   * on every install that had never reported one — the server sends null, not
   * undefined, and `` `v${null}` `` is the string "vnull". Seen on a device
   * rather than in a test, which is why the row is now built from the values
   * that are strings instead of the values that are not undefined.
   */
  const version = device.app_version;
  const parts = [device.model, typeof version === 'string' && version !== '' ? `v${version}` : null]
    .filter((part): part is string => typeof part === 'string' && part !== '');

  return parts.length > 0 ? parts.join(' · ') : null;
}

/**
 * "Active now", "2 hours ago", "3 days ago".
 *
 * The website prints `diffForHumans()` on the same value; this is that, with
 * the recent end collapsed. Anything inside two minutes reads as active now
 * rather than "1 minute ago", because a device that is currently in use and
 * one that was in use ninety seconds ago are the same fact to a viewer.
 */
const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

export function lastActive(iso: string | null | undefined, now: number = Date.now()): string {
  if (typeof iso !== 'string' || iso === '') return '';

  const when = new Date(iso).getTime();
  if (Number.isNaN(when)) return '';

  const elapsed = now - when;

  if (elapsed < 2 * MINUTE) return 'Active now';
  if (elapsed < HOUR) return `${Math.floor(elapsed / MINUTE)} minutes ago`;
  if (elapsed < DAY) {
    const hours = Math.floor(elapsed / HOUR);
    return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
  }

  const days = Math.floor(elapsed / DAY);
  return days === 1 ? '1 day ago' : `${days} days ago`;
}

/**
 * Which devices count against the watching limit, and which do not.
 *
 * Rio's mockup splits Active from Inactive, and unlike most of that drawing
 * this one had a real rule waiting for it: `AccountDeviceRegistry::countAgainstCap`
 * already ignores anything not seen inside the session lifetime. So the split
 * is not "recent versus old" invented here — it is the server's own answer to
 * "does this take one of your slots", which is the only reason a viewer would
 * care about the distinction at all.
 *
 * The cutoff mirrors the server's session lifetime. It is a constant rather
 * than a value from the API because the endpoint does not send it; that is a
 * known duplication, named here so it is findable, and the consequence of it
 * drifting is a device in the wrong section rather than a wrong decision.
 */
export const ACTIVE_WINDOW_MS = 2 * HOUR;

export type DeviceSections = {
  active: Device[];
  inactive: Device[];
};

export function splitByActivity(
  devices: readonly Device[],
  now: number = Date.now(),
): DeviceSections {
  const active: Device[] = [];
  const inactive: Device[] = [];

  for (const device of devices) {
    // The current device is always active, whatever its stamp says. It is
    // making the request that produced this list, and the stamp is written at
    // most once a minute — so a freshly launched app can list itself as idle.
    if (device.is_current === true) {
      active.push(device);
      continue;
    }

    const seen = typeof device.last_seen_at === 'string' ? new Date(device.last_seen_at).getTime() : NaN;

    // An unreadable timestamp sorts as active. The alternative is filing a
    // device a viewer may not recognise under a heading that reads as
    // harmless.
    if (Number.isNaN(seen) || now - seen < ACTIVE_WINDOW_MS) active.push(device);
    else inactive.push(device);
  }

  return { active, inactive };
}

/**
 * The meter's label and how full the bar is.
 *
 * **It counts streams, not devices**, and the label says so. Rio's mockup drew
 * "4 of 6 devices used", which describes a cap Jambo does not have: nothing
 * stops an account registering a hundred installs. What the tier enforces is
 * how many can watch at once.
 *
 * `null` for the whole thing when there is no cap to report — no subscription,
 * or a tier that sets none. Drawing a meter with no limit would need a
 * denominator, and the only honest denominator is absent.
 */
export type StreamMeter = { label: string; fraction: number; full: boolean };

export function streamMeter(streams: {
  watching?: number | undefined;
  limit?: number | null | undefined;
}): StreamMeter | null {
  const limit = streams.limit;

  if (typeof limit !== 'number' || limit <= 0) return null;

  const watching = typeof streams.watching === 'number' && streams.watching >= 0 ? streams.watching : 0;

  // Clamped, because the server counts sessions and the cap can be lowered by
  // a plan change after those sessions exist. A bar past its own end is a
  // rendering fault; being over the cap is a real state.
  const fraction = Math.min(1, watching / limit);

  return {
    label: `${watching} of ${limit} watching now`,
    fraction,
    full: watching >= limit,
  };
}
