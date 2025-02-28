/* eslint-disable testing-library/no-container */

import { render, screen } from '@/test';

import { GameTitle } from './GameTitle';

describe('Component: GameTitle', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameTitle title="Super Mario Bros." />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it("doesn't explode when given tags", () => {
    // ARRANGE
    render(<GameTitle title="~Hack~ Celeste SMC" />);

    // ASSERT
    expect(screen.getByText(/hack/i)).toBeVisible();
    expect(screen.getByText(/celeste smc/i)).toBeVisible();
  });

  it("doesn't explode when given a subset", () => {
    // ARRANGE
    render(<GameTitle title="Super Mario Bros. [Subset - Bonus]" />);

    // ASSERT
    expect(screen.getByText(/super mario bros/i)).toBeVisible();
    expect(screen.getByText(/bonus/i)).toBeVisible();
  });

  it("doesn't crash when encountering a symbol", () => {
    // ARRANGE
    render(<GameTitle title="Super Mario Sunshine [Subset - Max% Pre-Peach]" />);

    // ASSERT
    expect(screen.getByText(/super mario sunshine/i)).toBeVisible();
    expect(screen.getByText(/max% pre-peach/i)).toBeVisible();
  });

  it('renders multiple non-subset tags correctly', () => {
    // ARRANGE
    render(<GameTitle title="~Prototype~ ~Hack~ Biohazard 2" />);

    // ASSERT
    expect(screen.getByText(/prototype/i)).toBeVisible();
    expect(screen.getByText(/hack/i)).toBeVisible();
    expect(screen.getByText(/biohazard 2/i)).toBeVisible();
  });

  it('renders tags and subset labels simultaneously', () => {
    // ARRANGE
    render(<GameTitle title="~Prototype~ ~Hack~ Biohazard 2 [Subset - Bonus]" />);

    // ASSERT
    expect(screen.getByText(/prototype/i)).toBeVisible();
    expect(screen.getByText(/hack/i)).toBeVisible();
    expect(screen.getByText(/biohazard 2/i)).toBeVisible();
    expect(screen.getByText(/subset/i)).toBeVisible();
    expect(screen.getByText(/bonus/i)).toBeVisible();
  });

  it('given showTags is false, does not render tags', () => {
    // ARRANGE
    render(<GameTitle title="~Prototype~ ~Hack~ Biohazard 2" showTags={false} />);

    // ASSERT
    expect(screen.queryByText(/prototype/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/hack/i)).not.toBeInTheDocument();
  });

  it('renders title as a single text node when isWordWrappingEnabled is false', () => {
    // ARRANGE
    const { container } = render(<GameTitle title="Super Mario Bros." />);

    // ASSERT
    // The title should be directly in the span without word wrapping elements.
    const mainSpan = container.firstChild;
    expect(mainSpan?.textContent).toEqual('Super Mario Bros.');

    // Check that there are no inline spans for individual words.
    const inlineSpans = container.querySelectorAll('.inline');
    expect(inlineSpans.length).toBe(0);
  });

  it('splits the title into separate word elements when isWordWrappingEnabled is true', () => {
    // ARRANGE
    const { container } = render(
      <GameTitle title="Super Mario Bros." isWordWrappingEnabled={true} />,
    );

    // ASSERT
    // Each word should be in its own inline span.
    const inlineSpans = container.querySelectorAll('.inline');
    expect(inlineSpans.length).toEqual(3); // "Super", "Mario", "Bros."

    expect(inlineSpans[0].textContent).toBe('Super');
    expect(inlineSpans[1].textContent).toBe('Mario');
    expect(inlineSpans[2].textContent).toBe('Bros.');
  });

  it('maintains tag rendering when using word wrapping', () => {
    // ARRANGE
    render(<GameTitle title="Super Mario Bros. [Subset - Bonus]" isWordWrappingEnabled={true} />);

    // ASSERT
    const spans = screen.getAllByText(/super|mario|bros/i);
    expect(spans.length).toEqual(3);

    expect(screen.getByText(/bonus/i)).toBeVisible();
    expect(screen.getByText(/subset/i)).toBeVisible();
  });
});
