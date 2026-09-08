<?php

/**
 * Builds the Android launcher icons from the branding the admin has set.
 *
 *   php design/build-app-icons.php
 *
 * Reads mobile/assets/branding/favicon.png (written by export-branding.mjs)
 * and produces two files Expo's prebuild consumes:
 *
 *   mobile/assets/icon.png           1024x1024, opaque, the legacy launcher icon
 *   mobile/assets/adaptive-icon.png  1024x1024, transparent foreground layer
 *
 * WHY THIS EXISTS RATHER THAN POINTING EXPO AT THE FAVICON
 *
 * Two Android rules the source image does not satisfy:
 *
 * 1. A legacy launcher icon with transparency is composited by the launcher
 *    against whatever it likes. The site's mark is transparent, so it is
 *    flattened here onto the same black the app and the website use.
 *
 * 2. An adaptive icon is masked to a shape the OEM chooses — circle, squircle,
 *    teardrop — and only the centre ~66% is guaranteed to survive. The mark
 *    fills roughly 80% of the favicon's canvas, so pointing Expo straight at it
 *    would clip the J on any round-masked launcher, which is most of them.
 *    Both outputs therefore scale the mark down and centre it.
 *
 * The source is 192x192 and these are 1024x1024, so this upscales ~5x and the
 * result is soft. That is a known, accepted trade for now (Rio, 2026-09-08):
 * it is fine for development and the preview APK, and should be replaced with
 * a 1024px master before the Play listing.
 *
 * GD rather than an npm image library: this repository already runs PHP with
 * GD, and the alternative was adding a native dependency to the app's build
 * for three files that change about once a year.
 */

$repo = dirname(__DIR__);
$source = $repo . '/mobile/assets/branding/favicon.png';
$outDir = $repo . '/mobile/assets';

if (! is_file($source)) {
    fwrite(STDERR, "Missing {$source}\nRun: node design/export-branding.mjs\n");
    exit(1);
}

$mark = imagecreatefrompng($source);
if ($mark === false) {
    fwrite(STDERR, "Could not read {$source}\n");
    exit(1);
}

$srcW = imagesx($mark);
$srcH = imagesy($mark);

const CANVAS = 1024;

/** The site's background, so the icon sits on the same black as everything else. */
const BG = [0, 0, 0];

/**
 * How much of the canvas the mark occupies.
 *
 * The adaptive foreground is the smaller of the two: Android's safe zone is the
 * centre 66% and anything outside it is the launcher's to crop. 0.55 keeps the
 * whole mark inside that with room to spare, which is what stops the J losing
 * its shoulder on a circular mask.
 */
const SCALE_LEGACY = 0.72;
const SCALE_ADAPTIVE = 0.55;

function render(GdImage $mark, int $srcW, int $srcH, float $scale, bool $opaque, string $path): void
{
    $canvas = imagecreatetruecolor(CANVAS, CANVAS);

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    if ($opaque) {
        $bg = imagecolorallocate($canvas, BG[0], BG[1], BG[2]);
        imagefilledrectangle($canvas, 0, 0, CANVAS - 1, CANVAS - 1, $bg);
    } else {
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, CANVAS - 1, CANVAS - 1, $transparent);
    }

    // The mark keeps its aspect ratio: squashing a logo to fill a square is the
    // one thing nobody would accept, and it is easy to do by accident here.
    $box = (int) round(CANVAS * $scale);
    $ratio = min($box / $srcW, $box / $srcH);
    $w = (int) round($srcW * $ratio);
    $h = (int) round($srcH * $ratio);
    $x = (int) round((CANVAS - $w) / 2);
    $y = (int) round((CANVAS - $h) / 2);

    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $mark, $x, $y, 0, 0, $w, $h, $srcW, $srcH);
    imagesavealpha($canvas, true);

    imagepng($canvas, $path, 9);
    imagedestroy($canvas);

    printf("  %-22s %dx%d  %s%s", basename($path), CANVAS, CANVAS,
        $opaque ? 'opaque' : 'transparent', PHP_EOL);
}

render($mark, $srcW, $srcH, SCALE_LEGACY, true, $outDir . '/icon.png');
render($mark, $srcW, $srcH, SCALE_ADAPTIVE, false, $outDir . '/adaptive-icon.png');

imagedestroy($mark);

echo "\nBuilt from mobile/assets/branding/favicon.png ({$srcW}x{$srcH}).\n";
echo "Upscaled ~5x — replace the source with a 1024px master before the Play listing.\n";
