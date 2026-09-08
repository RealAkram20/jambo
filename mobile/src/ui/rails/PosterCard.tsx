import { StyleSheet, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { Crown } from 'phosphor-react-native';

import { card, colors } from '../theme';
import { imageUrl } from '../media';
import type { TitleCard } from '../../api/catalogue';
import { Focusable } from './Focusable';

/**
 * A movie or a series card, straight from the generated schema. Hand-writing
 * this shape would compile happily against a server that had renamed a field.
 */
export type PosterItem = TitleCard;

/**
 * A poster in a rail.
 *
 * **The site's poster card is the poster, and nothing else.** Its title, year,
 * runtime, watchlist button and "Play now" all live in `.card-description`,
 * which `card-style.blade.php` renders with `opacity: 0; visibility: hidden`
 * until `.iq-card:hover`. No phone viewer has ever seen them and no remote
 * could reach them, so there is nothing here to reproduce: a hover state
 * cannot exist on either target, and an always-visible version of it would be
 * inventing styling the site does not have.
 *
 * The title is not lost. The site ships it as the image's `alt` and the app
 * ships it as the accessibility label, which is the same fact in the form each
 * platform reads. The whole card is one press target going to the detail
 * screen — which is exactly what a phone viewer gets on the website today,
 * where the entire card is a link to `$cardPath`.
 */
export function PosterCard({
  item,
  width,
  height,
  onPress,
  hasTVPreferredFocus = false,
}: {
  item: PosterItem;
  width: number;
  height: number;
  onPress?: (() => void) | undefined;
  hasTVPreferredFocus?: boolean;
}) {
  const title = item.title ?? 'Untitled';
  const premium = typeof item.tier_required === 'string' && item.tier_required !== '';

  return (
    <Focusable
      accessibilityRole="imagebutton"
      // The year is included because a rail of posters is often several
      // versions of the same story, and "Dune" twice is not a choice.
      accessibilityLabel={item.year ? `${title}, ${item.year}` : title}
      accessibilityHint="Opens details"
      ringRadius={card.radius}
      hasTVPreferredFocus={hasTVPreferredFocus}
      onPress={onPress}
      style={{ width, height }}
    >
      <ExpoImage
        // The width the image is *drawn* at decides which size is fetched. See
        // src/ui/media.ts: the API hands out originals, the site's own cards do
        // not, and thirteen rails of originals is the difference between a home
        // screen that loads on mobile data and one that does not.
        source={imageUrl(item.poster_url, width)}
        style={[styles.poster, { width, height, borderRadius: card.radius }]}
        contentFit="cover"
        // A rail is recycled as it scrolls; without this the wrong poster
        // shows for a frame when a row is reused.
        recyclingKey={item.slug ?? String(item.id ?? title)}
        transition={120}
        cachePolicy="memory-disk"
        // Decorative: the Focusable above already carries the accessible name,
        // and announcing the poster again would read the title twice.
        accessible={false}
      />
      {premium ? <PremiumBadge /> : null}
    </Focusable>
  );
}

/**
 * The crown a `tier_required` title carries, drawn where the site draws it.
 *
 * ⚠️ The colours are the website's and they are hard to see: a `#d1d0cf` crown
 * on a `#ffd81c` badge is about 1.3:1, well under the 3:1 WCAG asks of a
 * meaningful graphic. Both values are read off the rendered site, so this is
 * the site's contrast problem rather than one introduced here — changing it in
 * the app alone would make the two disagree. Raised with Rio rather than
 * resolved quietly; the honest fix is on the website, where both clients would
 * get it.
 *
 * It is not the only signal in any case: the badge has an accessibility label,
 * so the information is not carried by colour alone.
 */
export function PremiumBadge() {
  return (
    <View
      accessible
      accessibilityLabel="Premium"
      style={[
        styles.badge,
        {
          width: card.badgeSize,
          height: card.badgeSize,
          borderRadius: card.badgeRadius,
          backgroundColor: card.badgeBg,
        },
      ]}
    >
      <Crown size={card.badgeIconSize} color={card.badgeIcon} weight="fill" />
    </View>
  );
}

const styles = StyleSheet.create({
  // A ground under the image so a slow or missing poster is a dark card of the
  // right shape rather than a hole in the rail.
  poster: { backgroundColor: colors.surface },

  badge: {
    position: 'absolute',
    // The site puts it top-right, clear of the release ribbon on the left.
    top: 8,
    right: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
