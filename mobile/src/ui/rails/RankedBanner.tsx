import { StyleSheet, Text, View } from 'react-native';
import { Image as ExpoImage } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { Clock } from 'phosphor-react-native';

import { banner, colors, fonts, rankedBanner } from '../theme';
import { imageUrl } from '../media';
import { capitalizeWords, rankLabel, seriesMeta, slideGenres, slideRuntime } from './banner';
import { BannerButton } from './BannerButton';
import { BannerPager } from './BannerPager';
import { DottedList } from './DottedList';
import { Focusable } from './Focusable';
import { ImdbMark } from './ImdbMark';
import { TextureText } from './TextureText';
import type { BannerSlide } from '../../api/catalogue';

const PLATE = require('../../../assets/streamit/trending-label.webp');

/**
 * The two daily Top 10 banners, each at the website's own design.
 *
 * `kind: 'banner'` on a home rail, and `style` says which — `movies_today` is
 * `components/sections/verticle-slider.blade.php`, `series_today` is
 * `components/sections/tab-slider.blade.php`. They read as a pair and are not
 * built like one, which is why this dispatches to two slides rather than
 * parameterising one:
 *
 *              movies_today                 series_today
 *   align      centred, 32pt gutters        left, 16pt gutters
 *   over art   the 0.6 veil alone           the veil, then three gradients
 *   headline   62pt plain white, 2 lines    25pt texture-filled
 *   under it   genre chips, IMDb, runtime   synopsis, release month, seasons
 *   navigation two circular arrows          dynamic pagination dots
 *   height     its content                  a fixed 480
 *
 * 🔴 **The veil is the one thing here that is not the website's original
 * rendering, and the website now matches it rather than the other way round.**
 * Streamit declares a scrim on `.slider--image`, the element that CONTAINS the
 * slide's `<img>` — so a child image painted over its own parent's background
 * and the scrim never reached a pixel. Every word on that banner sat on raw
 * artwork, and Rio saw it: "the texts here are not visible enough work around
 * the opacity to have it clear". The fix landed on both surfaces in the same
 * change, at the same measured alpha. See `rankedBanner.scrim`.
 *
 * Everything dimensional comes from `theme.rankedBanner`, read off `/` in a
 * headless browser at 390x844 and confirmed unchanged at 430x932 — Streamit
 * fixes these in pixels inside the phone breakpoint rather than scaling them,
 * so they are constants and not ratios.
 */
export function RankedBanner({
  style,
  items,
  width,
  onPress,
}: {
  style: string | undefined;
  items: readonly BannerSlide[];
  width: number;
  onPress: (item: BannerSlide) => void;
}) {
  const series = style === 'series_today';

  return (
    <View
      style={{
        marginBottom: series ? rankedBanner.series.sectionGap : rankedBanner.movies.sectionGap,
      }}
    >
      <BannerPager
        items={items}
        width={width}
        indicator={series ? 'dots' : 'arrows'}
        dynamicDots={series}
        keyExtractor={(item, i) => `${item.type ?? ''}${item.id ?? i}`}
        renderItem={(item) =>
          series ? (
            <SeriesSlide item={item} width={width} onPress={() => onPress(item)} />
          ) : (
            <MoviesSlide item={item} width={width} onPress={() => onPress(item)} />
          )
        }
      />
    </View>
  );
}

/**
 * One slide of the Top 10 Movies of the Day.
 *
 * **The height is the content's**, which is the site's too: the slide measured
 * 530.375 at both viewports because that is what its six stacked blocks add up
 * to, not because anything declares it. The headline is clamped to two lines,
 * so the content is bounded; a horizontal list stretches every slide to the
 * tallest, exactly as one swiper wrapper does.
 */
