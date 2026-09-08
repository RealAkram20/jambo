import type { ApiClient } from './client';
import type { Home, MovieList, SeriesList, TitleCard } from './catalogue';
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

  /**
   * The home screen.
   *
   * Public, but personalising: with a bearer token Continue Watching appears
   * and the personalised rails become personal, and without one the guest home
   * screen comes back rather than an error. The app sends the token because it
   * only ever calls this signed in, but the endpoint not requiring one is why
   * a dropped session shows a home screen instead of a wall.
   *
   * `rails` is an ordered list, not a fixed object, so a rail added on the
   * server needs no app release. Nothing here reshapes it — the screen renders
   * what it is given, in the order it is given.
   */
  async home(): Promise<Home> {
    const { data } = await this.client.request<Home>('/home');
    return data;
  }

  /**
   * Published movies, newest first, cursor-paginated.
   *
   * The cursor is the server's and is passed back opaquely. The app never
   * builds one: the endpoint's own note records that an offset cursor on
   * `created_at` skipped every tied row on real data, which is the kind of
   * thing a client that invents pagination reproduces.
   */
  async movies(cursor?: string): Promise<MovieList> {
    const { data } = await this.client.request<MovieList>(
      cursor === undefined ? '/movies' : `/movies?cursor=${encodeURIComponent(cursor)}`,
    );
    return data;
  }

  /** Published series, newest first, cursor-paginated. */
  async series(cursor?: string): Promise<SeriesList> {
    const { data } = await this.client.request<SeriesList>(
      cursor === undefined ? '/series' : `/series?cursor=${encodeURIComponent(cursor)}`,
    );
    return data;
  }

  /**
   * The viewer's watchlist.
   *
   * Not paginated by the contract, and deliberately not paged here either —
   * inventing a `?cursor=` the server ignores would look like it worked until
   * somebody had more than a page of titles.
   */
  async watchlist(): Promise<{ items: TitleCard[] }> {
    const { data } = await this.client.request<{ items?: TitleCard[] }>('/watchlist');
    return { items: data.items ?? [] };
  }

  /**
   * Put a title on the watchlist.
   *
   * Idempotent by design on the server: adding something already on the list
   * succeeds and changes nothing, so a retry after a dropped connection is
   * safe. That is why this is not the website's toggle — a retried toggle
   * silently undoes itself, which over a mobile connection is a bug a viewer
   * cannot explain.
   */
  async addToWatchlist(type: 'movie' | 'show' | 'episode', id: number): Promise<boolean> {
    const { data } = await this.client.request<{ in_list?: boolean }>('/watchlist', {
      method: 'POST',
      body: { type, id },
    });
    return data.in_list ?? true;
  }

  /** Take a title off the watchlist. Idempotent: removing what is not there is a success. */
  async removeFromWatchlist(type: 'movie' | 'show' | 'episode', id: number): Promise<boolean> {
    const { data } = await this.client.request<{ in_list?: boolean }>(
      `/watchlist/${type}/${id}`,
      { method: 'DELETE' },
    );
    return data.in_list ?? false;
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
