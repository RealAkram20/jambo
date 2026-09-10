import { StyleSheet, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { Check } from 'phosphor-react-native';

import type { WatchlistCard as WatchlistItemCard } from '../../api/catalogue';
import { imageUrl } from '../../ui/media';
import { PremiumBadge } from '../../ui/rails/PosterCard';
import { Focusable } from '../../ui/rails/Focusable';
import { card, colors, watchlist } from '../../ui/theme';
import { metaLine, playTargetFor, titleOf } from './list';

/**
 * One saved title: **the poster, and nothing else.**
 *
 * Rio, 2026-09-09, looking at the rendered screen: *"let these be 3 and a half
 * cards the same way we have them on our webapp, and no text descriptions —
 * the cards are simply clean."* So this is now the site's own card, at the
 * site's own size, and the title, meta line and per-card buttons an earlier
 * cut drew under the poster are gone.
 *
 * **That is not a reduction, it is the web app's card.** `PosterCard` already
 * carries the reason in full: everything `card-style.blade.php` puts under a
 * poster — title, year, runtime, genres, the watchlist button and "Play now" —
 * lives inside `.card-description`, which is `opacity: 0; visibility: hidden`
 * until `.iq-card:hover`. No phone viewer has ever seen any of it. Drawing it
 * permanently was the foreign design; a bare poster is the replica.
 *
 * **The hover state resolves to the press, and that is the site's own answer,
 * not an invention.** Both watchlist templates wrap the whole poster in one
 * `<a href="{{ $cardPath }}">`, and on the watchlist that path is the queue
 * player for a movie and the series page for a show
 * (`widgets/watchlist-detail-card.blade.php`). A phone visiting the site today
 * cannot hover, so the whole card is already the link — which is exactly what
 * this component does.
 *
 * **The text is not lost; it moves to where a non-sighted viewer reads it.**
 * The card's accessibility label carries the title and the meta line the
 * poster cannot speak, which is the same fact in the form each user reads —
 * the site ships the title as the image's `alt` for the same reason.
 */
export function WatchlistCard({
  item,
  width,
  height,
  editing,
  selected,
  onPress,
  onToggleSelect,
}: {
  item: WatchlistItemCard;
  width: number;
  height: number;
  editing: boolean;
  selected: boolean;
  onPress: () => void;
  onToggleSelect: () => void;
}) {
  const title = titleOf(item);
  const meta = metaLine(item);
  const tier = (item as { tier_required?: string | null }).tier_required;
  const premium = typeof tier === 'string' && tier !== '';
  const poster =
    (item as { poster_url?: string }).poster_url ?? (item as { still_url?: string }).still_url;

  /*
   * Whether a press plays or opens. `WatchlistPlayResolver` says which, and
   * says null for an unreleased title or a series with no episodes — the case
   * the site handles by swapping "Play now" for "View details". The hint tells
   * a screen-reader user which of the two this card will do, since there is no
   * longer a separate button whose label could.
   */
  const plays = playTargetFor(item) !== null;

  return (
    <Focusable
      accessibilityRole={editing ? 'checkbox' : 'imagebutton'}
      // Everything the poster shows to an eye, said once, to everyone else.
      accessibilityLabel={
        editing
          ? `${title}, ${selected ? 'selected' : 'not selected'}`
          : `${title}. ${meta === '—' ? 'No details' : meta}${premium ? '. Premium' : ''}`
      }
      accessibilityHint={
        editing ? 'Selects this title for removal' : plays ? 'Plays this title' : 'Opens details'
      }
      accessibilityState={editing ? { checked: selected } : {}}
      ringRadius={card.radius}
      onPress={editing ? onToggleSelect : onPress}
      style={{ width, height }}
    >
      <ExpoImage
        // The width the image is *drawn* at decides which size is fetched, so
        // a 3.5-across card asks for a 3.5-across image. See ui/media.ts.
        source={imageUrl(poster, width)}
        style={[styles.poster, { width, height, borderRadius: card.radius }]}
        contentFit="cover"
        // A grid is recycled as it scrolls; without this the wrong poster
        // shows for a frame when a row is reused.
        recyclingKey={
          (item as { slug?: string }).slug ?? String((item as { id?: number }).id ?? title)
        }
        transition={120}
        cachePolicy="memory-disk"
        // Decorative: the Focusable above already carries the accessible name,
        // and announcing the poster again would read the title twice.
        accessible={false}
      />

      {/* The site's own crown, in the site's own corner. */}
      {premium ? <PremiumBadge /> : null}

      {/*
        Edit mode only, and the two marks work together: the wash makes
        "selected" legible while scrolling, and the tick means a viewer is not
        reading that state out of a brightness difference alone.
      */}
      {editing && !selected ? (
        <View style={[styles.wash, { borderRadius: card.radius }]} pointerEvents="none" />
      ) : null}

      {editing ? (
        <View
          style={[styles.select, selected ? styles.selectOn : styles.selectOff]}
          pointerEvents="none"
        >
          {selected ? <Check size={14} color={colors.onPrimary} weight="bold" /> : null}
        </View>
      ) : null}
    </Focusable>
  );
}

const styles = StyleSheet.create({
  // A ground under the image so a slow or missing poster is a dark card of the
  // right shape rather than a hole in the grid.
  poster: { backgroundColor: colors.surface },

  wash: {
    position: 'absolute',
    top: 0,
    right: 0,
    bottom: 0,
    left: 0,
    backgroundColor: watchlist.unselectedWash,
  },

  select: {
    position: 'absolute',
    top: 6,
    left: 6,
    width: watchlist.selectSize,
    height: watchlist.selectSize,
    borderRadius: watchlist.selectSize / 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  selectOn: { backgroundColor: watchlist.selectBg },
  selectOff: {
    backgroundColor: watchlist.selectIdleBg,
    borderWidth: watchlist.selectIdleBorderWidth,
    borderColor: watchlist.selectIdleBorder,
  },
});
