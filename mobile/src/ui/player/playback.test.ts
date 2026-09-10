import type { Season } from '../../api/catalogue';
import {
  clampPosition,
  episodeLabel,
  episodesInOrder,
  formatTime,
  nextEpisodeAfter,
  preferredRendition,
  progressFraction,
  renditionLabel,
  resumeStartMs,
  seekTargetMs,
  usableRendition,
} from './playback';

/*
 * A two-season series, deliberately given to the helpers OUT OF ORDER —
 * season 2 before season 1, and episode 3 before episode 2 inside it. The
 * endpoint returns whatever the database returns, and an admin who adds a
 * missing episode later produces exactly this shape. A fixture already in
 * watch order would let a broken sort pass.
 */
const seasons = [
  {
    id: 6,
    number: 2,
    episodes: [
      { type: 'episode', id: 31, number: 1, title: 'Est nihil et' },
      { type: 'episode', id: 32, number: 2, title: 'Quo dolor est' },
    ],
  },
  {
    id: 5,
    number: 1,
    episodes: [
      { type: 'episode', id: 25, number: 1, title: 'Vitae et suscipit' },
      { type: 'episode', id: 27, number: 3, title: 'Doloremque' },
      { type: 'episode', id: 26, number: 2, title: 'Itaque autem aut' },
    ],
  },
] as unknown as Season[];

describe('which rendition to ask for', () => {
  it('maps the account preference onto the two files that exist', () => {
    expect(preferredRendition('high', false)).toBe('default');
    expect(preferredRendition('data_saver', false)).toBe('low');
    // data_saver stays low on wifi: it is a choice, not a connection guess.
    expect(preferredRendition('data_saver', true)).toBe('low');
  });

  it('auto follows the connection, and cellular means low', () => {
    expect(preferredRendition('auto', true)).toBe('low');
    expect(preferredRendition('auto', false)).toBe('default');
  });

  it('treats an unknown preference as auto rather than as full quality', () => {
    // A newer server sending a value this build does not know. Guessing
    // `default` on a metered connection is the expensive way to be wrong.
    expect(preferredRendition('ultra' as never, true)).toBe('low');
    expect(preferredRendition(null, true)).toBe('low');
    expect(preferredRendition(undefined, false)).toBe('default');
  });

  it('falls back to default when the title has no low rendition', () => {
    expect(usableRendition('low', ['default'])).toBe('default');
    expect(usableRendition('low', ['default', 'low'])).toBe('low');
    expect(usableRendition('default', ['default'])).toBe('default');
  });

  it('keeps the wanted rendition when availability is not known yet', () => {
    // The first request cannot know: available_qualities arrives in the very
    // response being asked for.
    expect(usableRendition('low', null)).toBe('low');
    expect(usableRendition('low', [])).toBe('low');
  });

  it('names the renditions the way the site does', () => {
    expect(renditionLabel('low')).toBe('Data saver');
    expect(renditionLabel('default')).toBe('Default');
  });
});

describe('where a resume starts', () => {
  it('resumes where the viewer left off', () => {
    expect(resumeStartMs(12, 52_000)).toBe(12_000);
  });

  it('starts from the beginning when the stored position is past the media', () => {
    // The real case: seeded history says 1512s for a 52s fixture. Seeking
    // there parks the player on the last frame with nothing to play.
    expect(resumeStartMs(1512, 52_000)).toBe(0);
  });

  it('starts from the beginning within the last fifteen seconds', () => {
    // Resuming three seconds before the credits is not resuming. The boundary
    // for a 52s title is 37s, and it is pinned on both sides so a change to
    // the window has to be deliberate rather than incidental.
    expect(resumeStartMs(50, 52_000)).toBe(0);
    expect(resumeStartMs(37, 52_000)).toBe(0);
    expect(resumeStartMs(36, 52_000)).toBe(36_000);
  });

  it('trusts the position while the duration is still unknown', () => {
    expect(resumeStartMs(90, 0)).toBe(90_000);
  });

  it('treats a missing or nonsense position as the beginning', () => {
    expect(resumeStartMs(null, 52_000)).toBe(0);
    expect(resumeStartMs(undefined, 52_000)).toBe(0);
    expect(resumeStartMs(-5, 52_000)).toBe(0);
    expect(resumeStartMs(Number.NaN, 52_000)).toBe(0);
  });
});

