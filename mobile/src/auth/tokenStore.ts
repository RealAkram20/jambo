import * as SecureStore from 'expo-secure-store';

import type { components } from '../api/schema';

type SessionUser = NonNullable<components['schemas']['Session']['user']>;

/**
 * The session, in the platform keystore.
 *
 * SecureStore rather than AsyncStorage: this token does not expire on a timer
 * (`docs/api/openapi.yaml` says so explicitly — a TV re-login is a real cost),
 * so it is a long-lived credential sitting on a handset that gets lost, sold
 * and lent. Keystore entries are excluded from device backups; AsyncStorage is
 * a plain file that is not.
 *
 * The keys live under `jambo.auth.` and the device id lives under
 * `jambo.device.` on purpose — see `src/device/deviceId.ts` for why the two
 * must never be cleared together.
 */
const TOKEN_KEY = 'jambo.auth.token';
const USER_KEY = 'jambo.auth.user';

export type StoredSession = { token: string; user: SessionUser };

export async function readSession(): Promise<StoredSession | null> {
  const [token, serialisedUser] = await Promise.all([
    SecureStore.getItemAsync(TOKEN_KEY),
    SecureStore.getItemAsync(USER_KEY),
  ]);

  if (token === null || serialisedUser === null) return null;

  try {
    return { token, user: JSON.parse(serialisedUser) as SessionUser };
  } catch {
    // A half-written or format-changed record. Treated as signed out rather
    // than crashing the launch: signing in again costs a viewer seconds, and a
    // crash loop costs them the app.
    return null;
  }
}

export async function writeSession(token: string, user: SessionUser): Promise<void> {
  await Promise.all([
    SecureStore.setItemAsync(TOKEN_KEY, token),
    SecureStore.setItemAsync(USER_KEY, JSON.stringify(user)),
  ]);
}

/**
 * Clears the credential and nothing else.
 *
 * It must not reach `jambo.device.uuid`. That key is the install's identity
 * rather than the session's, and clearing it here would file a new row in the
 * account's device list on every sign-out. `deviceIdSurvivesSignOut` in
 * `signOut.test.ts` is the test that fails if this grows a third delete.
 */
export async function clearSession(): Promise<void> {
  await Promise.all([
    SecureStore.deleteItemAsync(TOKEN_KEY),
    SecureStore.deleteItemAsync(USER_KEY),
  ]);
}
