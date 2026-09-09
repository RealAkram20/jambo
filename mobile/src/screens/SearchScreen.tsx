import { useEffect, useState } from 'react';
import { StyleSheet, TextInput, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { MagnifyingGlass } from 'phosphor-react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '../api/jambo';
import type { TitleCard } from '../api/catalogue';
import { colors, radius, spacing, typography } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { EmptyState, ErrorState, Spinner } from '../ui/components';
import { PosterGrid } from '../ui/rails/PosterGrid';
import type { AppScreenProps } from '../navigation/types';

/** How long to wait after the last keystroke before asking the server. */
const DEBOUNCE_MS = 300;

/**
 * Search.
 *
 * Debounced rather than fired per keystroke: every character typed is not a
 * request, and on a mobile connection the difference is the whole feel of the
 * screen. 300ms is under the threshold where typing feels laggy and well above
 * the rate at which a fast typist would queue requests.
 *
 * The server returns empty lists for fewer than two characters rather than an
 * error, so there is no special case for the first letter — the screen just
 * shows its resting state until something comes back.
 *
 * **The input is a plain `TextInput`, not the `AuthField` from the auth kit.**
 * That kit is the glassy mockup treatment and is deliberately quarantined to
 * the three auth screens; this is the ordinary design system.
 */
export function SearchScreen({ navigation }: AppScreenProps<'Search'>) {
  const metrics = useRailMetrics();
  const [text, setText] = useState('');
  const [query, setQuery] = useState('');
  const [focused, setFocused] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => setQuery(text.trim()), DEBOUNCE_MS);
    return () => clearTimeout(timer);
  }, [text]);

  const { data, isFetching, isError, error, refetch } = useQuery({
    queryKey: ['search', query],
    queryFn: () => api.search(query),
    // Below two characters the server answers empty anyway; not asking saves a
    // round trip on every single-letter pause.
    enabled: query.length >= 2,
  });

  const items: TitleCard[] = data ? [...data.movies, ...data.series] : [];

  return (
    <SafeAreaView style={styles.screen} edges={['top']}>
      <View style={styles.searchRow}>
        <View style={[styles.field, focused && styles.fieldFocused]}>
          <MagnifyingGlass size={20} color={colors.placeholder} weight="regular" />
          <TextInput
            accessibilityLabel="Search films and series"
            value={text}
            onChangeText={setText}
            placeholder="Search films and series"
            placeholderTextColor={colors.placeholder}
            autoCapitalize="none"
            autoCorrect={false}
            returnKeyType="search"
            autoFocus
            onFocus={() => setFocused(true)}
            onBlur={() => setFocused(false)}
            style={styles.input}
          />
          {/*
            The spinner sits inside the field rather than replacing the results.
            Swapping a list of results for a spinner on every keystroke makes
            the screen flash; leaving the last results up while the next ones
            load is what a search field is expected to do.
          */}
          {isFetching ? <Spinner /> : null}
        </View>
      </View>

      {isError ? (
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : 'Search is unavailable right now.'
          }
          onRetry={() => {
            void refetch();
          }}
        />
      ) : query.length < 2 ? (
        <EmptyState
          title="Find something to watch"
          detail="Type at least two letters to search the catalogue by title."
        />
      ) : (
        <PosterGrid
          items={items}
          metrics={metrics}
          onPressItem={(item) => {
            if (item.slug === undefined) return;
            navigation.push('Title', {
              type: item.type === 'series' ? 'series' : 'movie',
              slug: item.slug,
              ...(item.title === undefined ? {} : { title: item.title }),
            });
          }}
          emptyTitle={`Nothing matches “${query}”`}
          emptyDetail="Check the spelling, or try part of the title instead of the whole thing."
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  searchRow: { paddingHorizontal: spacing.lg, paddingBottom: spacing.lg },
  field: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    height: 44,
    paddingHorizontal: spacing.md,
    borderRadius: radius.input,
    backgroundColor: colors.inputBg,
    borderWidth: 1,
    borderColor: 'transparent',
  },
  // The site's focused control takes the primary colour on its border. Border
  // only — never elevation on a View containing a TextInput. See
  // src/ui/authComponents.test.ts for what that costs.
  fieldFocused: { borderColor: colors.borderFocus, backgroundColor: colors.inputBgFocus },
  input: { flex: 1, ...typography.field, color: colors.fieldText, padding: 0 },
});
