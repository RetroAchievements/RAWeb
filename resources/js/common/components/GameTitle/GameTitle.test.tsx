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
});
