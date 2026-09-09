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
  Account: undefined;
  Devices: undefined;
  ContinueWatching: undefined;
  History: undefined;
  Notifications: undefined;
  Security: undefined;
  Plans: undefined;
  /**
   * How this viewer wants their video delivered — quality, autoplay, and
   * Wi-Fi-only downloads. Kept on the account rather than the handset, so the
   * same answers hold on a second phone and on the television in Phase 4.
   */
  StreamingPreferences: undefined;

  /**
   * A whole home rail, the website's "View all".
   *
   * `rail` is the COLLECTION key, not the rail key — the two key spaces
   * differ (underscored vs hyphenated, plus one pair no rule derives), and
   * `collectionKeyFor` converts. Passing a rail key here would 404.
   */
  Collection: { rail: string; title: string };
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
