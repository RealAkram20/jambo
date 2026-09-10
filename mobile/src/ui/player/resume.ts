import type { ContinueWatchingCard } from '../../api/catalogue';

/**
 * Turning a Continue Watching card into somewhere the player can go.
 *
 * One function, used by both the home rail and the Continue Watching screen,
 * because the alternative is the same three lines in two files that drift.
 *
 * **This is why the player takes `{type, id}` and not a slug.** A
 * `ContinueWatchingCard` carries `resume: {type, id}` and no slug, and the
 * catalogue's detail endpoints are slug-only — `/movies/1` is a 404, checked
 * against the running API. Slice 2b therefore had to ship the card completely
 * inert: it had no honest action at all, not even "open the title's page".
 * `POST /playback/sessions` takes exactly this pair, so resume needs no server
 * change.
 *
 * Note `resume` is deliberately not `remove`. For a series, `resume` points at
 * the EPISODE and `remove` points at the SHOW, because the rail dedupes by
 * show and removing one episode would let the card come straight back. Reading
 * the wrong one here would resume the series id as though it were an episode.
 */
export type ResumeTarget = {
  type: 'movie' | 'episode';
  id: number;
  title: string;
  subtitle?: string;
};

export function resumeTarget(item: ContinueWatchingCard): ResumeTarget | null {
  const type = item.resume?.type;
  const id = item.resume?.id;

  /*
   * Both fields are optional in the generated types because the spec does not
   * mark them required, so this is a real check rather than a formality. A
   * card missing either one gets no action, and the component renders it inert
   * — the same path slice 2b used for every card. Guessing an id would open
   * the player on the wrong title.
   */
  if (type !== 'movie' && type !== 'episode') return null;
  if (typeof id !== 'number' || !Number.isFinite(id)) return null;

  const title = item.title ?? 'Untitled';
  const subtitle = item.subtitle;

  return {
    type,
    id,
    title,
    ...(typeof subtitle === 'string' && subtitle !== '' ? { subtitle } : {}),
  };
}
