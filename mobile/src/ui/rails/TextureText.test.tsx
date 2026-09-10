import { act, render } from '@testing-library/react-native';

import { TextureText } from './TextureText';

/**
 * The first render test in this codebase, and it earns the precedent.
 *
 * `TextureText` has a failure mode no other component here has: it draws
 * nothing at all until React Native hands back a text measurement, so a wiring
 * mistake does not look like a bug, it looks like a missing film title. Every
 * other test in `mobile/` is a pure function because every other risk here is
 * arithmetic. This one is a handoff between two render passes, and the only
 * way to ask whether the handoff happens is to render it.
 *
 * What this cannot answer is whether the native side reports the *truncated*
 * line in `lines[].text`. That is the platform's answer rather than React's
 * and it needs a device. The `onLayout` clamp exists for exactly that
 * uncertainty, and the last test here is what covers it.
 *
 * The assertions reach for host component names — `RNSVGText`, and the
 * `content` prop on the `RNSVGTSpan` beneath it — because that is where
 * `react-native-svg` actually puts the drawn string. They were read off a
 * rendered tree rather than guessed, and they are the reason this file asserts
 * on internals at all: there is no accessible text to query, by design, since
 * the painted glyphs are decorative and the measuring pass is what a screen
 * reader sees.
 */

const LINE = {
  text: 'A Film',
  width: 120,
  height: 30,
  ascender: 24,
  descender: 6,
  capHeight: 20,
  xHeight: 12,
  x: 0,
  y: 0,
};

type Line = typeof LINE;

async function renderTitle(text = 'A Film') {
  const view = await render(
    <TextureText size={25} weight={800} tracking={1.56} fontFamily="Roboto" numberOfLines={1}>
      {text}
    </TextureText>,
  );

  const byType = (type: string) => view.root?.queryAll((node) => node.type === type) ?? [];

  /** What RN reports after laying the invisible measuring text out. */
  const measure = async (lines: Line[]) => {
    const target = byType('Text')[0];
    await act(async () => {
      target?.props.onTextLayout({ nativeEvent: { lines } });
    });
  };

  /** The width the line box got. `view.root` is the component's own View. */
  const resize = async (width: number) => {
    await act(async () => {
      view.root?.props.onLayout({ nativeEvent: { layout: { x: 0, y: 0, width, height: 30 } } });
    });
  };

  /** The string actually painted, which lives on the tspan. */
  const paintedText = () =>
    byType('RNSVGText').map((node) => (node.children[0] as { props?: { content?: string } })?.props?.content);

  const canvasWidth = () => byType('RNSVGSvgView')[0]?.props.width;
  const baseline = () => byType('RNSVGText')[0]?.props.y;
  const measuringText = () => byType('Text')[0];

  return { ...view, measure, resize, paintedText, canvasWidth, baseline, measuringText };
}

describe('TextureText', () => {
  it('draws no SVG text before it has been measured', async () => {
    const { paintedText } = await renderTitle();

    expect(paintedText()).toHaveLength(0);
  });

  it('keeps the string laid out while it is invisible, or it never measures', async () => {
    // The measuring pass is `opacity: 0`, NOT unmounted and NOT
    // `display: none`. A text node that is not laid out reports no lines and
    // the effect never starts, so this asserts the thing whose removal would
    // look like tidying up.
    //
    // Queried by host type rather than by text: the node is deliberately
    // `accessible={false}` and hidden from assistive technology, so RNTL's
    // text queries skip it. That it is hidden from a screen reader and still
    // laid out for the layout engine is exactly the property under test.
    const { measuringText } = await renderTitle('A Film');

    const node = measuringText();
    expect(node?.children).toEqual(['A Film']);
    expect(node?.props.style).toContainEqual({ opacity: 0 });
    expect(node?.props.style).not.toContainEqual(expect.objectContaining({ display: 'none' }));
  });

  it('paints the measured line once React Native reports it', async () => {
    const { measure, paintedText } = await renderTitle();

    await measure([LINE]);

    expect(paintedText()).toEqual(['A Film']);
  });

  it('paints the string RN reports, not the string it was given', async () => {
    // The whole point of measuring. When the line is truncated, the ellipsised
    // string is what must be drawn — painting `children` instead overruns the
    // box on every long title, and film titles are long.
    const { measure, paintedText } = await renderTitle('A Very Long Film Title That Cannot Fit');

    await measure([{ ...LINE, text: 'A Very Long Film…' }]);

    expect(paintedText()).toEqual(['A Very Long Film…']);
  });

  it('hangs the line from the baseline RN reported, not from the top of its box', async () => {
    const { measure, baseline } = await renderTitle();

    await measure([LINE]);

    // SVG text sits on its baseline, and `y` is an array of per-line values.
    // Using the line height (30) here would drop every glyph by its descender.
    expect(baseline()).toEqual([LINE.ascender]);
  });

  it('stacks a wrapped headline by the heights RN reported', async () => {
    const { measure, paintedText } = await renderTitle('Two Lines');

    await measure([
      { ...LINE, text: 'Two', height: 30, ascender: 24 },
      { ...LINE, text: 'Lines', height: 30, ascender: 24 },
    ]);

    expect(paintedText()).toEqual(['Two', 'Lines']);
  });

  it('never paints wider than the box the text was laid out in', async () => {
    const { measure, resize, canvasWidth, paintedText } = await renderTitle();

    // A line reported wider than its container: what an RN build that measures
    // before applying `numberOfLines` would hand back. The clamp turns that
    // into a clipped tail instead of a headline lying across the artwork.
    await resize(200);
    await measure([{ ...LINE, width: 900 }]);

    expect(canvasWidth()).toBeLessThanOrEqual(200);
    expect(paintedText()).toHaveLength(1);
  });

  it('sizes itself to the glyphs when nothing has reported a box', async () => {
    // No onLayout — the clamp must not collapse the canvas to zero.
    const { measure, canvasWidth } = await renderTitle();

    await measure([LINE]);

    expect(canvasWidth()).toBeGreaterThanOrEqual(LINE.width);
  });
});
