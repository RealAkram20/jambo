import { useMemo, useState } from 'react';
import { FlatList, Modal, StyleSheet, Text, TextInput, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { Check, MagnifyingGlass, X } from 'phosphor-react-native';

import { api } from '../api/jambo';
import { NetworkError } from '../api/errors';
import { Focusable } from './rails/Focusable';
import { ErrorState, Loading } from './components';
import { ListRow } from './list';
import { colors, fonts, profileMenu, radius, spacing, typography } from './theme';
import type { Country } from '../api/endpoints';

/**
 * Choosing a country.
 *
 * A shared component from the start rather than a block inside the profile
 * form, because there is already a second caller coming: Rio wants phone and
 * contact details collected at registration too, and a second copy of a
 * 249-row searchable list is exactly the duplication the project's rules
 * forbid.
 *
 * **The list comes from the server, not from the app.** One source of truth
 * for the codes and the names, so the picker can never offer a country the
 * validator then refuses — which is the failure a bundled copy drifts into,
 * and which shows up as a viewer choosing Uganda and being told it is invalid.
 *
 * The list is a modal rather than an inline dropdown for a reason a phone
 * makes obvious: 249 rows inside a scrolling form means two nested scrollers,
 * and the one the thumb grabs is a coin toss.
 */
export function CountryPicker({
  visible,
  selected,
  onSelect,
  onClose,
}: {
  visible: boolean;
  /** The currently chosen ISO code, or null. */
  selected: string | null;
  onSelect: (code: string, name: string) => void;
  onClose: () => void;
}) {
  const [query, setQuery] = useState('');

  /*
   * Fetched once and cached for the session. The ISO country list changes
   * about once a decade, so re-fetching it on every open would be a request
   * that can only ever return the same thing.
   */
  const list = useQuery({
    queryKey: ['countries'],
    queryFn: () => api.countries(),
    staleTime: Infinity,
    enabled: visible,
  });

  const suggestedCount = list.data?.suggested.length ?? 0;

  /*
   * `?? []` inside the memo, not above it. A fresh array literal on every
   * render is a new dependency every render, so the filter would re-run over
   * 249 rows on every keystroke in the form behind this sheet.
   */
  const filtered = useMemo(() => {
    const countries = list.data?.countries ?? [];
    const term = query.trim().toLowerCase();

    if (term === '') return countries;

    /*
     * Matches on the name and on the code, because people type both — "UG"
     * and "Uganda" are the same intent. `startsWith` on the code and
     * `includes` on the name: a two-letter `includes` would put every country
     * containing those letters above the one the viewer actually typed.
     */
    return countries.filter(
      (c) => c.name.toLowerCase().includes(term) || c.code.toLowerCase().startsWith(term),
    );
  }, [list.data?.countries, query]);

  /* The divider under the suggested block only makes sense in the unfiltered
     list — once somebody searches, "nearby" is not what they are looking at. */
  const showDivider = query.trim() === '' && suggestedCount > 0;

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={false}
      onRequestClose={onClose}
      // Android's back button closes the picker rather than the screen behind
      // it, which is what `onRequestClose` is for and what a viewer expects.
      statusBarTranslucent={false}
    >
      <View style={styles.sheet}>
        <View style={styles.header}>
          <Text style={styles.title}>Country</Text>
          <Focusable accessibilityLabel="Close" ringRadius={20} onPress={onClose} style={styles.close}>
            <X size={22} color={colors.text} weight="bold" />
          </Focusable>
        </View>

        <View style={styles.search}>
          <MagnifyingGlass size={18} color={colors.placeholder} weight="regular" />
          <TextInput
            value={query}
            onChangeText={setQuery}
            placeholder="Search countries"
            placeholderTextColor={colors.placeholder}
            style={styles.searchInput}
            autoCorrect={false}
            autoCapitalize="none"
            // A label, not just a placeholder — a placeholder disappears the
            // moment somebody types and a screen reader never sees it.
            accessibilityLabel="Search countries"
            returnKeyType="search"
          />
        </View>

        {list.isPending ? (
          <Loading label="Loading countries" />
        ) : list.isError ? (
          <View style={styles.state}>
            <ErrorState
              message={
                list.error instanceof NetworkError
                  ? 'Could not reach Jambo. Check your connection.'
                  : 'We could not load the country list.'
              }
              onRetry={() => void list.refetch()}
            />
          </View>
        ) : (
          <FlatList
            data={filtered}
            keyExtractor={(item: Country) => item.code}
            keyboardShouldPersistTaps="handled"
            contentContainerStyle={styles.listContent}
            ListEmptyComponent={
              <View style={styles.state}>
                <Text style={styles.empty}>No country matches “{query.trim()}”.</Text>
              </View>
            }
            renderItem={({ item, index }) => (
              <>
                {/*
                  The same row the rest of the app draws. A picker with its own
                  row height was one of the four different rhythms Rio asked to
                  be made uniform.
                */}
                <ListRow
                  label={item.name}
                  onPress={() => onSelect(item.code, item.name)}
                  accessory={
                    selected === item.code ? (
                      <Check size={18} color={colors.primary} weight="bold" />
                    ) : undefined
                  }
                  last
                />

                {showDivider && index === suggestedCount - 1 ? (
                  <View style={styles.divider} />
                ) : null}
              </>
            )}
          />
        )}
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  sheet: { flex: 1, backgroundColor: profileMenu.background },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.xl,
    paddingBottom: spacing.md,
  },
  title: { fontFamily: fonts.bold, fontSize: 20, lineHeight: 26, color: colors.fieldText },
  close: { padding: spacing.sm },

  search: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginHorizontal: spacing.lg,
    paddingHorizontal: spacing.md,
    height: 46,
    borderRadius: radius.input,
    backgroundColor: colors.inputBg,
    borderWidth: StyleSheet.hairlineWidth,
    borderColor: colors.border,
  },
  searchInput: { ...typography.field, flex: 1, color: colors.fieldText, padding: 0 },

  listContent: { paddingVertical: spacing.sm },

  /* Separates the East African block from the alphabetical rest. */
  divider: {
    height: StyleSheet.hairlineWidth,
    backgroundColor: colors.divider,
    marginVertical: spacing.sm,
    marginHorizontal: spacing.md,
  },

  state: { padding: spacing.xl },
  empty: { ...typography.body, color: colors.textMuted, textAlign: 'center' },
});
