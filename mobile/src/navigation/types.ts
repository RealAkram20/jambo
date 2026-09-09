import type { BottomTabScreenProps } from '@react-navigation/bottom-tabs';
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
  /** The tab navigator, as one screen in the stack above it. */
  Tabs: undefined;
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
