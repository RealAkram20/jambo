import { requireNativeView } from 'expo';
import { forwardRef, useImperativeHandle, useRef } from 'react';

import type { JamboPlayerHandle, JamboPlayerViewProps } from './JamboMedia.types';

/**
 * The native video surface.
 *
 * `requireNativeView` throws at import time when the native module is not in
 * the running binary — which is exactly what happens after adding this module
 * without an `expo prebuild --clean` and a rebuild of the dev client. The
 * error is clear enough to leave alone; swallowing it would give a black
 * rectangle and a much longer hunt.
 */
const NativeView = requireNativeView<
  JamboPlayerViewProps & { ref?: unknown }
>('ExpoJamboMedia');

export const JamboPlayerView = forwardRef<JamboPlayerHandle, JamboPlayerViewProps>(
  function JamboPlayerView(props, ref) {
    const nativeRef = useRef<JamboPlayerHandle | null>(null);

    /*
     * The handle is re-exposed rather than passed straight through so that a
     * call made before the view has mounted is a no-op instead of a crash.
     * The watch screen legitimately calls `seekTo` in response to a session
     * arriving, which can land in the same frame as the first render.
     */
    useImperativeHandle(
      ref,
      () => ({
        play: async () => nativeRef.current?.play(),
        pause: async () => nativeRef.current?.pause(),
        seekTo: async (positionMs: number) => nativeRef.current?.seekTo(positionMs),
        seekBy: async (deltaMs: number) => nativeRef.current?.seekBy(deltaMs),
        replaceSource: async (uri: string, keepPosition: boolean) =>
          nativeRef.current?.replaceSource(uri, keepPosition),
      }),
      [],
    );

    return <NativeView {...props} ref={nativeRef} />;
  },
);
