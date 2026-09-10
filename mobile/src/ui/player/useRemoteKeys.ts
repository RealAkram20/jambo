import { useTVEventHandler } from 'react-native';

/**
 * The remote control, wired now rather than in Phase 4.
 *
 * Android TV is formally Phase 4, but the player is where that phase is won or
 * lost: retrofitting key handling into a finished player means revisiting
 * every control, every focus order and every overlay. Building it with the
 * player costs this file.
 *
 * `useTVEventHandler` is a no-op on a phone build — react-native-tvos ships
 * it on both targets and only the TV runtime emits events — so this runs
 * harmlessly in the app Rio is testing today.
 *
 * WHAT THE KEYS DO, and why these and not others:
 *
 *  - **select / playPause** toggle playback. `select` is the OK button, which
 *    is what a viewer presses first; a remote's dedicated play/pause key is
 *    the same action.
 *  - **left / right** seek by ten seconds, the same step the site's own
 *    `<media-seek-button seconds="10">` uses. On a d-pad this is the primary
 *    way to scrub: there is no finger to drag the bar with.
 *  - **up** reveals the controls without changing anything, so a viewer can
 *    see where they are before acting.
 *  - **down** opens settings, which is where quality lives.
 *
 * Long-press variants are deliberately absent. `apple-design` warns that
 * disambiguating a long press delays the short one, and a viewer holding right
 * to scrub fast is a feature that would make every single ten-second seek feel
 * laggy. Repeat presses already scrub quickly.
 */
export type RemoteKeyHandlers = {
  onPlayPause: () => void;
  onSeekBy: (deltaMs: number) => void;
  onShowControls: () => void;
  onSettings: () => void;
  /** Ignore keys while a menu is up: the menu owns the d-pad then. */
  enabled: boolean;
  seekStepMs: number;
};

export function useRemoteKeys({
  onPlayPause,
  onSeekBy,
  onShowControls,
  onSettings,
  enabled,
  seekStepMs,
}: RemoteKeyHandlers): void {
  useTVEventHandler((event: { eventType?: string } | undefined) => {
    if (!enabled) return;

    const type = event?.eventType;
    if (type === undefined) return;

    /*
     * Every key first wakes the controls. A remote press that seeks without
     * showing where it seeked to is the television equivalent of feedback
     * that arrives only at the end of a gesture — the viewer has no idea
     * whether anything happened.
     */
    switch (type) {
      case 'playPause':
      case 'select':
        onShowControls();
        onPlayPause();
        return;
      case 'left':
        onShowControls();
        onSeekBy(-seekStepMs);
        return;
      case 'right':
        onShowControls();
        onSeekBy(seekStepMs);
        return;
      case 'up':
        onShowControls();
        return;
      case 'down':
        onShowControls();
        onSettings();
        return;
      default:
        // `blur`, `focus`, `pan` and the long-press variants. Deliberately
        // ignored rather than swallowed silently by a catch-all above, so a
        // key this player should handle stands out as a missing case.
        return;
    }
  });
}
