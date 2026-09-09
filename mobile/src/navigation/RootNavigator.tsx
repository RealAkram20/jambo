import { DarkTheme, NavigationContainer, type Theme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';

import { useAuth } from '../auth/AuthProvider';
import { colors, fonts, typography } from '../ui/theme';
import { Loading } from '../ui/components';
import { AccountScreen } from '../screens/AccountScreen';
import { ProfileMenuScreen } from '../screens/ProfileMenuScreen';
import { DevicesScreen } from '../screens/DevicesScreen';
import { ForgotPasswordScreen } from '../screens/ForgotPasswordScreen';
import { RegisterScreen } from '../screens/RegisterScreen';
import { SignInScreen } from '../screens/SignInScreen';
import { TwoFactorScreen } from '../screens/TwoFactorScreen';
import { UpdateRequiredScreen } from '../screens/UpdateRequiredScreen';
import { ContinueWatchingScreen } from '../screens/ContinueWatchingScreen';
import { HistoryScreen } from '../screens/HistoryScreen';
import { NotificationsScreen } from '../screens/NotificationsScreen';
import { CollectionScreen } from '../screens/CollectionScreen';
import { PlansScreen } from '../screens/PlansScreen';
import { ProfileEditScreen } from '../screens/ProfileEditScreen';
import { ReferralsScreen } from '../screens/ReferralsScreen';
import { WalletScreen } from '../screens/WalletScreen';
import { SearchScreen } from '../screens/SearchScreen';
import { SecurityScreen } from '../screens/SecurityScreen';
import { StreamingPreferencesScreen } from '../screens/StreamingPreferencesScreen';
import { TaxonomyScreen } from '../screens/TaxonomyScreen';
import { TitleDetailScreen } from '../screens/TitleDetailScreen';
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
    return <Loading label="Starting Jambo" />;
  }

  return (
    <NavigationContainer theme={navigationTheme}>
      {phase === 'signedIn' ? (
        <AppStack.Navigator screenOptions={screenOptions}>
          {/*
            The tabs are the app's home, and they carry their own headers. The
            stack above them exists for the screens that push over the tabs —
            Account, Devices, and the detail screens that follow in this slice.
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
            name="Account"
            component={AccountScreen}
            options={{ title: 'Account' }}
          />
          <AppStack.Screen
            name="Devices"
            component={DevicesScreen}
            options={{ title: 'Devices' }}
          />
          <AppStack.Screen
            name="ContinueWatching"
            component={ContinueWatchingScreen}
            options={{ title: 'Continue Watching' }}
          />
          <AppStack.Screen
            name="History"
            component={HistoryScreen}
            options={{ title: 'History' }}
          />
          <AppStack.Screen
            name="Notifications"
            component={NotificationsScreen}
            options={{ title: 'Notifications' }}
          />
          <AppStack.Screen
            name="Security"
            component={SecurityScreen}
            options={{ title: 'Security' }}
          />
          <AppStack.Screen name="Plans" component={PlansScreen} options={{ title: 'Plans' }} />
          <AppStack.Screen
            name="StreamingPreferences"
            component={StreamingPreferencesScreen}
            // "Streaming" rather than "Streaming preferences": the header has
            // one line at a phone width and the longer title truncates on a
            // Tecno-class screen, which is the device this is for.
            options={{ title: 'Streaming' }}
          />
          <AppStack.Screen
            name="Collection"
            component={CollectionScreen}
            // The rail's own heading, so "View all" lands on a screen titled
            // the same thing the viewer just tapped under.
            options={({ route }) => ({ title: route.params.title })}
          />
          <AppStack.Screen
            name="ProfileEdit"
            component={ProfileEditScreen}
            options={{ title: 'Your details' }}
          />
          <AppStack.Screen
            name="Referrals"
            component={ReferralsScreen}
            options={{ title: 'Refer and Earn' }}
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
