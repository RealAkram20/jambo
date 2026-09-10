import { StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { Clock } from 'phosphor-react-native';

import { banner, colors, fonts, hero, typography } from '../theme';
import { imageUrl } from '../media';
import { bannerBadge, bannerRuntime, taxonomyNames } from './banner';
import { BannerPager } from './BannerPager';
import { Focusable } from './Focusable';
import { BannerButton } from './BannerButton';
import { ImdbMark } from './ImdbMark';
import { TextureText } from './TextureText';
import type { HeroCard } from '../../api/catalogue';

/**
 * The home banner: the website's own, slide for slide.
 *
 * What the site draws, in this order, is
 * `components/partials/hero-banner.blade.php`: a full-bleed backdrop, a
 * texture-filled headline, a meta row of certification-or-season badge, the
 * IMDb mark and a runtime, a three-line synopsis, then Tags, Genres and
 * Starring, then Play Now. The measurements behind every number here are in
 * `theme.banner`, read off the rendered page at 390pt rather than matched by
 * eye.
 *
 * **The five-star row is gone from this banner and from the website's**, by
 * Rio's decision on 2026-09-10. It was never a rating: the blade reads
 * `ratings()->avg('stars') ?? 5`, the `ratings` table is empty, and no
 * controller or endpoint on either surface can write to it — only a seeder
 * can. So every one of the 120 titles showed five filled gold stars,
 * permanently, for a score nobody had given. The IMDb mark it sat beside
 * stays, on both. ADR-0006 has the reasoning and the alternatives.
 *
 * The paging, the dots, and the two deliberate departures that come with them
 * — no auto-rotation and no thumbnail strip — are `BannerPager`, which all
 * three of the site's home banners now share.
 */
export function Hero({
  items,
  width,
  onPress,
}: {
  items: readonly HeroCard[];
  width: number;
  onPress: (item: HeroCard) => void;
}) {
  const height = heroHeight(width);

  /*
   * The paging list and the dot strip both live in `BannerPager` as of the
   * daily-banners slice. They were written here first, and the second banner
   * to need them would have been a copy — which is the one thing this
   * repository's rules are most explicit about. Nothing rendered changes: the
   * hero's dots are the site's equal-sized ones, measured again on the way
   * out, so `dynamicDots` stays off.
   */
  return (
    <BannerPager
      items={items}
      width={width}
      indicator="dots"
      keyExtractor={(item, i) => `${item.type ?? ''}${item.id ?? i}`}
      renderItem={(item) => (
        <HeroSlide item={item} width={width} height={height} onPress={() => onPress(item)} />
      )}
    />
  );
}

function HeroSlide({
  item,
  width,
  height,
  onPress,
}: {
  item: HeroCard;
  width: number;
  height: number;
  onPress: () => void;
}) {
  const title = item.title ?? 'Untitled';

  return (
    <View style={{ width, height }}>
      {/*
        The artwork is the slide's background and not a control of its own, so
        it is drawn plainly and the Focusable below carries the whole slide's
        press target and label. Wrapping the image would give a remote two
        stops for one thing.
      */}
      <ExpoImage
        source={imageUrl(item.backdrop_url, width)}
        style={StyleSheet.absoluteFill}
        contentFit="cover"
        // `background-position: 50% 0%` on the site. It matters most when a
        // title has no backdrop and the server falls back to its poster: a
        // 5:7 poster centred in this box crops to a midriff.
        contentPosition="top"
        recyclingKey={item.slug ?? String(item.id ?? title)}
        transition={180}
        cachePolicy="memory-disk"
        accessible={false}
      />

      {/* Two layers, left then right, exactly as the site stacks them. */}
      <LinearGradient
        colors={banner.scrimLeftColors}
        locations={banner.scrimLeftLocations}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />
      <LinearGradient
        colors={banner.scrimRightColors}
        locations={banner.scrimRightLocations}
        start={{ x: 1, y: 0 }}
        end={{ x: 0, y: 0 }}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />

      <View style={styles.contentWrap} pointerEvents="box-none">
        <Focusable
          accessibilityLabel={title}
          accessibilityHint="Opens details"
          ringRadius={0}
          onPress={onPress}
          style={styles.content}
        >
          <View style={styles.title}>
            <TextureText
              size={hero.titleSize}
              weight={hero.titleWeight}
              tracking={hero.tracking}
              fontFamily={fonts.black}
              numberOfLines={1}
              accessibilityHidden
            >
              {title}
            </TextureText>
          </View>

          <MetaRow item={item} />

          {item.synopsis ? (
            <Text numberOfLines={banner.synopsisLines} style={styles.synopsis}>
              {item.synopsis}
            </Text>
          ) : null}

          <TaxonomyLine label="Tags" terms={item.tags} />
          <TaxonomyLine label="Genres" terms={item.genres} />
          <TaxonomyLine label="Starring" terms={item.cast} />
        </Focusable>

        <View style={styles.cta}>
          <BannerButton label="Play Now" onPress={onPress} />
        </View>
      </View>
    </View>
  );
}

/**
 * Badge, IMDb mark, runtime — the site's one wrapping row.
 *
 * The badge and the runtime are each conditional, because each is a claim
 * about this particular title and an absent one should draw nothing rather
 * than a gap. The mark is not conditional: it says nothing about the title,
 * so there is no state in which it is the wrong thing to draw.
 *
 * The row's 48pt height comes from the mark's 32pt box rather than from any
 * text in it, which is true on the website too and is why the app's row was
 * 14pt short of the site's while the mark was missing.
 */
function MetaRow({ item }: { item: HeroCard }) {
  const badge = bannerBadge(item);
  const runtime = bannerRuntime(item);

  return (
    <View style={styles.meta}>
      {badge === null ? null : (
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{badge.toUpperCase()}</Text>
        </View>
      )}

      {/*
        The IMDb mark sits where the site puts it, between the badge and the
        runtime, and it is unconditional because it says nothing about this
        particular title. The five-star row that used to be beside it is gone
        from both surfaces — Rio's call, 2026-09-10, once it was established
        that the ratings table is empty and nothing in the product can write
        to it. See ADR-0006.
      */}
      <ImdbMark size={banner.imdbSize} />

      {runtime === null ? null : (
        <View style={styles.runtime}>
          <Clock size={banner.runtimeFontSize} color={colors.text} />
          <Text style={styles.runtimeText}>{runtime}</Text>
        </View>
      )}
    </View>
  );
}

/** "Genres: Drama, Comedy" — the label in primary, the names in body text. */
function TaxonomyLine({
  label,
  terms,
}: {
  label: string;
  terms: readonly { name?: string }[] | undefined;
}) {
  const names = taxonomyNames(terms);

  if (names === null) return null;

  return (
    <Text style={styles.taxonomy} numberOfLines={1}>
      <Text style={styles.taxonomyLabel}>{label}: </Text>
      {names}
    </Text>
  );
}

/**
 * How tall the banner is.
 *
 * The site's slide is 440 tall in a 390 viewport, so the shape is a ratio and
 * not a number — a fixed height is a phone height, and on a rotated phone it
 * would be most of the screen. The cap is what stops it following the width
 * into landscape, where the slide would otherwise be taller than the screen.
 */
function heroHeight(width: number): number {
  return Math.round(Math.min(width * banner.aspect, 520));
}

const styles = StyleSheet.create({
  /*
   * The content is centred in the slide, which is what `align-items: center`
   * on the site's row resolves to: 50 above and 50 below a 340-tall block in
   * a 440-tall slide.
   *
   * `box-none` so the artwork behind it is not swallowed, and so the CTA below
   * stays its own press target rather than being covered by the block.
   */
  contentWrap: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    justifyContent: 'center',
    paddingHorizontal: banner.inset,
  },
  content: { alignSelf: 'stretch' },
  title: { marginBottom: banner.titleGap },

  meta: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: banner.metaGap,
    paddingVertical: banner.metaPadY,
  },
  badge: {
    backgroundColor: banner.badgeBg,
    paddingVertical: banner.badgePadY,
    paddingHorizontal: banner.badgePadX,
    // Square, not rounded. `.badge.rounded-0` on the site, and it is the one
    // hard corner in the banner.
    borderRadius: 0,
  },
  badgeText: {
    color: banner.badgeFg,
    fontSize: banner.badgeFontSize,
    fontWeight: '700',
  },
  runtime: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  runtimeText: {
    color: colors.text,
    fontSize: banner.runtimeFontSize,
    fontWeight: '500',
  },

  synopsis: {
    ...typography.body,
    color: colors.text,
    lineHeight: banner.synopsisLineHeight,
    marginVertical: banner.synopsisMarginY,
  },

  taxonomy: {
    fontSize: banner.taxonomyFontSize,
    lineHeight: banner.taxonomyLineHeight,
    color: colors.text,
    marginBottom: banner.taxonomyGap,
  },
  taxonomyLabel: { color: colors.primary },

  cta: { marginTop: banner.buttonMarginTop },
});
