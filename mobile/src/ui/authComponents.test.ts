import { readFileSync } from 'node:fs';
import { join } from 'node:path';

/**
 * A regression guard for the bug that made the sign-in form unusable.
 *
 * 🔴 What happened, 2026-09-09. `AuthField` drew its focus glow by adding
 * `elevation` to the `well` — the View that *contains* the TextInput. Adding
 * elevation to a View on Android makes the platform rebuild that view, which
 * destroyed the TextInput inside it the instant it took focus. The keyboard
 * opened, focus was lost in the same frame, and typing did nothing. Rio
 * reported it as "on the signin screen i can not enter anything" and it
 * reproduced from a clean launch every time.
 *
 * **Why this is a source test and not a render test.** The failure is a native
 * Android view-recycling behaviour. Jest renders to a mock host tree where
 * changing `elevation` remounts nothing at all, so a `@testing-library` test
 * asserting "the input is still focused" passes just as happily with the bug
 * present. A test that cannot fail proves nothing, so this asserts the rule
 * that was actually broken — a style that toggles with focus must not change
 * elevation — against the source, where it is checkable.
 *
 * The glow still exists. It moved to `styles.glow`, a sibling layer that is
 * never an ancestor of the TextInput, and its elevation is constant with only
 * `shadowColor` changing. That is why `glowOn` is checked too.
 */
const SOURCE = readFileSync(join(__dirname, 'authComponents.tsx'), 'utf8');

/** Pull one `name: { ... }` entry out of the StyleSheet.create block. */
function styleBlock(name: string): string {
  const start = SOURCE.indexOf(`${name}: {`);
  if (start === -1) throw new Error(`No style named "${name}" in authComponents.tsx`);

  let depth = 0;
  for (let i = SOURCE.indexOf('{', start); i < SOURCE.length; i++) {
    if (SOURCE[i] === '{') depth++;
    if (SOURCE[i] === '}') {
      depth--;
      if (depth === 0) return SOURCE.slice(start, i + 1);
    }
  }
  throw new Error(`Unbalanced braces reading "${name}"`);
}

describe('AuthField focus styling', () => {
  it.each(['wellFocused', 'glowOn'])(
    '%s does not change elevation — that rebuilds the view and kills the focused input',
    (name) => {
      expect(styleBlock(name)).not.toMatch(/\belevation\b/);
    },
  );

  it('keeps the glow on a layer that is not an ancestor of the TextInput', () => {
    // The glow View must be a sibling of `styles.well`, not a wrapper around
    // it. If someone moves it back to wrapping the field, this is the line
    // that says why they should not.
    const glow = styleBlock('glow');
    expect(glow).toMatch(/position: 'absolute'/);

    // And its elevation must be constant, so focus never rebuilds it either.
    expect(glow).toMatch(/\belevation\b/);
  });

  it('still renders a visible focus state, because removing the glow is not the fix', () => {
    // The site's own focused field takes the primary colour on its border.
    // Deleting the focus treatment altogether would "fix" the bug by making
    // the form unusable for anyone navigating with a remote or a keyboard.
    expect(styleBlock('wellFocused')).toMatch(/borderColor/);
  });
});
