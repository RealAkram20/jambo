import * as SecureStore from 'expo-secure-store';

import { ensureDeviceId, resetDeviceIdCache } from '../device/deviceId';
import { clearSession, readSession, writeSession } from './tokenStore';

const user = { id: 1, username: 'rio', email: 'rio@example.test' };

beforeEach(() => {
  resetDeviceIdCache();
});

describe('the device id', () => {
  it('is minted once and reused on the next launch', async () => {
    const first = await ensureDeviceId();

    resetDeviceIdCache(); // as if the app had been killed and relaunched
    const second = await ensureDeviceId();

    expect(second).toBe(first);
  });

  /*
   * The one this file exists for.
   *
   * A device id cleared on sign-out is invisible in every other test: sign-in
   * still works, requests still carry a header, nothing throws. What happens
   * instead is that every sign-in files a fresh row in `devices`, the viewer's
   * device list fills with handsets they cannot recognise, and `active_streams`
   * — which uses this uuid as the client session key — stops recognising the
   * same phone as the same phone, so the concurrent-stream cap silently stops
   * counting it. Nothing about that surfaces until a real account has been
   * signed in and out a few times on a real handset.
   */
  it('survives a sign-out', async () => {
    const before = await ensureDeviceId();
    await writeSession('token-abc', user);

    await clearSession();

    expect(await readSession()).toBeNull();

    resetDeviceIdCache(); // force a read from the keystore rather than memory
    expect(await ensureDeviceId()).toBe(before);
  });

  it('is stored under its own key, not the session namespace', async () => {
    await ensureDeviceId();
    await writeSession('token-abc', user);
    await clearSession();

    // Whatever clearSession deletes, it must not be this key. Asserted against
    // the keystore directly so that a future refactor of clearSession cannot
    // pass this test by clearing the module cache instead.
    expect(await SecureStore.getItemAsync('jambo.device.uuid')).not.toBeNull();
    expect(await SecureStore.getItemAsync('jambo.auth.token')).toBeNull();
  });
});

describe('the stored session', () => {
  it('round-trips a token and its user', async () => {
    await writeSession('token-abc', user);

    expect(await readSession()).toEqual({ token: 'token-abc', user });
  });

  it('reads as signed out rather than throwing when the record is corrupt', async () => {
    await writeSession('token-abc', user);
    await SecureStore.setItemAsync('jambo.auth.user', '{not json');

    expect(await readSession()).toBeNull();
  });
});
