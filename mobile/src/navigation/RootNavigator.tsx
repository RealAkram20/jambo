import { DarkTheme, NavigationContainer, type Theme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';


import { useAuth } from '../auth/AuthProvider';
import { colors, fonts, typography } from '../ui/theme';
import { Preloader } from '../ui/components';
import { ProfileMenuScreen } from '../screens/ProfileMenuScreen';
import { DevicesScreen } from '../features/devices/DevicesScreen';
import { ForgotPasswordScreen } from '../screens/ForgotPasswordScreen';
import { RegisterScreen } from '../screens/RegisterScreen';
import { SignInScreen } from '../screens/SignInScreen';
import { TwoFactorScreen } from '../screens/TwoFactorScreen';
import { UpdateRequiredScreen } from '../screens/UpdateRequiredScreen';
import { NotificationsScreen } from '../features/notifications/NotificationsScreen';
import { NotificationSettingsScreen } from '../features/notifications/NotificationSettingsScreen';
import { CollectionScreen } from '../screens/CollectionScreen';
import { GenresScreen } from '../screens/GenresScreen';
import { PlansScreen } from '../screens/PlansScreen';
import { ProfileEditScreen } from '../screens/ProfileEditScreen';
import { ReferralsScreen } from '../features/referrals/ReferralsScreen';
import { WalletScreen } from '../features/wallet/WalletScreen';
import { SearchScreen } from '../screens/SearchScreen';
import { SecurityScreen } from '../screens/SecurityScreen';
import { TaxonomyScreen } from '../screens/TaxonomyScreen';
import { TitleDetailScreen } from '../screens/TitleDetailScreen';
import { WatchScreen } from '../screens/WatchScreen';
import { BillingScreen } from '../features/billing/BillingScreen';
import { InvoiceScreen } from '../features/billing/InvoiceScreen';
import { ChangePasswordScreen } from '../features/security/ChangePasswordScreen';
import { TwoFactorSetupScreen } from '../features/security/TwoFactorSetupScreen';
import { TabNavigator } from './TabNavigator';
import type { AppStackParams, AuthStackParams } from './types';

const AuthStack = createNativeStackNavigator<AuthStackParams>();
const AppStack = createNativeStackNavigator<AppStackParams>();

/**
 * React Navigation's own theme, pointed at the site's colours.
 *
 * Without this the navigator paints its own near-black between screens, which
 * shows as a flash of the wrong black during every push on a slower handset.
 */
const navigationTheme: Theme = {
  ...DarkTheme,
  colors: {
    ...DarkTheme.colors,
    primary: colors.primary,
    background: colors.background,
    card: colors.background,
    text: colors.text,
    border: colors.divider,
  },
};

const screenOptions = {
  headerStyle: { backgroundColor: colors.background },
  headerTintColor: colors.text,
  headerTitleStyle: {
    fontFamily: fonts.medium,
    fontSize: typography.heading.fontSize,
  },
  headerShadowVisible: false,
  contentStyle: { backgroundColor: colors.background },
} as const;

export function RootNavigator() {
  const { phase, updateRequired } = useAuth();

  /*
   * The update gate sits outside both stacks and outranks the session.
   *
   * A build below the server's floor is refused whether or not somebody is
   * signed in — the point of the gate is that this version can no longer be
   * trusted to talk to this server correctly, and being signed in makes that
   * worse rather than better.
   */
  if (updateRequired) {
    return (
      <NavigationContainer theme={navigationTheme}>
        <UpdateRequiredScreen />
      </NavigationContainer>
    );
  }

  if (phase === 'starting') {
    return <Preloader label="Starting Jambo" />;
  }

  return (
    <NavigationContainer theme={navigationTheme}>
      {phase === 'signedIn' ? (
        <AppStack.Navigator screenOptions={screenOptions}>
          {/*
            The tabs are the app's home, and they carry their own headers. The
            stack above them exists for the screens that push over the tabs —
            Devices, the account screens, and the detail screens they push.
          */}
          <AppStack.Screen
            name="Tabs"
            component={TabNavigator}
            options={{ headerShown: false }}
          />
          <AppStack.Screen
            name="Title"
            component={TitleDetailScreen}
            // The backdrop runs under the status bar, so the header floats over
            // it rather than sitting on a bar of its own. `title` comes from the
            // route so the back affordance names what you are leaving.
            options={({ route }) => ({
              headerTransparent: true,
              headerTitle: '',
              headerBackButtonDisplayMode: 'minimal',
              title: route.params.title ?? '',
            })}
          />
          <AppStack.Screen
            name="Taxonomy"
            component={TaxonomyScreen}
            options={({ route }) => ({ title: route.params.name })}
          />
          <AppStack.Screen
            name="Search"
            component={SearchScreen}
            options={{ title: 'Search' }}
          />
          <AppStack.Screen
            name="Devices"
            component={DevicesScreen}
            options={{ title: 'Devices' }}
          />
          {/*
            **The header action is set by the screen, not here.**

            It used to be a gear defined on this route. The screen now owns an
            overflow that opens "Mark all read", "Delete all" and Settings in
            one sheet, and only this route knows which of those have anything
            to do — the unread count and the row count live in the screen's
            own query. A static option here could not hide a button for an
            inbox that is already empty.

            The title stays where the stack puts it on each platform: Rio's
            mockup centres it because it is drawn in an iPhone frame, and
            overriding `headerTitleAlign` for this one screen would make it the
            only screen in the app whose header sits differently from all the
            others.
          */}
          <AppStack.Screen
            name="Notifications"
            component={NotificationsScreen}
            options={{ title: 'Notifications' }}
          />
          <AppStack.Screen
            name="NotificationSettings"
            component={NotificationSettingsScreen}
            options={{ title: 'Notification settings' }}
          />
          <AppStack.Screen
            name="Security"
            component={SecurityScreen}
            options={{ title: 'Security' }}
          />
          {/*
            The route is `Plans` and the header is Membership, and the two
            differ on purpose: the profile menu's Membership row already points
            at this route, and renaming it would ripple through another
            session's files to no end. What a viewer reads is what the website
            calls the page.
          */}
          <AppStack.Screen
            name="Plans"
            component={PlansScreen}
            options={{ title: 'Membership' }}
          />
          {/*
            Password and two-factor enrolment. Both hang off Security, which is
            where the website keeps them: `profile-hub/security.blade.php` is
            one page carrying the password form and the 2FA card together.
          */}
          <AppStack.Screen
            name="ChangePassword"
            component={ChangePasswordScreen}
            options={{ title: 'Change password' }}
          />
          <AppStack.Screen
            name="TwoFactorSetup"
            component={TwoFactorSetupScreen}
            options={{ title: 'Two-factor' }}
          />
          {/*
            Billing and the invoice behind it. "Membership" is the header the
            profile menu's Membership row leads to, so the ladder screen keeps
            its route name and these sit beside it.
          */}
          <AppStack.Screen
            name="Billing"
            component={BillingScreen}
            options={{ title: 'Billing' }}
          />
          <AppStack.Screen
            name="Invoice"
            component={InvoiceScreen}
            options={{ title: 'Invoice' }}
          />
          <AppStack.Screen
            name="Collection"
            component={CollectionScreen}
            // The rail's own heading, so "View all" lands on a screen titled
            // the same thing the viewer just tapped under.
            options={({ route }) => ({ title: route.params.title })}
          />
          <AppStack.Screen
            name="Genres"
            component={GenresScreen}
            options={({ route }) => ({ title: route.params?.title ?? 'Genres' })}
          />
          {/*
            The profile. One screen, reading and editing together.

            There were two until 2026-09-10 — a read-only `Profile` above this
            form — and the read-only one showed a subset of the fields the form
            already displayed. The audit's §4.1 deleted it. The title is
            "Profile" rather than the form's old "Your details" because this is
            now the destination the menu's Profile row and its identity block
            both open.
          */}
          <AppStack.Screen
            name="ProfileEdit"
            component={ProfileEditScreen}
            options={{ title: 'Profile' }}
          />
          <AppStack.Screen
            name="Referrals"
            component={ReferralsScreen}
            options={{ title: 'Refer & Earn' }}
          />
          <AppStack.Screen name="Wallet" component={WalletScreen} options={{ title: 'Wallet' }} />

          {/*
            The profile menu. A screen of its own, by Rio's correction on
            2026-09-09 — it was first built as a drawer sliding in over the
            home screen and that was wrong: there is nothing behind it worth
            seeing through to.

            `presentation: 'modal'` because the mockup closes with an X rather
            than a back arrow, and a modal is the shape that gesture belongs
            to. Its own header is hidden: the screen draws the Jambo wordmark
            and that X itself, which is what the mockup shows.
          */}
          <AppStack.Screen
            name="ProfileMenu"
            component={ProfileMenuScreen}
            options={{ headerShown: false, presentation: 'modal' }}
          />

          {/*
            The player.

            `orientation: 'landscape'` is set HERE rather than by a library
            call inside the screen. react-native-screens applies it to the
            Activity for as long as this route is on the stack and unwinds it
            on the way out, so there is no lock to release by hand and no new
            native dependency. It matters because a phone with rotation lock
            on would otherwise hold the player in portrait and show a
            letterboxed strip.

            No header and no back gesture chrome: the player draws its own
            controls, and a navigation bar over a film is the one place the
            site's design has nothing to say because the website is fullscreen
            there too.
          */}
          <AppStack.Screen
            name="Watch"
            component={WatchScreen}
            options={{
              headerShown: false,
              orientation: 'landscape',
              animation: 'fade',
              // The status and navigation bars stay out of the way. A film
              // with the Android clock over the top of it is not fullscreen.
              navigationBarHidden: true,
              statusBarHidden: true,
            }}
          />
        </AppStack.Navigator>
      ) : (
        <AuthStack.Navigator screenOptions={screenOptions}>
          <AuthStack.Screen
            name="SignIn"
            component={SignInScreen}
            options={{ headerShown: false }}
          />
          <AuthStack.Screen
            name="ForgotPassword"
            component={ForgotPasswordScreen}
            options={{ headerShown: false }}
          />
          <AuthStack.Screen
            name="Register"
            component={RegisterScreen}
            options={{ headerShown: false }}
          />
          <AuthStack.Screen
            name="TwoFactor"
            component={TwoFactorScreen}
            options={{ title: 'Two-factor' }}
          />
        </AuthStack.Navigator>
      )}
    </NavigationContainer>
  );
}