function MoviesSlide({
  item,
  width,
  onPress,
}: {
  item: BannerSlide;
  width: number;
  onPress: () => void;
}) {
  const title = item.title ?? 'Untitled';
  const runtime = slideRuntime(item);
  const genres = slideGenres(item);

  return (
    <View style={styles.moviesSlide}>
      <Backdrop item={item} width={width} />
      <View style={styles.scrim} pointerEvents="none" />

      <View style={styles.moviesContent} pointerEvents="box-none">
        <Focusable
          accessibilityLabel={title}
          accessibilityHint="Opens details"
          ringRadius={0}
          onPress={onPress}
          style={styles.stretch}
        >
          <Plate item={item} centred />

          {genres.length === 0 ? null : (
            <DottedList
              parts={genres}
              fontSize={rankedBanner.movies.genreSize}
              lineHeight={rankedBanner.movies.genreLineHeight}
              weight={rankedBanner.movies.genreWeight}
              tracking={rankedBanner.movies.genreTracking}
              dotColor={rankedBanner.movies.genreDotColor}
              gap={rankedBanner.movies.genreGap}
              centred
              style={styles.genres}
            />
          )}

          {/*
            Plain text, not TextureText. The series banner's headline is
            `.texture-text` and this one is not — it computes to solid white —
            so the two are drawn by different components on purpose.
          */}
          <Text
            numberOfLines={rankedBanner.movies.titleLines}
            style={styles.moviesTitle}
            accessibilityElementsHidden
            importantForAccessibility="no-hide-descendants"
          >
            {capitalizeWords(title)}
          </Text>

          <View style={styles.moviesMeta}>
            {/*
              Unconditional, as it is on the hero: the mark says nothing about
              this particular title, so there is no state in which it is the
              wrong thing to draw. It is also what makes this row 48 tall.
            */}
            <ImdbMark size={banner.imdbSize} />

            {runtime === null ? null : (
              <View style={styles.runtime}>
                <Clock size={rankedBanner.movies.clockSize} color={colors.text} />
                <Text style={styles.runtimeText}>{runtime}</Text>
              </View>
            )}
          </View>

          {item.synopsis ? (
            <Text numberOfLines={rankedBanner.movies.synopsisLines} style={styles.moviesSynopsis}>
              {item.synopsis}
            </Text>
          ) : null}
        </Focusable>

        <BannerButton label="Play Now" align="center" onPress={onPress} />
      </View>
    </View>
  );
}

/**
 * One slide of the Top 10 Series of the Day.
 *
 * A fixed 480 tall with its content centred, which is what the site does — the
 * slide stayed 480 at both viewports while the content inside it changed
 * height. The episode list the blade carries is not here because no phone ever
 * draws it: it sits in a `d-none d-lg-block` column.
 */
function SeriesSlide({
  item,
  width,
  onPress,
}: {
  item: BannerSlide;
  width: number;
  onPress: () => void;
}) {
  const title = item.title ?? 'Untitled';
  const meta = seriesMeta(item);

  return (
    <View style={styles.seriesSlide}>
      <Backdrop item={item} width={width} />
      <View style={styles.scrim} pointerEvents="none" />

      {/* Three layers in the order the site's one `::before` declares them. */}
      <LinearGradient
        colors={rankedBanner.series.scrimLeftColors}
        locations={rankedBanner.series.scrimLeftLocations}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />
      <LinearGradient
        colors={rankedBanner.series.scrimRightColors}
        locations={rankedBanner.series.scrimRightLocations}
        start={{ x: 1, y: 0 }}
        end={{ x: 0, y: 0 }}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />
      <LinearGradient
        colors={rankedBanner.series.scrimBottomColors}
        locations={rankedBanner.series.scrimBottomLocations}
        start={{ x: 0, y: 0 }}
        end={{ x: 0, y: 1 }}
        style={StyleSheet.absoluteFill}
        pointerEvents="none"
      />

      <View style={styles.seriesContent} pointerEvents="box-none">
        <Focusable
          accessibilityLabel={title}
          accessibilityHint="Opens details"
          ringRadius={0}
          onPress={onPress}
          style={styles.stretch}
        >
          <Plate item={item} />

          <View style={styles.seriesTitle}>
            <TextureText
              size={rankedBanner.series.titleSize}
              weight={rankedBanner.series.titleWeight}
              fontFamily={fonts.black}
              numberOfLines={2}
              accessibilityHidden
            >
              {capitalizeWords(title)}
            </TextureText>
          </View>

          {item.synopsis ? (
            <Text numberOfLines={rankedBanner.series.synopsisLines} style={styles.seriesSynopsis}>
              {item.synopsis}
            </Text>
          ) : null}

          {meta.length === 0 ? null : (
            <DottedList
              parts={meta}
              fontSize={rankedBanner.series.metaSize}
              lineHeight={rankedBanner.series.metaLineHeight}
              dotColor={rankedBanner.series.metaDotColor}
              gap={rankedBanner.series.metaGap}
              style={styles.seriesMeta}
            />
          )}
        </Focusable>

        <BannerButton label="Stream Now" onPress={onPress} />
      </View>
    </View>
  );
}

/**
 * The artwork.
 *
 * Drawn plainly rather than as a control of its own: the Focusable over the
 * content carries the whole slide's press target, and wrapping the image would
 * give a remote two stops for one thing. `contentPosition` differs between the
 * two banners because the site's does — the movies slide is an `<img>` with
 * `object-fit: cover` and no position, which centres, and the series slide is
 * a `background-position: 50% 50%`. Both centre; the hero's top anchor is the
 * odd one out, and it is not copied here.
 */
