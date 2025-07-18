import { render, screen } from '@/test';
import { createGame, createGameRecentPlayer } from '@/test/factories';

import { GameRecentPlayers } from './GameRecentPlayers';

describe('Component: GameRecentPlayers', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameRecentPlayers />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no recent players, renders nothing', () => {
    // ARRANGE
    render(<GameRecentPlayers />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /recent players/i })).not.toBeInTheDocument();
  });

  it('given there are recent players, renders the section heading', () => {
    // ARRANGE
    const recentPlayers = [createGameRecentPlayer()];

    render(<GameRecentPlayers />, {
      pageProps: {
        game: createGame(),
        recentPlayers,
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /recent players/i })).toBeVisible();
  });

  it('given there are recent players, renders both list and table components', () => {
    // ARRANGE
    const recentPlayers = [createGameRecentPlayer(), createGameRecentPlayer()];

    render(<GameRecentPlayers />, {
      pageProps: {
        game: createGame(),
        recentPlayers,
      },
    });

    // ASSERT
    const listItems = screen.getAllByRole('listitem');
    expect(listItems).toHaveLength(2);

    const table = screen.getByRole('table');
    expect(table).toBeVisible();

    const rows = screen.getAllByRole('row');
    expect(rows).toHaveLength(3); // 1 header + 2 data rows
  });
});
