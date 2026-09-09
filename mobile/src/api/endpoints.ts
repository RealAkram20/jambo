import type { ApiClient } from './client';
import type {
  ContinueWatchingCard,
  HistoryEntry,
  Home,
  MovieDetail,
  MovieList,
  Notification,
  Plan,
  SecurityState,
  SeriesDetail,
  SeriesList,
  Subscription,
  TitleCard,
  TitleDetail,
} from './catalogue';
import { required } from './errors';
import type { components } from './schema';
import { deviceRegistration } from '../device/deviceId';

export type AppConfig = components['schemas']['AppConfig'];
export type Session = components['schemas']['Session'];
export type Me = components['schemas']['Me'];
export type Device = components['schemas']['Device'];
export type Profile = components['schemas']['Profile'];
export type ReferralDashboard = components['schemas']['ReferralDashboard'];
export type Wallet = components['schemas']['Wallet'];
export type StreamingPreferences = components['schemas']['StreamingPreferences'];

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

  /**
   * One movie.
   *
   * `is_released` says whether the title can be watched yet, which is NOT the
   * same as whether this viewer may watch it — that question is answered by
   * POST /playback/sessions after the entitlement check, and the detail screen
   * must not try to answer it from `tier_required` alone.
   */
  async movie(slug: string): Promise<{ movie: MovieDetail; isReleased: boolean }> {
    const { data } = await this.client.request<{
      movie?: MovieDetail;
      is_released?: boolean;
    }>(`/movies/${encodeURIComponent(slug)}`);

    return { movie: required(data.movie, 'the movie'), isReleased: data.is_released ?? false };
  }

  /** One series, with its seasons and their episodes. */
  async seriesDetail(slug: string): Promise<{ series: SeriesDetail; isReleased: boolean }> {
    const { data } = await this.client.request<{
      series?: SeriesDetail;
      is_released?: boolean;
    }>(`/series/${encodeURIComponent(slug)}`);

    return { series: required(data.series, 'the series'), isReleased: data.is_released ?? false };
  }

  /**
   * A movie or a series, whichever this is, in one shape.
   *
   * The detail screen is one component because the website's two pages are one
   * page with an episode list added, so it wants one call and one return type
   * rather than a union it has to narrow on every field. `seasonsOf()` is what
   * asks the only question that actually differs.
   */
  async titleDetail(
    type: 'movie' | 'series',
    slug: string,
  ): Promise<{ detail: TitleDetail; isReleased: boolean }> {
    if (type === 'movie') {
      const { movie, isReleased } = await this.movie(slug);
      return { detail: movie, isReleased };
    }

    const { series, isReleased } = await this.seriesDetail(slug);
    return { detail: series, isReleased };
  }

  /**
   * Which "see all" archives the server publishes.
   *
   * Fetched rather than assumed, and that is the point: the home screen offers
   * a rail's "View all" only when the key it derived is in this list, so a
   * rail with no archive gets no link instead of a link to a 404, and a
   * collection added server-side lights up its rail with no app release.
   * See `collectionKeyFor` for why the two key spaces need converting at all.
   */
  async collections(): Promise<{ key: string; title: string; type: string }[]> {
    const { data } = await this.client.request<{
      collections?: { key?: string; title?: string; type?: string }[];
    }>('/collections');

    return (data.collections ?? [])
      .filter((c): c is { key: string; title: string; type: string } => typeof c.key === 'string')
      .map((c) => ({ key: c.key, title: c.title ?? '', type: c.type ?? '' }));
  }

  /**
   * One archive: the whole rail rather than its first ten.
   *
   * **Offset paging, not a cursor, and that is the server's decision rather
   * than an oversight.** The endpoint's own note records why: the pinned rails
   * order with a raw `FIELD()` clause a cursor cannot be built from, and the
   * plain ones order on `created_at`, which seeded and bulk-imported titles
   * share to the second — a cursor on it skipped every tied row and page two
   * of latest-movies came back empty on real data. The website pages these by
   * offset too.
   */
  async collection(
    key: string,
    page?: number,
  ): Promise<{ title: string; items: TitleCard[]; nextPage: number | null }> {
    const query = page === undefined ? '' : `?page=${page}`;
    const { data } = await this.client.request<{
      title?: string;
      items?: TitleCard[];
      next_page?: number | null;
    }>(`/collections/${encodeURIComponent(key)}${query}`);

    return {
      title: data.title ?? '',
      items: data.items ?? [],
      nextPage: data.next_page ?? null,
    };
  }

  /**
   * A genre, category, VJ or cast archive.
   *
   * All four answer with the same shape — the entity, then its movies and its
   * series — which is why the app has one archive screen rather than four. The
   * path segment is the only thing that differs, and it is chosen from a fixed
   * map rather than interpolated, so a `kind` the server does not have cannot
   * be built into a URL.
   */
  async taxonomy(
    kind: 'genre' | 'category' | 'vj' | 'cast',
    slug: string,
  ): Promise<{ movies: TitleCard[]; series: TitleCard[]; description: string | null }> {
    const path = { genre: 'genres', category: 'categories', vj: 'vjs', cast: 'cast' }[kind];

    const { data } = await this.client.request<{
      movies?: TitleCard[];
      series?: TitleCard[];
      genre?: { description?: string | null };
      category?: { description?: string | null };
      vj?: { description?: string | null };
      person?: { bio?: string | null };
    }>(`/${path}/${encodeURIComponent(slug)}`);

    const blurb =
      data.genre?.description ??
      data.category?.description ??
      data.vj?.description ??
      data.person?.bio ??
      null;

    return {
      movies: data.movies ?? [],
      series: data.series ?? [],
      description: typeof blurb === 'string' && blurb !== '' ? blurb : null,
    };
  }

  /**
   * Search movies and series by title.
   *
   * Fewer than two characters comes back empty rather than as an error, which
   * is why the screen can call this on every keystroke behind a debounce
   * without special-casing the first letter.
   */
  async search(query: string): Promise<{ movies: TitleCard[]; series: TitleCard[] }> {
    const { data } = await this.client.request<{
      movies?: TitleCard[];
      series?: TitleCard[];
    }>(`/search?q=${encodeURIComponent(query)}`);

    return { movies: data.movies ?? [], series: data.series ?? [] };
  }

  /**
   * Continue Watching as its own screen, rather than as a home rail.
   *
   * Same cards the rail carries. Not paginated by the contract — the row is
   * capped server-side (`WatchHistoryItem` keeps only the most recent N
   * titles, because viewers bail during end credits and in-progress rows would
   * otherwise pile up forever), so there is nothing to page through.
   */
  async continueWatching(): Promise<ContinueWatchingCard[]> {
    const { data } = await this.client.request<{ items?: ContinueWatchingCard[] }>(
      '/continue-watching',
    );
    return data.items ?? [];
  }

  /**
   * Everything watched, newest first.
   *
   * A record rather than a to-do list: unlike Continue Watching it keeps
   * finished titles, which is why it is a separate screen and not a "see all"
   * on the rail.
   */
  async history(cursor?: string): Promise<{ items: HistoryEntry[]; nextCursor: string | null }> {
    const { data } = await this.client.request<{
      items?: HistoryEntry[];
      next_cursor?: string | null;
    }>(cursor === undefined ? '/history' : `/history?cursor=${encodeURIComponent(cursor)}`);

    return { items: data.items ?? [], nextCursor: data.next_cursor ?? null };
  }

  /** The viewer's notifications, newest first, with the unread count. */
  async notifications(): Promise<{ items: Notification[]; unread: number }> {
    const { data } = await this.client.request<{
      items?: Notification[];
      unread_count?: number;
    }>('/notifications');

    return { items: data.items ?? [], unread: data.unread_count ?? 0 };
  }

  /** Mark everything read. One call rather than one per row. */
  async markNotificationsRead(): Promise<void> {
    await this.client.request<null>('/notifications/read-all', { method: 'POST' });
  }

  /**
   * The plans, and this viewer's subscription.
   *
   * Read-only on both build variants for now. ADR-0004 forbids the Play build
   * from showing anything that leads to a payment, and the `direct` build's
   * PesaPal flow does not exist server-side yet — so a Subscribe button would
   * be a policy risk on one variant and a dead control on the other.
   */
  async subscription(): Promise<{ subscription: Subscription | null; plans: Plan[] }> {
    const { data } = await this.client.request<{
      subscription?: Subscription | null;
      plans?: Plan[];
    }>('/subscription');

    return { subscription: data.subscription ?? null, plans: data.plans ?? [] };
  }

  /** Two-factor state, whether Google is linked, and whether the email is verified. */
  async security(): Promise<{
    twoFactor: NonNullable<SecurityState['two_factor']> | null;
    emailVerified: boolean;
    googleEnabled: boolean;
  }> {
    const { data } = await this.client.request<SecurityState>('/account/security');

    return {
      twoFactor: data.two_factor ?? null,
      emailVerified: data.email_verified ?? false,
      googleEnabled: data.google_enabled ?? false,
    };
  }

  /** Send the verification email again. */
  async resendVerificationEmail(): Promise<void> {
    await this.client.request<null>('/auth/email/resend', { method: 'POST' });
  }

  /** Sign one device out. Takes a device uuid or a browser session id. */
  async revokeDevice(id: string): Promise<void> {
    await this.client.request<null>(`/devices/${encodeURIComponent(id)}`, {
      method: 'DELETE',
    });
  }

  /**
   * The profile screen's own payload: the editable fields, the avatar, and
   * whether the email is verified.
   *
   * Separate from `me()` on purpose, and the server says why in as many words:
   * `/me` answers "who am I and what can I watch" on every launch and stays
   * small for that reason. This is the heavier one, fetched by the screens that
   * actually show a face.
   *
   * `avatar_url` is genuinely nullable — most accounts have never uploaded one
   * — so it is returned as `null` rather than defaulted to a placeholder image.
   * A caller that draws something in its place must draw something it derived,
   * such as initials, never a stock photograph of a stranger.
   */
  async profile(): Promise<Profile> {
    const { data } = await this.client.request<{ profile: Profile }>('/profile');

    return data.profile;
  }

  /**
   * Save the viewer's own details.
   *
   * **Not a partial update.** `PATCH /profile` requires `first_name`,
   * `last_name`, `username` and `email` together, so the caller sends all four
   * whichever one changed — sending only the edited field would blank the
   * rest.
   *
   * `phone` is nullable and `null` is how "no phone" is said. An empty string
   * would be stored as a phone number zero characters long.
   */
  async updateProfile(input: {
    first_name: string;
    last_name: string;
    username: string;
    email: string;
    phone: string | null;
  }): Promise<Profile> {
    const { data } = await this.client.request<Profile>('/profile', {
      method: 'PATCH',
      body: input,
    });

    return data;
  }

  /**
   * Refer and earn: the code, the link, and what it has produced.
   *
   * **This endpoint 404s when the referral programme is switched off** — with
   * one exception the spec is explicit about, and it is not a quirk to route
   * around: a viewer who already has wallet history still gets their
   * dashboard, because money someone earned must not become unreachable
   * because an admin changed a setting. A caller must therefore treat a 404 as
   * "the programme is off for you", not as an error worth showing.
   *
   * Whether to offer the screen at all is a separate question and is answered
   * before the fact by `features.referrals` on `/app/config`, which is what
   * the profile drawer reads. This call is for the screen itself.
   */
  async referrals(): Promise<ReferralDashboard> {
    const { data } = await this.client.request<ReferralDashboard>('/referrals');

    return data;
  }

  /**
   * The wallet: balance, the withdrawal floor, and the ledger.
   *
   * **Never gated on the referral programme**, and the website says why in a
   * comment on the same row of its own sidebar: this is the money page. A
   * viewer whose earnings predate the programme being switched off still
   * reaches their balance here.
   *
   * `balance`, `min_withdrawal` and every entry's `amount` are strings on the
   * wire, and they must stay strings all the way to the screen. Parsing them
   * to a JavaScript number is how money loses its last cent to binary
   * floating point, and this is somebody's actual balance.
   */
  async wallet(cursor?: string): Promise<Wallet> {
    const { data } = await this.client.request<Wallet>('/wallet', { query: { cursor } });

    return data;
  }

  /**
   * How this viewer wants their video delivered.
   *
   * Always a complete set — the server fills every key in from its own
   * defaults — so nothing here is optional in practice and no screen has to
   * branch on a missing preference.
   */
  async preferences(): Promise<StreamingPreferences> {
    const { data } = await this.client.request<{ preferences: StreamingPreferences }>(
      '/account/preferences',
    );

    return data.preferences;
  }

  /**
   * Change some of them, and get the whole set back.
   *
   * **Send only what moved.** The endpoint is a PATCH for a reason: a client
   * that resends every field and forgets one silently resets it, which is how
   * a viewer's data saver turns itself off when they toggle autoplay. The
   * response is the complete set, so a caller replaces its state with the
   * answer rather than merging its own guess into it.
   */
  async updatePreferences(
    changes: Partial<StreamingPreferences>,
  ): Promise<StreamingPreferences> {
    const { data } = await this.client.request<{ preferences: StreamingPreferences }>(
      '/account/preferences',
      { method: 'PATCH', body: changes },
    );

    return data.preferences;
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