describe('clamping a position', () => {
  it('keeps a seek inside the media', () => {
    expect(clampPosition(80_000, 52_000)).toBe(52_000);
    expect(clampPosition(-4_000, 52_000)).toBe(0);
    expect(clampPosition(10_500, 52_000)).toBe(10_500);
  });

  it('does not clamp before the duration is known', () => {
    expect(clampPosition(10_000, 0)).toBe(10_000);
  });
});

describe('the clock', () => {
  it('matches the shape the website renders', () => {
    expect(formatTime(0)).toBe('0:00');
    expect(formatTime(5_000)).toBe('0:05');
    expect(formatTime(65_000)).toBe('1:05');
    expect(formatTime(600_000)).toBe('10:00');
    expect(formatTime(3_723_000)).toBe('1:02:03');
  });

  it('renders a zero rather than throwing on an unknown duration', () => {
    // Every stream has an unknown duration for its first moments. A player
    // that crashes on its own loading state is worse than one showing 0:00.
    expect(formatTime(Number.NaN)).toBe('0:00');
    expect(formatTime(-1)).toBe('0:00');
    expect(formatTime(Number.POSITIVE_INFINITY)).toBe('0:00');
  });
});

describe('the next episode', () => {
  it('puts a series into watch order regardless of the order it arrived in', () => {
    expect(episodesInOrder(seasons).map((e) => e.id)).toEqual([25, 26, 27, 31, 32]);
  });

  it('follows an episode within its season', () => {
    expect(nextEpisodeAfter(seasons, 25)?.id).toBe(26);
  });

  it('crosses the season boundary', () => {
    // The case a one-season fixture cannot exercise: the last episode of
    // season one is followed by the first of season two, not by nothing.
    expect(nextEpisodeAfter(seasons, 27)?.id).toBe(31);
    expect(nextEpisodeAfter(seasons, 27)?.seasonNumber).toBe(2);
  });

  it('offers nothing after the last episode', () => {
    expect(nextEpisodeAfter(seasons, 32)).toBeNull();
  });

  it('offers nothing for an episode that is not in the series', () => {
    // Never the pilot. A viewer whose episode has been removed should be
    // offered nothing rather than silently sent back to the beginning.
    expect(nextEpisodeAfter(seasons, 9999)).toBeNull();
  });

  it('survives a series with no seasons', () => {
    expect(nextEpisodeAfter(null, 25)).toBeNull();
    expect(nextEpisodeAfter([], 25)).toBeNull();
    expect(episodesInOrder(undefined)).toEqual([]);
  });

  it('labels an episode the way the Continue Watching card does', () => {
    expect(episodeLabel({ id: 31, seasonNumber: 2, episodeNumber: 1, title: 'Est nihil et' })).toBe(
      'S02E01 · Est nihil et',
    );
    expect(episodeLabel({ id: 31, seasonNumber: 2, episodeNumber: 1, title: '' })).toBe('S02E01');
  });
});

describe('the seek bar', () => {
  it('reports how far along the film is', () => {
    expect(progressFraction(26_000, 52_000)).toBe(0.5);
    expect(progressFraction(0, 52_000)).toBe(0);
    expect(progressFraction(60_000, 52_000)).toBe(1);
  });

  it('reports zero rather than Infinity before the duration is known', () => {
    // position / 0 is Infinity, which becomes a flex value of Infinity and a
    // layout that never recovers.
    expect(progressFraction(10_000, 0)).toBe(0);
    expect(Number.isFinite(progressFraction(10_000, 0))).toBe(true);
  });

  it('turns a touch on the track into a position', () => {
    expect(seekTargetMs(0, 300, 52_000)).toBe(0);
    expect(seekTargetMs(150, 300, 52_000)).toBe(26_000);
    expect(seekTargetMs(300, 300, 52_000)).toBe(52_000);
  });

  it('clamps a drag that leaves the track on either side', () => {
    // A finger travelling past the end of the bar is normal, not an error.
    expect(seekTargetMs(-40, 300, 52_000)).toBe(0);
    expect(seekTargetMs(420, 300, 52_000)).toBe(52_000);
  });

  it('returns zero before the track has been measured', () => {
    expect(seekTargetMs(50, 0, 52_000)).toBe(0);
    expect(seekTargetMs(50, 300, 0)).toBe(0);
  });
});
