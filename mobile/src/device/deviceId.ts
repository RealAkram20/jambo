import * as Crypto from 'expo-crypto';
import * as Device from 'expo-device';
import * as SecureStore from 'expo-secure-store';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

import type { components } from '../api/schema';

/**
 * The install's identity, and it is not the session.
 *
 * Deliberately stored under its own key, in its own module, away from
 * `src/auth/tokenStore.ts`. The distinction is the whole point of this file:
 *
 * - A **token** is who is signed in. Sign out and it is gone.
 * - A **device id** is which install this is. It outlives every sign-out and
 *   changes only when the app is uninstalled.
 *
 * Getting that backwards is the easiest mistake available here and it is
 * invisible in testing. A uuid regenerated on sign-out would file a brand-new
 * row in `devices` on every login: the account's device list fills with
 * ghosts, the viewer sees a list they cannot recognise or clean up, and
 * `active_streams` — which keys on this uuid as the client session key — stops
 * counting the same handset as the same handset, so the concurrent-stream cap
 * silently stops working. `clearSession()` must never touch this key, and
 * there is a test that fails if it starts to.
 */
const DEVICE_ID_KEY = 'jambo.device.uuid';

let cached: string | null = null;

/**
 * Resolves the device id, minting one on first launch.
 *
 * Must be awaited before the first request: `ApiClient` throws rather than
 * send a request without `X-Device-Id`, because a request missing that header
 * is rate-limited against the carrier's address rather than this handset.
 */
export async function ensureDeviceId(): Promise<string> {
  if (cached !== null) return cached;

  const stored = await SecureStore.getItemAsync(DEVICE_ID_KEY);
  if (stored !== null && stored !== '') {
    cached = stored;
    return stored;
  }

  const minted = Crypto.randomUUID();
  await SecureStore.setItemAsync(DEVICE_ID_KEY, minted);
  cached = minted;
  return minted;
}

/** The resolved device id, or null before `ensureDeviceId()` has run. */
export function deviceId(): string | null {
  return cached;
}

/** Test seam. Not called by the app. */
export function resetDeviceIdCache(): void {
  cached = null;
}

export type DeviceRegistration = components['schemas']['DeviceRegistration'];

/**
 * What `POST /auth/login` is told about this handset.
 *
 * `platform` comes from `Platform.isTV` rather than from `expo-device`'s
 * device type, because it is the flag the TV build actually turns on — the
 * same one `EXPO_TV=1` sets — so a phone build and a TV build cannot disagree
 * with what was compiled.
 *
 * `name` is the phone's own name where the platform will give it, because it
 * is what makes the website's device list readable: "Rio's A54" is something a
 * viewer can recognise and boot, and "SM-A546E" is not.
 */
export function deviceRegistration(): DeviceRegistration {
  const registration: DeviceRegistration = {
    uuid: cached ?? '',
    platform: Platform.isTV ? 'android_tv' : 'android',
  };

  const name = Device.deviceName;
  if (name) registration.name = name.slice(0, 120);

  const model = Device.modelName;
  if (model) registration.model = model.slice(0, 120);

  const version = Constants.expoConfig?.version;
  if (version) registration.app_version = version.slice(0, 32);

  return registration;
}
