import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { SidebarManagementSection } from './SidebarManagementSection';

describe('Component: SidebarManagementSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SidebarManagementSection game={createGame()} />, {
      pageProps: {
        backingGame: createGame(),
        can: {},
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user can manage games, displays the Management section', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGames: true }, // !!
      },
    });

    // ASSERT
    expect(screen.getByText(/management/i)).toBeVisible();
  });

  it('given the user can manage games and create game forum topics and the backing game has no forum topic, shows a create new forum topic button', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame({ forumTopicId: undefined })} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ forumTopicId: undefined }), // !!
        can: { manageGames: true, createGameForumTopic: true }, // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /create new forum topic/i })).toBeVisible();
  });

  it('given the user can manage games and create game forum topics and the game already has a forum topic, does not show a create new forum topic button', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame({ forumTopicId: 123 })} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ forumTopicId: 123 }), // !!
        can: { manageGames: true, createGameForumTopic: true },
      },
    });

    // ASSERT
    expect(
      screen.queryByRole('button', { name: /create new forum topic/i }),
    ).not.toBeInTheDocument();
  });

  it('given only the updateGame permission is truthy, shows the Management section', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { updateGame: true }, // !!
      },
    });

    // ASSERT
    expect(screen.getByText(/management/i)).toBeVisible();
  });

  it('given only the manageGameHashes permission is truthy, shows the Management section', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGameHashes: true }, // !!
      },
    });

    // ASSERT
    expect(screen.getByText(/management/i)).toBeVisible();
  });

  it('given only the updateAnyAchievementSetClaim permission is truthy, shows the Management section', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { updateAnyAchievementSetClaim: true }, // !!
      },
    });

    // ASSERT
    expect(screen.getByText(/management/i)).toBeVisible();
  });

  it('given the user can manage games and update this game, shows an Edit Game Details link that opens in a new tab', () => {
    // ARRANGE
    const game = createGame({ id: 123 });

    render(<SidebarManagementSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ id: 123 }),
        can: { manageGames: true, updateGame: true }, // !!
      },
    });

    // ASSERT
    const link = screen.getByRole('link', { name: /edit game details/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', '/manage/games/123/edit');
    expect(link).toHaveAttribute('target', '_blank');
  });

  it('given the user can manage games but not update this game, shows a View Game Details link', () => {
    // ARRANGE
    const game = createGame({ id: 456 });

    render(<SidebarManagementSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ id: 456 }),
        can: { manageGames: true, updateGame: false }, // !!
      },
    });

    // ASSERT
    const link = screen.getByRole('link', { name: /view game details/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', '/manage/games/456');
    expect(link).toHaveAttribute('target', '_blank');
  });

  it('given the user cannot manage games, does not show the Game Details button', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGames: false, updateGame: true }, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /game details/i })).not.toBeInTheDocument();
  });

  it('given only the manageGameHashes permission is truthy, shows the Manage Hashes link', () => {
    // ARRANGE
    const game = createGame({ id: 789 });

    render(<SidebarManagementSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ id: 789 }),
        can: { manageGameHashes: true }, // !!
      },
    });

    // ASSERT
    const link = screen.getByRole('link', { name: /manage hashes/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', '/manage/games/789/hashes');
  });

  it('given both the manageGameHashes and updateAnyAchievementSetClaim permissions are truthy, shows shorter Hashes and Claims button labels', () => {
    // ARRANGE
    const game = createGame({ id: 123 });

    render(<SidebarManagementSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ id: 123 }),
        can: { manageGameHashes: true, updateAnyAchievementSetClaim: true }, // !!
      },
    });

    // ASSERT
    const hashesLink = screen.getByRole('link', { name: /^hashes$/i });
    expect(hashesLink).toBeVisible();
    expect(hashesLink).toHaveTextContent('Hashes');
    expect(hashesLink).not.toHaveTextContent('Manage Hashes');

    const claimsLink = screen.getByRole('link', { name: /^claims$/i });
    expect(claimsLink).toBeVisible();
    expect(claimsLink).toHaveTextContent('Claims');
    expect(claimsLink).not.toHaveTextContent('Manage Claims');
  });

  it('given the manageGameHashes permission is false, does not show Manage Hashes link', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGameHashes: false }, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /hashes/i })).not.toBeInTheDocument();
  });

  it('given only the updateAnyAchievementSetClaim permission is truthy, shows the Manage Claims link', () => {
    // ARRANGE
    const game = createGame({ id: 999 });

    render(<SidebarManagementSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ id: 999 }),
        can: { updateAnyAchievementSetClaim: true }, // !!
      },
    });

    // ASSERT
    const link = screen.getByRole('link', { name: /manage claims/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', '/manageclaims.php?g=999');
  });

  it('given the updateAnyAchievementSetClaim permission is false, does not show the Manage Claims link', () => {
    // ARRANGE
    render(<SidebarManagementSection game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { updateAnyAchievementSetClaim: false }, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /claims/i })).not.toBeInTheDocument();
  });

  it('given the game and backing game are different, shows subset indicators on Hashes and Claims buttons', () => {
    // ARRANGE
    const game = createGame({ id: 1 }); // !!
    const backingGame = createGame({ id: 2 }); // !!

    render(<SidebarManagementSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        can: { manageGames: true, manageGameHashes: true, updateAnyAchievementSetClaim: true }, // !!
      },
    });

    // ASSERT
    expect(screen.getAllByRole('img', { name: /subset/i })).toHaveLength(3);
  });

  it('given the game and backing game are the same, does not show subset indicators on Hashes and Claims buttons', () => {
    // ARRANGE
    const game = createGame({ id: 1 }); // !!
    const backingGame = createGame({ id: 1 }); // !!

    render(<SidebarManagementSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        can: { manageGameHashes: true, updateAnyAchievementSetClaim: true }, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('img', { name: /subset/i })).not.toBeInTheDocument();
  });

  it('given the user is viewing a subset, shows base game and subset game detail buttons', () => {
    // ARRANGE
    const game = createGame({ id: 1 }); // !!
    const backingGame = createGame({ id: 2 }); // !!

    render(<SidebarManagementSection game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        can: { manageGames: true, updateGame: true }, // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /edit base game details/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /edit subset game details/i })).toBeVisible();
  });
});
