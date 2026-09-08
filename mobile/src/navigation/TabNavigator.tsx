import { Platform, StyleSheet, View } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { BookmarkSimple, FilmStrip, House, Television } from 'phosphor-react-native';

import { colors, fonts, tabBar } from '../ui/theme';
import { HomeScreen } from '../screens/HomeScreen';
import { MoviesScreen } from '../screens/MoviesScreen';
import { SeriesScreen } from '../screens/SeriesScreen';
import { WatchlistScreen } from '../screens/WatchlistScreen';
import type { TabParams } from './types';

const Tabs = createBottomTabNavigator<TabParams>();

/**
 * The app's bottom navigation, which is the website's bottom navigation.
 *
 * `components/widgets/mobile-footer.blade.php` already renders a fixed bar
 * below 992px with exactly these four destinations, these labels and these
 * Phosphor icons — regular when idle, filled when active. The app ports it
 * rather than inventing a navigation shape, which is the first rule of this
 * phase: the UI is the website's UI.
 *
 * Search, notifications and the account live in the header on the site, not in
 * this bar, and they do the same here. Promoting them to tabs would be a
 * navigation the website does not have.
 *
 * Every tab is focusable by default under React Navigation, and the labels are
 * real text rather than icons alone — a remote user and a screen-reader user
 * both need to know which of four identical-sized targets they are on.
 */
export function TabNavigator() {
  return (
    <Tabs.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: tabBar.active,
        tabBarInactiveTintColor: tabBar.idle,
        tabBarStyle: styles.bar,
        tabBarLabelStyle: styles.label,
        tabBarItemStyle: styles.item,
        // The site's bar is translucent charcoal over the page. A solid
        // background here would be a different design; the blur it uses is
        // native-only and not worth a dependency for a bar this dark.
        tabBarBackground: () => (
          <View style={[StyleSheet.absoluteFill, styles.barBackground]} />
        ),
        sceneStyle: { backgroundColor: colors.background },
      }}
    >
      <Tabs.Screen
        name="Home"
        component={HomeScreen}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({ color, focused }) => (
            <House size={tabBar.iconSize} color={color} weight={focused ? 'fill' : 'regular'} />
          ),
        }}
      />
      <Tabs.Screen
        name="Movies"
        component={MoviesScreen}
        options={{
          tabBarLabel: 'Movies',
          tabBarIcon: ({ color, focused }) => (
            <FilmStrip size={tabBar.iconSize} color={color} weight={focused ? 'fill' : 'regular'} />
          ),
        }}
      />
      <Tabs.Screen
        name="Series"
        component={SeriesScreen}
        options={{
          tabBarLabel: 'Series',
          tabBarIcon: ({ color, focused }) => (
            <Television size={tabBar.iconSize} color={color} weight={focused ? 'fill' : 'regular'} />
          ),
        }}
      />
      <Tabs.Screen
        name="Watchlist"
        component={WatchlistScreen}
        options={{
          tabBarLabel: 'Watchlist',
          tabBarIcon: ({ color, focused }) => (
            <BookmarkSimple
              size={tabBar.iconSize}
              color={color}
              weight={focused ? 'fill' : 'regular'}
            />
          ),
        }}
      />
    </Tabs.Navigator>
  );
}

const styles = StyleSheet.create({
  /*
   * Background and border only.
   *
   * Height, and the padding that keeps the bar clear of the gesture handle,
   * are React Navigation's to compute from the safe-area inset. The first cut
   * set `paddingTop` and `height: undefined` here and the result on the
   * emulator was a bar cut in half by the system navigation — icons sliced
   * through the middle, labels gone entirely. The site's own bar does the same
   * thing in CSS (`padding-bottom: calc(0.375rem + env(safe-area-inset-bottom))`);
   * this is the platform's version of that `env()`.
   */
  bar: {
    backgroundColor: 'transparent',
    borderTopColor: tabBar.border,
    borderTopWidth: StyleSheet.hairlineWidth,
    elevation: 0,
  },
  barBackground: { backgroundColor: tabBar.background },
  item: { paddingTop: tabBar.padding },
  label: {
    fontFamily: fonts.medium,
    fontSize: tabBar.labelSize,
    // The site sets `letter-spacing: 0.01em` on the label.
    letterSpacing: 0.11,
    // Android clips a descender at this size without a little headroom.
    paddingBottom: Platform.OS === 'android' ? 2 : 0,
  },
});