function Backdrop({ item, width }: { item: BannerSlide; width: number }) {
  return (
    <ExpoImage
      source={imageUrl(item.backdrop_url, width)}
      style={StyleSheet.absoluteFill}
      contentFit="cover"
      recyclingKey={item.slug ?? String(item.id ?? '')}
      transition={180}
      cachePolicy="memory-disk"
      accessible={false}
    />
  );
}

/**
 * The "Top 10" plate and the line beside it.
 *
 * The line is the honest part of this banner: `rankLabel` prints a rank only
 * when the server says the title earned it today, and "Popular on Jambo" when
 * it was padded in from all-time popularity. See `rankedBanner.ts`.
 */
function Plate({ item, centred = false }: { item: BannerSlide; centred?: boolean }) {
  return (
    <View style={[styles.plateRow, centred && styles.centred]}>
      <ExpoImage
        source={PLATE}
        style={styles.plate}
        contentFit="contain"
        accessibilityLabel="Top 10"
      />
      <Text style={styles.plateLabel}>{rankLabel(item)}</Text>
    </View>
  );
}

const movies = rankedBanner.movies;
const series = rankedBanner.series;

const styles = StyleSheet.create({
  stretch: { alignSelf: 'stretch' },
  /*
   * The measured floor. See `rankedBanner.scrim` for where 0.6 comes from and
   * why it is flat; the website's copy of the same decision is in
   * `public/frontend/css/jambo-header.css`.
   */
  scrim: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: rankedBanner.scrim,
  },
  centred: { justifyContent: 'center' },

  // ── the plate row, identical on both banners ─────────────────────────
  plateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: rankedBanner.plateGap,
  },
  plate: {
    width: rankedBanner.plateSize,
    height: rankedBanner.plateSize,
    borderRadius: rankedBanner.plateRadius,
  },
  plateLabel: {
    color: rankedBanner.labelColor,
    fontSize: rankedBanner.labelSize,
    lineHeight: rankedBanner.labelLineHeight,
    fontWeight: rankedBanner.labelWeight,
  },

  // ── movies_today ─────────────────────────────────────────────────────
  /*
   * A floor, plus `flex: 1` to take whatever the row settles on.
   *
   * The pager lays its slides out in a row, so the row's height is the
   * tallest slide's and every slide stretches to it — which is exactly what
   * one swiper wrapper does on the site. Without `flex: 1` a shorter slide
   * would keep its own height and its backdrop, which fills this box, would
   * stop short of the row.
   */
  moviesSlide: { position: 'relative', flex: 1, minHeight: movies.minHeight },
  /*
   * `.description`: a column that centres its content on both axes, with the
   * artwork behind it. `box-none` so the CTA below the Focusable keeps its own
   * press target rather than being covered by the block.
   */
  moviesContent: {
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: movies.inset,
    paddingVertical: movies.inset,
  },
  genres: { marginTop: movies.plateRowGap, marginBottom: movies.genreRowGap },
  moviesTitle: {
    color: colors.onPrimary,
    fontSize: movies.titleSize,
    lineHeight: movies.titleLineHeight,
    fontWeight: movies.titleWeight,
    textAlign: 'center',
  },
  moviesMeta: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    justifyContent: 'center',
    gap: movies.metaGap,
    paddingVertical: movies.metaPadY,
  },
  runtime: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  runtimeText: {
    color: colors.text,
    fontSize: movies.runtimeSize,
    lineHeight: movies.runtimeLineHeight,
  },
  moviesSynopsis: {
    color: colors.text,
    fontSize: movies.synopsisSize,
    lineHeight: movies.synopsisLineHeight,
    textAlign: 'center',
    marginTop: movies.synopsisMarginTop,
    marginBottom: movies.synopsisMarginBottom,
  },

  // ── series_today ─────────────────────────────────────────────────────
  seriesSlide: { position: 'relative', height: series.height, overflow: 'hidden' },
  seriesContent: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    justifyContent: 'center',
    paddingHorizontal: series.inset,
  },
  seriesTitle: { marginTop: series.plateRowGap, marginBottom: series.titleMarginBottom },
  seriesSynopsis: {
    color: colors.text,
    fontSize: series.synopsisSize,
    lineHeight: series.synopsisLineHeight,
  },
  seriesMeta: {
    marginTop: series.metaMarginTop,
    marginBottom: series.metaMarginBottom,
  },
});
