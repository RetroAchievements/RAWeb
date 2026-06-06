import userEvent from '@testing-library/user-event';

import { render, screen, within } from '@/test';
import { createGame, createGameRecentPlayer, createUser } from '@/test/factories';

import { GameRecentPlayersTable } from './GameRecentPlayersTable';

describe('Component: GameRecentPlayersTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame(),
          recentPlayers: [],
        },
      },
    );

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

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame(),
          recentPlayers,
        },
      },
    );

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

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame(),
          recentPlayers: [activePlayer],
        },
      },
    );

    // ASSERT
    const table = screen.getByRole('table');
    const timestampElement = within(table).getByText(/\d+[hms]?\s+ago/i);
    expect(timestampElement).toHaveClass('text-green-500');
  });

  it('given a player is not active, shows their timestamp in neutral color', () => {
    // ARRANGE
    const inactivePlayer = createGameRecentPlayer({
      isActive: false,
      user: createUser({ displayName: 'InactiveUser' }),
    });

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame(),
          recentPlayers: [inactivePlayer],
        },
      },
    );

    // ASSERT
    const table = screen.getByRole('table');
    const timestampElement = within(table).getByText(/\d+[hms]?\s+ago/i);
    expect(timestampElement).toHaveClass('text-neutral-500');
  });

  it("given a player has a rich presence message, displays the player's rich presence", () => {
    // ARRANGE
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
    });

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ASSERT
    expect(screen.getByText(richPresenceMessage)).toBeVisible();
  });

  it('given isExpanded is false, the activity cell has line-clamp-1', () => {
    // ARRANGE
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ASSERT
    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });
    expect(richPresenceElement).toHaveClass('line-clamp-1');
  });

  it('given isExpanded is true, the activity cell does not have line-clamp-1', () => {
    // ARRANGE
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={true}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ASSERT
    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });
    expect(richPresenceElement).not.toHaveClass('line-clamp-1');
  });

  it('given an RP message is clicked, calls onToggleExpanded', async () => {
    // ARRANGE
    const onToggleExpanded = vi.fn();
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={onToggleExpanded}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ACT
    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });
    await userEvent.click(richPresenceElement);

    // ASSERT
    expect(onToggleExpanded).toHaveBeenCalledOnce();
  });

  it('given isExpanded is false, adds a title attribute for a tooltip', () => {
    // ARRANGE
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ASSERT
    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });
    expect(richPresenceElement).toHaveAttribute('title', richPresenceMessage);
  });

  it('given isExpanded is true, does not add a title attribute', () => {
    // ARRANGE
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={true}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ASSERT
    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });
    expect(richPresenceElement).not.toHaveAttribute('title');
  });

  it('renders table headers for accessibility', () => {
    // ARRANGE
    render(
      <GameRecentPlayersTable
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          game: createGame(),
          recentPlayers: [createGameRecentPlayer()],
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Player')).toBeInTheDocument();
    expect(screen.getByText('Last Seen')).toBeInTheDocument();
    expect(screen.getByText('Progress')).toBeInTheDocument();
    expect(screen.getByText('Activity')).toBeInTheDocument();
  });
});
