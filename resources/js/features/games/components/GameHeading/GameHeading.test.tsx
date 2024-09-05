import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { GameHeading } from './GameHeading';

describe('Component: GameHeading', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameHeading game={createGame()}>Hello, World</GameHeading>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays a clickable avatar of the given game', () => {
    // ARRANGE
    const game = createGame();

    render(<GameHeading game={game}>Hello, World</GameHeading>);

    // ASSERT
    const linkEl = screen.getByRole('link');
    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', `game.show,${{ game: game.id }}`);

    expect(screen.getByRole('img', { name: game.title })).toBeVisible();
  });

  it('displays an accessible header from `children`', () => {
    // ARRANGE
    const game = createGame();

    render(<GameHeading game={game}>Hello, World</GameHeading>);

    // ASSERT
    expect(screen.getByRole('heading', { name: /hello, world/i })).toBeVisible();
  });
});
