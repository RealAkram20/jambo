import { StyleSheet, Text, View } from 'react-native';
import { useQuery } from '@tanstack/react-query';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '../api/jambo';
import type { TitleCard } from '../api/catalogue';
import { colors, spacing, typography } from '../ui/theme';
import { useRailMetrics } from '../ui/metrics';
import { ErrorState, Loading } from '../ui/components';
import { PosterGrid } from '../ui/rails/PosterGrid';
import type { AppScreenProps } from '../navigation/types';

/**
 * A genre, category, VJ or cast archive.
 *
 * One screen for all four, because all four answer with the same shape — the
 * entity, then its movies, then its series — and the website's archive pages
 * differ by heading and source rather than by layout.
 *
 * Movies and series are shown as one grid rather than two sections. The
 * endpoint splits them because the models are different tables; a viewer
 * looking at "Action" wants the action titles, and a section break that says
 * nothing about the content is a division for the database's benefit.
 */
export function TaxonomyScreen({ route, navigation }: AppScreenProps<'Taxonomy'>) {
  const { kind, slug, name } = route.params;
  const metrics = useRailMetrics();

  const { data, isPending, isError, error, refetch } = useQuery({
    queryKey: ['taxonomy', kind, slug],
    queryFn: () => api.taxonomy(kind, slug),
  });

  if (isPending) return <Loading label={`Loading ${name}`} />;

  if (isError) {
    return (
      <SafeAreaView style={styles.screen} edges={['top']}>
        <ErrorState
          message={
            error instanceof Error && error.message !== ''
              ? error.message
              : `We could not load ${name}.`
          }
          onRetry={() => {
            void refetch();
          }}
        />
      </SafeAreaView>
    );
  }

  const items: TitleCard[] = [...data.movies, ...data.series];

  return (
    <View style={styles.screen}>
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
        header={
          data.description === null ? undefined : (
            <View style={styles.blurb}>
              <Text style={styles.blurbText}>{data.description}</Text>
            </View>
          )
        }
        emptyTitle={`Nothing in ${name} yet`}
        emptyDetail="No published titles here at the moment. Try another from the home screen."
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  blurb: { paddingHorizontal: spacing.xs, paddingBottom: spacing.lg },
  blurbText: { ...typography.body, color: colors.textMuted },
});
