import { render, screen } from '@/test';
import { createGame, createGameRecentPlayer, createUser } from '@/test/factories';

import { GameRecentPlayersList } from './GameRecentPlayersList';

describe('Component: GameRecentPlayersList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are recent players, renders all of them in the list', () => {
    // ARRANGE
    const recentPlayers = [
      createGameRecentPlayer(),
      createGameRecentPlayer(),
      createGameRecentPlayer(),
    ];

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame(),
        recentPlayers,
      },
    });

    // ASSERT
    const listItems = screen.getAllByRole('listitem');
    expect(listItems).toHaveLength(3);
  });

  it('given a player is active, shows their timestamp in green', () => {
    // ARRANGE
    const activePlayer = createGameRecentPlayer({
      isActive: true,
      user: createUser({ displayName: 'ActiveUser' }),
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [activePlayer],
      },
    });

    // ASSERT
    const listItem = screen.getByRole('listitem');
    const timestampElement = listItem.querySelector('.text-green-500');
    expect(timestampElement).toBeVisible();
  });

  it('given a player is not active, shows their timestamp in neutral color', () => {
    // ARRANGE
    const inactivePlayer = createGameRecentPlayer({
      isActive: false,
      user: createUser({ displayName: 'InactiveUser' }),
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame(),
        recentPlayers: [inactivePlayer],
      },
    });

    // ASSERT
    const listItem = screen.getByRole('listitem');
    const timestampElement = listItem.querySelector('.text-neutral-500');
    expect(timestampElement).toBeVisible();
  });

  it("given a player has a rich presence message, displays the player's rich presence", () => {
    // ARRANGE
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame({ title: 'Super Mario World' }),
        recentPlayers: [player],
      },
    });

    // ASSERT
    expect(screen.getByText(richPresenceMessage)).toBeVisible();
  });
});
