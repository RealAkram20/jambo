import { useEffect, useState } from 'react';
import { AccessibilityInfo } from 'react-native';

/**
 * Whether this viewer has asked the system for reduced motion.
 *
 * **Read AND subscribed to**, because the setting can be turned on while the
 * app is open — a screen that only checked at mount keeps animating for
 * somebody who has just asked it not to.
 *
 * `apple-design` is explicit that reduced motion means a *gentler equivalent*
 * rather than no feedback at all. A disclosure that opens instantly is fine;
 * a disclosure that gives no signal it opened is not, so callers should still
 * change what is on screen, they simply should not travel to get there.
 *
 * **`PlayerControls` has this same effect written inline and is deliberately
 * not converted here.** The player belongs to another slice and the standing
 * instruction for this work was to leave it alone; this is the canonical
 * place, and that copy is the legacy one. Whoever next opens
 * `ui/player/PlayerControls.tsx` should delete its `useEffect` and call this
 * instead — it is a four-line change and it retires the fork.
 */
export function useReducedMotion(): boolean {
  const [reduced, setReduced] = useState(false);

  useEffect(() => {
    let alive = true;

    void AccessibilityInfo.isReduceMotionEnabled().then((enabled) => {
      if (alive) setReduced(enabled);
    });

    const subscription = AccessibilityInfo.addEventListener('reduceMotionChanged', setReduced);

    return () => {
      alive = false;
      subscription.remove();
    };
  }, []);

  return reduced;
}
