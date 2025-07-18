import userEvent from '@testing-library/user-event';

import { render, screen, within } from '@/test';
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
    const timestampElement = within(listItem).getByText(/\d+\s+(second|minute|hour|day)s?\s+ago/i);
    expect(timestampElement).toHaveClass('text-green-500'); // !! active player timestamp in green
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
    const timestampElement = within(listItem).getByText(/\d+\s+(second|minute|hour|day)s?\s+ago/i);
    expect(timestampElement).toHaveClass('text-neutral-500'); // !! inactive player timestamp in neutral color
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

  it('given a rich presence message is truncated, clicking it expands the message', async () => {
    // ARRANGE
    const user = userEvent.setup();
    const richPresenceMessage =
      'Playing Stage 3 - Boss Fight with a very long message that should be truncated';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
      user: createUser({ displayName: 'TestUser' }),
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame({ title: 'Super Mario World' }),
        recentPlayers: [player],
      },
    });

    // ACT
    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    await user.click(richPresenceElement);

    // ASSERT
    expect(richPresenceElement).not.toHaveClass('truncate');
  });

  it('given a rich presence message is expanded, clicking it again collapses the message', async () => {
    // ARRANGE
    const user = userEvent.setup();
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight with a very long message';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
      user: createUser({ displayName: 'TestUser' }),
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame({ title: 'Super Mario World' }),
        recentPlayers: [player],
      },
    });

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ACT
    await user.click(richPresenceElement); // !! expand first
    await user.click(richPresenceElement); // !! then collapse

    // ASSERT
    expect(richPresenceElement).toHaveClass('truncate'); // !! back to truncated state
  });

  it('given a rich presence message, pressing Enter key expands/collapses it', async () => {
    // ARRANGE
    const user = userEvent.setup();
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
      user: createUser({ displayName: 'TestUser' }),
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame({ title: 'Super Mario World' }),
        recentPlayers: [player],
      },
    });

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ACT
    richPresenceElement.focus();
    await user.keyboard('{Enter}');

    // ASSERT
    expect(richPresenceElement).not.toHaveClass('truncate');
  });

  it('given a rich presence message, pressing Space key expands/collapses it', async () => {
    // ARRANGE
    const user = userEvent.setup();
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
      user: createUser({ displayName: 'TestUser' }),
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame({ title: 'Super Mario World' }),
        recentPlayers: [player],
      },
    });

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ACT
    richPresenceElement.focus();
    await user.keyboard(' ');

    // ASSERT
    expect(richPresenceElement).not.toHaveClass('truncate');
  });

  it('given a rich presence message, has proper accessibility attributes', () => {
    // ARRANGE
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
      user: createUser({ displayName: 'TestUser' }),
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame({ title: 'Super Mario World' }),
        recentPlayers: [player],
      },
    });

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ASSERT
    expect(richPresenceElement).toHaveAttribute('role', 'button'); // !! button role for screen readers
    expect(richPresenceElement).toHaveAttribute('aria-expanded', 'false'); // !! indicates collapsed state
    expect(richPresenceElement).toHaveAttribute(
      'aria-label',
      'Toggle rich presence details for TestUser',
    ); // !! descriptive label
    expect(richPresenceElement).toHaveAttribute('tabIndex', '0'); // !! keyboard focusable
  });

  it('given a rich presence message is expanded, aria-expanded attribute updates', async () => {
    // ARRANGE
    const user = userEvent.setup();
    const richPresenceMessage = 'Playing Stage 3 - Boss Fight';
    const player = createGameRecentPlayer({
      richPresence: richPresenceMessage,
      user: createUser({ displayName: 'TestUser' }),
    });

    render(<GameRecentPlayersList />, {
      pageProps: {
        game: createGame({ title: 'Super Mario World' }),
        recentPlayers: [player],
      },
    });

    const richPresenceElement = screen.getByRole('button', {
      name: /toggle rich presence details/i,
    });

    // ACT
    await user.click(richPresenceElement);

    // ASSERT
    expect(richPresenceElement).toHaveAttribute('aria-expanded', 'true');
  });
});
