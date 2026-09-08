import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { View, StyleSheet } from 'react-native';

import { AuthProvider } from './src/auth/AuthProvider';
import { RootNavigator } from './src/navigation/RootNavigator';
import { useBrandFonts } from './src/ui/fonts';
import { colors } from './src/ui/theme';

/**
 * Query defaults, chosen for a Ugandan mobile connection rather than a desk.
 *
 * `retry: 2` because a single dropped request on a handover is normal and
 * showing an error for it trains viewers to distrust the app. The backoff is
 * capped so a genuinely dead connection still reaches its error state inside a
 * few seconds rather than leaving somebody watching a spinner.
 *
 * Nothing is persisted to disk. Query results here include an email address
 * and a subscription, and where account data is allowed to rest is a decision
 * that belongs with Phase 3's encrypted cache — not one to make by accident in
 * the slice that happened to install the persister.
 */
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 2,
      retryDelay: (attempt) => Math.min(1000 * 2 ** attempt, 4000),
      staleTime: 30_000,
    },
  },
});

export default function App() {
  const fontsReady = useBrandFonts();

  if (!fontsReady) {
    // The site's black, not a white flash. This is the first frame the app
    // ever draws and on a slower handset it is on screen long enough to see.
    return <View style={styles.blank} />;
  }

  return (
    <SafeAreaProvider>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          {/* Icon style only. There is no colour to set here: SDK 57 draws
              Android edge-to-edge, so the status bar is transparent and takes
              whatever this screen paints behind it. */}
          <StatusBar style="light" />
          <RootNavigator />
        </AuthProvider>
      </QueryClientProvider>
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  blank: { flex: 1, backgroundColor: colors.background },
});
