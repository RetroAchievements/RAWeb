import { render, screen } from '@/test';
import { createGame, createGameRecentPlayer, createUser } from '@/test/factories';

import { GameRecentPlayersTable } from './GameRecentPlayersTable';

describe('Component: GameRecentPlayersTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameRecentPlayersTable />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are recent players, renders all of them in the table', () => {
    // ARRANGE
    const recentPlayers = [
      createGameRecentPlayer(),
      createGameRecentPlayer(),
      createGameRecentPlayer(),
    ];

    render(<GameRecentPlayersTable />, {
      pageProps: {
        game: createGame(),
        recentPlayers,
      },
    });

    // ASSERT
    const rows = screen.getAllByRole('row');
    expect(rows).toHaveLength(4); // 1 header row + 3 data rows
  });

  it('given a player is active, shows their timestamp in green', () => {
    // ARRANGE
    const activePlayer = createGameRecentPlayer({
      isActive: true,
      user: createUser({ displayName: 'ActiveUser' }),
    });

    render(<GameRecentPlayersTable />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [activePlayer],
      },
    });

    // ASSERT
    const timestampElement = screen.getByRole('table').querySelector('.text-green-500');
    expect(timestampElement).toBeVisible();
  });

  it('given a player is not active, shows their timestamp in neutral color', () => {
    // ARRANGE
    const inactivePlayer = createGameRecentPlayer({
      isActive: false,
      user: createUser({ displayName: 'InactiveUser' }),
    });

    render(<GameRecentPlayersTable />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [inactivePlayer],
      },
    });

    // ASSERT
    const timestampElement = screen.getByRole('table').querySelector('.text-neutral-500');
    expect(timestampElement).toBeVisible();
  });

  it("given a player has a rich presence message, displays the player's rich presence with tooltip", () => {
    // ARRANGE
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
    });

    render(<GameRecentPlayersTable />, {
      pageProps: {
        game: createGame({ title: 'Super Mario World' }),
        recentPlayers: [player],
      },
    });

    // ASSERT
    expect(screen.getByText(richPresenceMessage)).toBeVisible();

    const richPresenceElement = screen.getByTitle(richPresenceMessage);
    expect(richPresenceElement).toBeVisible();
  });

  it('renders table headers for accessibility', () => {
    // ARRANGE
    render(<GameRecentPlayersTable />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [createGameRecentPlayer()],
      },
    });

    // ASSERT
    expect(screen.getByText(/player/i)).toBeInTheDocument();
    expect(screen.getByText(/last seen/i)).toBeInTheDocument();
    expect(screen.getByText(/progress/i)).toBeInTheDocument();
    expect(screen.getByText(/activity/i)).toBeInTheDocument();
  });
});
