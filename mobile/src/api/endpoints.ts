import type { ApiClient } from './client';
import { required } from './errors';
import type { components } from './schema';
import { deviceRegistration } from '../device/deviceId';

export type AppConfig = components['schemas']['AppConfig'];
export type Session = components['schemas']['Session'];
export type Me = components['schemas']['Me'];
export type Device = components['schemas']['Device'];
export type Profile = components['schemas']['Profile'];

/**
 * Every call this slice makes, in one place and typed from the spec.
 *
 * Screens never build a path or a body themselves. That keeps the set of
 * endpoints the app touches enumerable — which is what makes it possible to
 * answer "does the app still work against this server" by reading one file —
 * and it is why `npm run api:check` is worth having: a path renamed in the
 * spec breaks this file at compile time rather than at a viewer's first tap.
 */
export class JamboApi {
  constructor(private readonly client: ApiClient) {}

  /**
   * Called unauthenticated on launch, before anything else.
   *
   * Carries `min_app_version` (the update gate) and `server_time`, which
   * ADR-0003 makes the anchor for offline licence checks in Phase 3. Slice 2a
   * uses the version gate and stores the clock skew for later.
   */
  async appConfig(): Promise<AppConfig> {
    const { data } = await this.client.request<AppConfig>('/app/config');
    return data;
  }

  /**
   * Sign in.
   *
   * Two-factor arrives as a 422 `TWO_FACTOR_REQUIRED` carrying a
   * `challenge_token` alongside the error envelope — not as a success — so the
   * caller catches `ApiError` and reads the code. No token is issued and no
   * device row is created until the challenge is answered.
   */
  async login(email: string, password: string): Promise<Session> {
    const { data } = await this.client.request<Session>('/auth/login', {
      method: 'POST',
      body: { email, password, device: deviceRegistration() },
    });
    return data;
  }

  /**
   * Ask for a password reset link.
   *
   * The server answers identically whether or not the address has an account —
   * echoing the real status would turn this into an account-enumeration
   * oracle — so the screen must show the same message either way. The link
   * lands on the website, not in the app.
   */
  async forgotPassword(email: string): Promise<void> {
    await this.client.request<null>('/auth/forgot-password', {
      method: 'POST',
      body: { email },
    });
  }

  /**
   * Create an account and sign in.
   *
   * Same validation, default role and Registered event as the website, so an
   * account made here is indistinguishable from one made in a browser. The
   * account is usable before the email is verified: the site shows a banner
   * rather than a wall, and so does the app.
   */
  async register(input: {
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<Session> {
    const { data } = await this.client.request<Session>('/auth/register', {
      method: 'POST',
      body: { ...input, device: deviceRegistration() },
    });
    return data;
  }

  /** Answer a two-factor challenge with a TOTP code or a recovery code. */
  async twoFactorChallenge(
    challengeToken: string,
    answer: { code: string } | { recovery_code: string },
  ): Promise<Session> {
    const { data } = await this.client.request<Session>('/auth/2fa/challenge', {
      method: 'POST',
      body: { challenge_token: challengeToken, ...answer },
    });
    return data;
  }

  /**
   * Revoke this device's token server-side.
   *
   * The local session is cleared whatever this does — see `AuthProvider`. A
   * viewer who taps Sign out on a phone with no signal is signed out.
   */
  async logout(): Promise<void> {
    await this.client.request<null>('/auth/logout', { method: 'POST' });
  }

  /** The signed-in viewer: profile, plan, and the caps that gate playback. */
  async me(): Promise<Me> {
    const { data } = await this.client.request<Me>('/me');
    return data;
  }

  /**
   * Everything signed into this account — app installs and browser sessions.
   *
   * `counts_app_devices` reflects the server's `streams.count_app_devices`
   * switch, which ships off. The screen reads it rather than assuming, because
   * whether this phone counts against the viewer's cap is the one question the
   * list is there to answer, and the answer changes when Rio flips it.
   */
  async devices(): Promise<{ devices: Device[]; countsAppDevices: boolean }> {
    const { data } = await this.client.request<{
      devices?: Device[];
      counts_app_devices?: boolean;
    }>('/devices');

    return {
      devices: data.devices ?? [],
      countsAppDevices: data.counts_app_devices ?? false,
    };
  }

  /** Sign one device out. Takes a device uuid or a browser session id. */
  async revokeDevice(id: string): Promise<void> {
    await this.client.request<null>(`/devices/${encodeURIComponent(id)}`, {
      method: 'DELETE',
    });
  }
}

/**
 * The two fields the app genuinely cannot proceed without.
 *
 * The spec declares `required` on its envelopes but not on `Session`, so the
 * generated type makes every field optional. Rather than assert non-null at
 * each call site, the check happens once, here, and names the field it did not
 * get. See `ContractError` in `errors.ts`.
 */
export function sessionCredentials(session: Session): {
  token: string;
  user: NonNullable<Session['user']>;
} {
  return {
    token: required(session.token, 'a session token'),
    user: required(session.user, 'the signed-in user'),
  };
}
