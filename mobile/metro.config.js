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

/*
 * One package, one specifier, one override.
 *
 * `libphonenumber-js` publishes its metadata twice in its exports map:
 *
 *   "./metadata.max.json": { import: "./metadata.max.json.js",
 *                            require: "./metadata.max.json" }
 *
 * The `import` target is a shim the package ships because Node's ESM loader
 * cannot import JSON. Metro resolves an `import` statement with the `import`
 * condition, lands on `metadata.max.json.js`, and cannot resolve that double
 * extension — the app builds, launches, and dies on the dev launcher's error
 * screen. Metro reads `.json` natively and needs no shim at all, so this
 * points that one specifier at the `require` target.
 *
 * **Deliberately not `unstable_enablePackageExports = false`**, which is the
 * fix most often suggested for this. That switch changes how *every* package
 * in the tree resolves, to fix one file — a blast radius of the whole
 * dependency graph for a problem the size of a single import.
 */
const path = require('node:path');

const defaultResolveRequest = config.resolver.resolveRequest;

config.resolver.resolveRequest = (context, moduleName, platform) => {
  if (moduleName === 'libphonenumber-js/metadata.max.json') {
    return {
      type: 'sourceFile',
      filePath: path.join(
        __dirname,
        'node_modules',
        'libphonenumber-js',
        'metadata.max.json',
      ),
    };
  }

  return defaultResolveRequest
    ? defaultResolveRequest(context, moduleName, platform)
    : context.resolveRequest(context, moduleName, platform);
};

module.exports = config;
