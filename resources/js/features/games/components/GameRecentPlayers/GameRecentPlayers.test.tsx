import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGame, createGameRecentPlayer } from '@/test/factories';

import { GameRecentPlayers } from './GameRecentPlayers';

describe('Component: GameRecentPlayers', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameRecentPlayers />, {
      pageProps: {
        game: createGame(),
        isRichPresenceExpanded: false,
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
        isRichPresenceExpanded: false,
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
        backingGame: createGame(),
        game: createGame(),
        isRichPresenceExpanded: false,
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
        backingGame: createGame(),
        game: createGame(),
        isRichPresenceExpanded: false,
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

  it('given an RP message is clicked, toggles its expansion state', async () => {
    // ARRANGE
    const recentPlayers = [createGameRecentPlayer({ richPresence: 'Playing a level' })];

    render(<GameRecentPlayers />, {
      pageProps: {
        backingGame: createGame({ title: 'Super Mario World' }),
        game: createGame(),
        isRichPresenceExpanded: false,
        recentPlayers,
      },
    });

    // ACT
    const rpButtons = screen.getAllByRole('button', {
      name: /toggle rich presence details/i,
    });
    await userEvent.click(rpButtons[0]);

    // ASSERT
    const allRpButtons = screen.getAllByRole('button', {
      name: /toggle rich presence details/i,
    });
    for (const button of allRpButtons) {
      expect(button).toHaveAttribute('aria-expanded', 'true');
    }
  });
});
