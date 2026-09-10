import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
import type { NavigatorScreenParams } from '@react-navigation/native';
import type { NativeStackScreenProps } from '@react-navigation/native-stack';

/**
 * The signed-out stack.
 *
 * `TwoFactor` carries the challenge token as a route parameter rather than
 * holding it in a provider. It is a short-lived, single-use credential that
 * expires in five minutes, and a route parameter dies with the screen — which
 * is the lifetime it should have.
 */
export type AuthStackParams = {
  SignIn: undefined;
  TwoFactor: { challengeToken: string; email: string };
  ForgotPassword: undefined;
  Register: undefined;
};

/**
 * The four tabs, which are the website's four tabs — Home, Movies, Series and
 * Watchlist, exactly as `components/widgets/mobile-footer.blade.php` renders
 * them below 992px.
 */
export type TabParams = {
  Home: undefined;
  Movies: undefined;
  Series: undefined;
  Watchlist: undefined;
};

export type AppStackParams = {
  /**
   * The tab navigator, as one screen in the stack above it.
   *
   * Typed with its nested params rather than `undefined` so a screen in the
   * stack can select a specific tab — `navigate('Tabs', { screen: 'Watchlist' })`.
   * The profile drawer needs exactly that: its Watchlist row is the bottom
   * bar's Watchlist, and sending a viewer to the Home tab instead would be a
   * menu that lies about where it goes.
   */
  Tabs: NavigatorScreenParams<TabParams> | undefined;
  /**
   * A movie or series page. `type` decides which endpoint answers; the screen
   * itself is one component, because the website's two pages are the same page
   * with an episode list added.
   */
  Title: { type: 'movie' | 'series'; slug: string; title?: string };
  /**
   * A genre, category, VJ or cast archive. One route rather than four, for the
   * same reason: the website's archive pages differ by their heading and their
   * source, not by their layout.
   */
  Taxonomy: { kind: 'genre' | 'category' | 'vj' | 'cast'; slug: string; name: string };
  Search: undefined;
  Devices: undefined;
  Notifications: undefined;
  /** The inbox's gear: which channels may reach this viewer. */
  NotificationSettings: undefined;
  Security: undefined;
  /**
   * Changing the account password.
   *
   * The website renders this form inline on its Security page; the app gives
   * it a screen, because it carries its own validation and its own
   * consequence — the server signs every other device out — and both deserve
   * room to be said before the button is pressed.
   */
  ChangePassword: undefined;
  /**
   * Turning two-factor on: secret, authenticator, confirm, recovery codes.
   *
   * A route rather than a section on `Security`, because it is a flow with
   * three steps and a cancel path, and the website's own page is two
   * completely different layouts depending on whether a setup is pending.
   */
  TwoFactorSetup: undefined;
  Plans: undefined;
  /**
   * Payment history — the website's `profile-hub/billing.blade.php`.
   *
   * Reachable from two places on purpose. The website's own Billing page has
   * no sidebar entry at all and is linked only from the payment-complete
   * page, which the app has no equivalent of, so Rio's call on 2026-09-09 was
   * a row in the profile menu AND a row on Membership.
   */
  Billing: undefined;
  /**
   * One invoice.
   *
   * `reference` is the merchant reference, NOT the row id — that is what
   * `GET /subscription/orders/{reference}` takes, and it is the identifier
   * the rest of the payment flow already treats as canonical. The website's
   * own invoice route takes a numeric id instead.
   */
  Invoice: { reference: string };
  /**
   * A whole home rail, the website's "View all".
   *
   * `rail` is the COLLECTION key, not the rail key — the two key spaces
   * differ (underscored vs hyphenated, plus one pair no rule derives), and
   * `collectionKeyFor` converts. Passing a rail key here would 404.
   */
  Collection: { rail: string; title: string };
  /**
   * Every genre, which is the Genres rail's "View all".
   *
   * Not a `Collection`: a collection is a list of TITLES behind one rail, and
   * this is a list of genres. The website makes the same distinction — its
   * rail links to `/all-genres`, not to `/collection/genres`, and there is no
   * `genres` key in `RailArchiveCatalog` at all.
   *
   * `title` is optional so a caller that has no heading to hand still gets a
   * sensible one.
   */
  Genres: { title?: string } | undefined;
  /**
   * The viewer's own profile. Reading and editing are one screen.
   *
   * There was a read-only `Profile` route above this form until 2026-09-10,
   * and it showed a subset of the fields the form already displayed. The
   * audit's section 4.1 deleted it; the menu's Profile row and its identity
   * block both open this.
   */
  ProfileEdit: undefined;
  Referrals: undefined;
  Wallet: undefined;
  /**
   * The profile drawer: the website's profile-hub sidebar, as a panel that
   * slides in over the current screen.
   *
   * A route rather than an overlay, and the reasons are all things an overlay
   * would have had to reinvent: the Android back button dismisses it, its
   * position in the navigation state is how it knows which row to light up,
   * and it reaches `navigate` without being threaded through every screen.
   */
  ProfileMenu: undefined;

  /**
   * The player.
   *
   * `type` and `id`, and NO slug — deliberately, because that is the only
   * identifier a Continue Watching card carries. `POST /playback/sessions`
   * takes exactly this pair, so a card that could not open a detail page can
   * still resume. `title` and `poster` are passed for the chrome so the
   * screen has something to draw before the session comes back; both are
   * optional because a resume from a rail may not know them.
   */
  Watch: {
    type: 'movie' | 'episode';
    id: number;
    title?: string;
    subtitle?: string;
    /** The series slug, when known. Lets the player find the next episode. */
    seriesSlug?: string;
  };
};

export type AuthScreenProps<T extends keyof AuthStackParams> = NativeStackScreenProps<
  AuthStackParams,
  T
>;

export type AppScreenProps<T extends keyof AppStackParams> = NativeStackScreenProps<
  AppStackParams,
  T
>;

export type TabScreenProps<T extends keyof TabParams> = BottomTabScreenProps<TabParams, T>;
