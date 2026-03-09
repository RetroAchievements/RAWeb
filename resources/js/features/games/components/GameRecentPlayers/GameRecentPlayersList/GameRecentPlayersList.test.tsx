import userEvent from '@testing-library/user-event';

import { render, screen, within } from '@/test';
import { createGame, createGameRecentPlayer, createUser } from '@/test/factories';

import { GameRecentPlayersList } from './GameRecentPlayersList';

describe('Component: GameRecentPlayersList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          recentPlayers: [],
        },
      },
    );

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

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          recentPlayers,
        },
      },
    );

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

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          recentPlayers: [activePlayer],
        },
      },
    );

    // ASSERT
    const listItem = screen.getByRole('listitem');
    const timestampElement = within(listItem).getByText(/\d+\s+(second|minute|hour|day)s?\s+ago/i);
    expect(timestampElement).toHaveClass('text-green-500');
  });

  it('given a player is not active, shows their timestamp in neutral color', () => {
    // ARRANGE
    const inactivePlayer = createGameRecentPlayer({
      isActive: false,
      user: createUser({ displayName: 'InactiveUser' }),
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame(),
          recentPlayers: [inactivePlayer],
        },
      },
    );

    // ASSERT
    const listItem = screen.getByRole('listitem');
    const timestampElement = within(listItem).getByText(/\d+\s+(second|minute|hour|day)s?\s+ago/i);
    expect(timestampElement).toHaveClass('text-neutral-500');
  });

  it("given a player has a rich presence message, displays the player's rich presence", () => {
    // ARRANGE
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ASSERT
    expect(screen.getByText(richPresenceMessage)).toBeVisible();
  });

  it('given isExpanded is false, RP elements have the truncate class', () => {
    // ARRANGE
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ASSERT
    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });
    expect(richPresenceElement).toHaveClass('truncate');
  });

  it('given isExpanded is true, RP elements do not have the truncate class', () => {
    // ARRANGE
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={true}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    // ASSERT
    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });
    expect(richPresenceElement).not.toHaveClass('truncate');
  });

  it('given an RP message is clicked, calls onToggleExpanded', async () => {
    // ARRANGE
    const onToggleExpanded = vi.fn();
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={onToggleExpanded}
      />,
      {
        pageProps: {
          backingGame: createGame({ title: 'Super Mario World' }),
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

  it('given the Enter key is pressed, calls onToggleExpanded', async () => {
    // ARRANGE
    const onToggleExpanded = vi.fn();
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={onToggleExpanded}
      />,
      {
        pageProps: {
          backingGame: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ACT
    richPresenceElement.focus();
    await userEvent.keyboard('{Enter}');

    // ASSERT
    expect(onToggleExpanded).toHaveBeenCalledOnce();
  });

  it('given the Space key is pressed, calls onToggleExpanded', async () => {
    // ARRANGE
    const onToggleExpanded = vi.fn();
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={onToggleExpanded}
      />,
      {
        pageProps: {
          backingGame: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ACT
    richPresenceElement.focus();
    await userEvent.keyboard(' ');

    // ASSERT
    expect(onToggleExpanded).toHaveBeenCalledOnce();
  });

  it('given isExpanded is false, has proper accessibility attributes', () => {
    // ARRANGE
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={false}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ASSERT
    expect(richPresenceElement).toHaveAttribute('aria-expanded', 'false');
    expect(richPresenceElement).toHaveAttribute('aria-label', 'Toggle rich presence details');
  });

  it('given isExpanded is true, aria-expanded attribute reflects the expanded state', () => {
    // ARRANGE
    const player = createGameRecentPlayer({
      richPresence: 'Playing Stage 3 - Boss Fight',
      user: createUser({ displayName: 'TestUser' }),
    });

    render(
      <GameRecentPlayersList
        canToggleExpanded={true}
        isExpanded={true}
        onToggleExpanded={vi.fn()}
      />,
      {
        pageProps: {
          backingGame: createGame({ title: 'Super Mario World' }),
          recentPlayers: [player],
        },
      },
    );

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ASSERT
    expect(richPresenceElement).toHaveAttribute('aria-expanded', 'true');
  });
});
