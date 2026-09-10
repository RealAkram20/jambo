import type { ContinueWatchingCard } from '../../api/catalogue';
import { resumeTarget } from './resume';

/*
 * `Record<string, unknown>` rather than `Partial<ContinueWatchingCard>`,
 * because this project compiles with `exactOptionalPropertyTypes` and half
 * these cases exist precisely to pass a field as `undefined` — which that
 * setting forbids on an optional property. The cast is at the boundary of the
 * factory so the assertions themselves stay fully typed.
 */
function card(overrides: Record<string, unknown>): ContinueWatchingCard {
  return {
    title: 'The Viral Factor',
    image_url: 'https://example.test/still.jpg',
    progress_percent: 42,
    minutes_left: 51,
    position_seconds: 1512,
    resume: { type: 'movie', id: 1 },
    remove: { type: 'movie', id: 1 },
    ...overrides,
  } as ContinueWatchingCard;
}

describe('resuming from a Continue Watching card', () => {
  it('reads the movie the card points at', () => {
    expect(resumeTarget(card({}))).toEqual({ type: 'movie', id: 1, title: 'The Viral Factor' });
  });

  it('reads the EPISODE for a series, not the show', () => {
    /*
     * The distinction the contract is explicit about: `resume` is the episode
     * and `remove` is the show, because the rail dedupes by show. Reading
     * `remove` here would open the player on a series id as though it were an
     * episode id — a valid-looking request for the wrong title.
     */
    const item = card({
      title: 'Eternal Love of the Fox',
      subtitle: 'S01E02 · Itaque autem aut',
      resume: { type: 'episode', id: 26 },
      remove: { type: 'show', id: 3 },
    });

    expect(resumeTarget(item)).toEqual({
      type: 'episode',
      id: 26,
      title: 'Eternal Love of the Fox',
      subtitle: 'S01E02 · Itaque autem aut',
    });
  });

  it('gives no target when the card has no resume block', () => {
    // The card then renders inert, which is the path slice 2b used for every
    // card. Guessing an id would open the player on the wrong title.
    expect(resumeTarget(card({ resume: undefined }))).toBeNull();
  });

  it('gives no target when the id is missing or not a number', () => {
    expect(resumeTarget(card({ resume: { type: 'movie' } }))).toBeNull();
    expect(
      resumeTarget(card({ resume: { type: 'movie', id: undefined } })),
    ).toBeNull();
  });

  it('gives no target for a type the player cannot open', () => {
    /*
     * `show` is a valid value elsewhere in the contract — it is what `remove`
     * uses — so a card carrying it here must not be treated as an episode.
     *
     * The id is present ON PURPOSE. Without it this case was rejected by the
     * id guard instead, and the type guard was never exercised at all: a
     * mutation replacing it with `type === undefined` left the suite green.
     * A valid id is what forces the type check to be the thing that refuses.
     */
    expect(resumeTarget(card({ resume: { type: 'show', id: 3 } }))).toBeNull();
  });

  it('falls back to a name rather than rendering an empty title bar', () => {
    expect(resumeTarget(card({ title: undefined }))?.title).toBe('Untitled');
  });

  it('omits an empty subtitle rather than passing a blank line', () => {
    expect(resumeTarget(card({ subtitle: '' }))).not.toHaveProperty('subtitle');
  });
});
