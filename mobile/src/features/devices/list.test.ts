import type { Device } from '../../api/endpoints';
import { lastActive, splitByActivity, streamMeter, whereFrom } from './list';

const AT = new Date('2026-09-09T12:00:00.000Z').getTime();

function device(over: Partial<Device> = {}): Device {
  return { id: 'x', kind: 'app', name: 'A phone', is_current: false, ...over } as Device;
}

describe('whereFrom', () => {
  it('prefers the resolved city, which is what the mockup draws', () => {
    expect(whereFrom(device({ location: 'Kampala, Uganda', ip_address: '41.210.1.1' }))).toBe(
      'Kampala, Uganda',
    );
  });

  /*
   * The normal case, not the edge case. A private address has no location and
   * the geolocation database is not in the repository, so a server without one
   * answers null for every row.
   */
  it('falls back to the address, which is what the website shows', () => {
    expect(whereFrom(device({ ip_address: '41.210.1.1' }))).toBe('41.210.1.1');
    expect(whereFrom(device({ location: null, ip_address: '41.210.1.1' }))).toBe('41.210.1.1');
  });

  it('describes an app install by model and version when it has neither', () => {
    expect(whereFrom(device({ model: 'Tecno Spark 10', app_version: '1.2.0' }))).toBe(
      'Tecno Spark 10 · v1.2.0',
    );
  });

  /*
   * Found on a device, not here. The first cut asked whether the version was
   * `undefined`; the server sends null, and `v${null}` is the string "vnull",
   * which is what a row for an install that never reported a version actually
   * said on the emulator.
   */
  it('never renders the word null as a version', () => {
    expect(whereFrom(device({ name: 'Jambo app', app_version: null }))).toBeNull();
    expect(whereFrom(device({ model: 'TECNO KI7', app_version: null }))).toBe('TECNO KI7');
    expect(whereFrom(device({ model: 'TECNO KI7', app_version: '' }))).toBe('TECNO KI7');
  });

  /* Nothing to say is null, so the row draws no line rather than an empty one. */
  it('says nothing rather than something empty', () => {
    expect(whereFrom(device())).toBeNull();
    expect(whereFrom(device({ location: '', ip_address: '' }))).toBeNull();
  });
});

describe('lastActive', () => {
  it('collapses the recent end to Active now', () => {
    expect(lastActive('2026-09-09T12:00:00.000Z', AT)).toBe('Active now');
    expect(lastActive('2026-09-09T11:59:00.000Z', AT)).toBe('Active now');
  });

  it('reads as the website reads', () => {
    expect(lastActive('2026-09-09T11:30:00.000Z', AT)).toBe('30 minutes ago');
    expect(lastActive('2026-09-09T10:00:00.000Z', AT)).toBe('2 hours ago');
    expect(lastActive('2026-09-06T12:00:00.000Z', AT)).toBe('3 days ago');
  });

  it('does not say 1 hours or 1 days', () => {
    expect(lastActive('2026-09-09T11:00:00.000Z', AT)).toBe('1 hour ago');
    expect(lastActive('2026-09-08T12:00:00.000Z', AT)).toBe('1 day ago');
  });

  it('draws nothing rather than the words Invalid Date', () => {
    expect(lastActive('not a date', AT)).toBe('');
    expect(lastActive(null, AT)).toBe('');
  });
});

describe('splitByActivity', () => {
  it('files a recent device as active and an old one as inactive', () => {
    const recent = device({ id: 'r', last_seen_at: '2026-09-09T11:30:00.000Z' });
    const old = device({ id: 'o', last_seen_at: '2026-08-28T12:00:00.000Z' });

    const { active, inactive } = splitByActivity([recent, old], AT);

    expect(active.map((d) => d.id)).toEqual(['r']);
    expect(inactive.map((d) => d.id)).toEqual(['o']);
  });

  /*
   * The stamp is written at most once a minute, so a freshly launched app can
   * list itself as idle. It is making the request; it is active.
   */
  it('always files this device as active, whatever its stamp says', () => {
    const stale = device({ id: 'me', is_current: true, last_seen_at: '2026-08-01T12:00:00.000Z' });

    expect(splitByActivity([stale], AT).active.map((d) => d.id)).toEqual(['me']);
  });

  /*
   * Filing an unreadable device under Inactive would put something a viewer
   * may not recognise beneath a heading that reads as harmless.
   */
  it('files an unreadable timestamp as active rather than hiding it', () => {
    const broken = device({ id: 'b', last_seen_at: 'not a date' });

    expect(splitByActivity([broken], AT).active.map((d) => d.id)).toEqual(['b']);
  });

  it('returns two empty lists for no devices', () => {
    expect(splitByActivity([], AT)).toEqual({ active: [], inactive: [] });
  });
});

describe('streamMeter', () => {
  it('counts streams and says so, because it is not a device cap', () => {
    expect(streamMeter({ watching: 2, limit: 4 })).toEqual({
      label: '2 of 4 watching now',
      fraction: 0.5,
      full: false,
    });
  });

  /*
   * Null is not zero. A zero limit would draw a full bar and read as "you may
   * watch on nothing", so there is no meter at all rather than a wrong one.
   */
  it('draws no meter when there is no cap to report', () => {
    expect(streamMeter({ watching: 2, limit: null })).toBeNull();
    expect(streamMeter({ watching: 2 })).toBeNull();
    expect(streamMeter({ watching: 2, limit: 0 })).toBeNull();
  });

  it('knows when the limit is reached', () => {
    expect(streamMeter({ watching: 4, limit: 4 })?.full).toBe(true);
    expect(streamMeter({ watching: 3, limit: 4 })?.full).toBe(false);
  });

  /*
   * Being over the cap is a real state — a plan can be downgraded while
   * sessions exist — but a bar past its own end is a rendering fault.
   */
  it('clamps the bar without hiding that the limit is passed', () => {
    const meter = streamMeter({ watching: 6, limit: 4 });

    expect(meter?.fraction).toBe(1);
    expect(meter?.full).toBe(true);
    expect(meter?.label).toBe('6 of 4 watching now');
  });
});
