import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { GameBreadcrumbs } from './GameBreadcrumbs';

describe('Component: GameBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameBreadcrumbs t_currentPageLabel="Some Page" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the All Games list', () => {
    // ARRANGE
    render(<GameBreadcrumbs t_currentPageLabel="Some Page" />);

    // ASSERT
    const allGamesLinkEl = screen.getByRole('link', { name: /all games/i });
    expect(allGamesLinkEl).toBeVisible();
    expect(allGamesLinkEl).toHaveAttribute('href', 'game.index');
  });

  it('given a system, has a link to the system games list', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(<GameBreadcrumbs t_currentPageLabel="Some Page" game={game} system={system} />);

    // ASSERT
    const systemGamesLinkEl = screen.getByRole('link', { name: /nintendo 64/i });
    expect(systemGamesLinkEl).toBeVisible();
    expect(systemGamesLinkEl).toHaveAttribute('href', `system.game.index,${system.id}`);
  });

  it('given a game, has a link to the game page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(<GameBreadcrumbs t_currentPageLabel="Some Page" game={game} system={system} />);

    // ASSERT
    const gameLinkEl = screen.getByRole('link', { name: game.title });
    expect(gameLinkEl).toBeVisible();
    expect(gameLinkEl).toHaveAttribute('href', `game.show,${{ game: game.id }}`);
  });

  it('stylizes tags that are within game titles', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ title: '~Hack~ Super Junkoid' });

    render(<GameBreadcrumbs currentPageLabel="Some Page" game={game} system={system} />);

    // ASSERT
    expect(screen.queryByText('~')).not.toBeInTheDocument();

    const hackEl = screen.getByText(/hack/i);
    expect(hackEl).toBeVisible();
    expect(hackEl.nodeName).toEqual('SPAN');
  });
});
