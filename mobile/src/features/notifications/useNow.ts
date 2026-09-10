import { useSyncExternalStore } from 'react';

const MINUTE = 60_000;

function subscribe(onChange: () => void): () => void {
  const id = setInterval(onChange, MINUTE);
  return () => clearInterval(id);
}

/**
 * The current time, to the minute, as a value a component may read in render.
 *
 * Every row and every day heading on the inbox asks what time it is. Asking
 * separately means a list can straddle midnight mid-render — a row filed under
 * Today above a heading that has already become yesterday — so the screen
 * reads the clock once and hands the same number to both.
 *
 * **`useSyncExternalStore` rather than `Date.now()` in a `useMemo`.** The clock
 * is a mutable value outside React, and reading one during render is exactly
 * what this hook exists for; the alternative is an impure render, which the
 * lint rule refuses and which produces timestamps that change on any unrelated
 * re-render. `getSnapshot` quantises to the minute so it returns the same
 * number every time it is called within one, which is the stability the store
 * contract requires — an unrounded `Date.now()` here would re-render forever.
 *
 * The minute tick is also why "2m ago" becomes "3m ago" on a screen somebody
 * leaves open, instead of freezing at whatever it said when the list loaded.
 */
export function useNow(): number {
  return useSyncExternalStore(subscribe, () => Math.floor(Date.now() / MINUTE) * MINUTE);
}
