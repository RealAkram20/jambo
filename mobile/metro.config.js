// Learn more: https://docs.expo.dev/guides/customizing-metro/
const { getDefaultConfig } = require('expo/metro-config');

/**
 * The only reason this file exists: `inlineRequires`.
 *
 * Metro defaults it to false and Expo does not override it, so every module in
 * the bundle is evaluated at startup whether or not the viewer ever reaches
 * it. `RootNavigator` imports every screen at module load. For this app that
 * eventually means the player, the detail pages and their rails being parsed
 * and initialised before the first frame of a launch that, most of the time,
 * ends with somebody tapping the second card on the home screen.
 *
 * With this on, a module's factory runs the first time something reads from it
 * rather than when the bundle loads. Nothing downloads differently — React
 * Native ships one bundle either way — but the parse cost moves off the
 * startup path. That matters here more than it would elsewhere: the target
 * handset is a Tecno-class phone, where startup is CPU-bound rather than
 * network-bound.
 *
 * The caveat, and why this is deliberate rather than a default: modules whose
 * *import* has a side effect that must happen in order stop being safe,
 * because the import may now happen later or not at all. This app has one.
 */
const config = getDefaultConfig(__dirname);

config.transformer.getTransformOptions = async () => ({
  transform: {
    inlineRequires: {
      /**
       * `react-native-gesture-handler` installs its native handlers as an
       * import side effect and its own documentation requires it to be first
       * in the entry file. Deferring that import gives a navigator whose
       * gestures work on the second screen and not the first.
       */
      blockList: {
        [require.resolve('react-native-gesture-handler')]: true,
      },
    },
  },
});

module.exports = config;
