import {
  Roboto_400Regular,
  Roboto_500Medium,
  Roboto_700Bold,
  Roboto_900Black,
} from '@expo-google-fonts/roboto';
import { useFonts } from 'expo-font';

/**
 * Roboto, bundled rather than borrowed from Android.
 *
 * Roboto *is* the Android system face, so `fontFamily: 'Roboto'` would cost
 * nothing in bundle size and be pixel-identical — on a Pixel. It is not
 * identical on the handsets this app is actually for. Tecno and Infinix ship
 * HiOS and XOS, which replace the system font, and the app would then render
 * in whatever those skins prefer while the website next to it rendered in
 * Roboto. Three font files is the price of the design surviving contact with
 * the target device.
 *
 * Weight is carried by the family *name*, never by `fontWeight`. Naming both
 * is the standard React Native custom-font bug: Android has no family to
 * synthesise a weight from, so it silently falls back to a system face and the
 * screen renders in something that is not Roboto at all — the exact failure
 * this bundling exists to prevent.
 *
 * Black (900) is here for one reason: the sign-in screen's greeting is set in
 * it, and at that size the difference between Bold and Black is the difference
 * between the screen looking like the design and looking approximately like
 * it. Light (300) is still absent — nothing uses it yet, and a font file per
 * unused weight is bundle size a viewer on a metered connection pays for and
 * never sees.
 */
export function useBrandFonts(): boolean {
  const [loaded, error] = useFonts({
    Roboto_400Regular,
    Roboto_500Medium,
    Roboto_700Bold,
    Roboto_900Black,
  });

  // A font that fails to decode must not hold the app at a splash screen for
  // ever. React Native falls back to the system face, which is legible; a
  // viewer who cannot reach their subscription because a typeface did not load
  // has been failed considerably worse than one looking at the wrong one.
  return loaded || error !== null;
}
