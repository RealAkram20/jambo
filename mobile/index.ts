/*
 * `react-native-gesture-handler` must be the first import in the entry file.
 *
 * Its own documentation requires it: the import installs native handlers as a
 * side effect, and the navigators depend on that having already happened.
 * `metro.config.js` block-lists this module from `inlineRequires` for the same
 * reason — with inlining on, an import is evaluated on first *read*, and
 * nothing here reads from it.
 *
 * A tidy-up that removes this line as unused fails no test, no typecheck and
 * no lint run. It produces gestures that work on the second screen and not the
 * first.
 */
import 'react-native-gesture-handler';

import { registerRootComponent } from 'expo';
import * as Sentry from '@sentry/react-native';

import App from './App';
import { startObservability } from './src/observability';

/*
 * Before React, deliberately: a crash during startup is the one most worth
 * reporting, and a reporter initialised inside a component has already missed
 * it. Inert without EXPO_PUBLIC_SENTRY_DSN.
 */
startObservability();

/*
 * `Sentry.wrap` measures app start to the first mounted component rather than
 * to the end of bundle evaluation — which for this app is the difference
 * between timing the bundle and timing what actually happens at launch: the
 * fonts, the keystore read for the device id and the session, and the config
 * call. Cold start is a viewer's first impression and those are what it is
 * made of.
 */
registerRootComponent(Sentry.wrap(App));
