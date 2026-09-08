import * as Sentry from '@sentry/react-native';

import { SENTRY_DSN, VARIANT } from './config/env';
import { appVersion } from './config/version';

/**
 * Crash and error reporting, inert without a DSN.
 *
 * Wired at bootstrap rather than added later, for one reason: the first real
 * crash will happen on a handset in somebody's hand, on a network and an
 * Android build nobody here owns, and there is no debugger to attach to that.
 * A report is the only thing that will exist.
 *
 * `EXPO_PUBLIC_SENTRY_DSN` is unset in development and under Jest, which is
 * how this whole path stays silent there — `Sentry.init` with an empty DSN is
 * a documented no-op rather than an error.
 *
 * **Nothing viewer-identifying is attached.** No email, no username, no user
 * id. A crash report is a debugging artefact, and the moment it carries an
 * account it becomes a copy of the customer list living at a third party.
 * `sendDefaultPii` is false explicitly rather than by default, so that a
 * future SDK changing its default cannot quietly change this decision.
 */
export function startObservability(): void {
  if (SENTRY_DSN === '') return;

  Sentry.init({
    dsn: SENTRY_DSN,
    sendDefaultPii: false,
    // The variant is the single most useful tag here: `play` and `direct` are
    // different builds with different capabilities, and a report that does not
    // say which is a report that cannot be reproduced.
    environment: VARIANT,
    ...(appVersion() !== null ? { release: `jambo-app@${appVersion()}` } : {}),
  });
}
