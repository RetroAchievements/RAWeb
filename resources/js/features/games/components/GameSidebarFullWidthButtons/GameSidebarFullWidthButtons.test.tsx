import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { GameSidebarFullWidthButtons } from './GameSidebarFullWidthButtons';

describe('Component: GameSidebarFullWidthButtons', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        backingGame: createGame(),
        can: {},
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, does not render the Extras section', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        backingGame: createGame(),
        can: {},
      },
    });

    // ASSERT
    expect(screen.queryByText(/extras/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /memory/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /tickets/i })).not.toBeInTheDocument();
  });

  it('given the user is authenticated, renders the Extras section', () => {
    // ARRANGE
    render(
      <GameSidebarFullWidthButtons game={createGame({ system: createSystem({ active: true }) })} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          can: {},
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/extras/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /memory/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /tickets/i })).toBeVisible();
  });

  it('given the user is authenticated and the backing game has a guide, renders the Guide link', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ guideUrl: 'google.com' }),
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /guide/i })).toBeVisible();
  });

  it('given the user can manage games, displays the Management section', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGames: true },
      },
    });

    // ASSERT
    expect(screen.getByText(/management/i)).toBeVisible();
  });

  it('given the user can manage games and create game forum topics and the backing game has no forum topic, shows a create new forum topic button', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame({ forumTopicId: undefined })} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ forumTopicId: undefined }),
        can: { manageGames: true, createGameForumTopic: true },
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /create new forum topic/i })).toBeVisible();
  });

  it('given the user can manage games and create game forum topics and the game already has a forum topic, does not show a create new forum topic button', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame({ forumTopicId: 123 })} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGames: true, createGameForumTopic: true },
      },
    });

    // ASSERT
    expect(
      screen.queryByRole('button', { name: /create new forum topic/i }),
    ).not.toBeInTheDocument();
  });

  it('given the game system is not active, never displays the memory and tickets buttons', () => {
    // ARRANGE
    render(
      <GameSidebarFullWidthButtons
        game={createGame({ system: createSystem({ active: false }) })}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          can: { manageGames: true, createGameForumTopic: true },
        },
      },
    );

    // ASSERT
    expect(screen.queryByRole('button', { name: /memory/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /tickets/i })).not.toBeInTheDocument();
  });

  it('given there are no compatible hashes, does not display a supported game files link', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGames: true, createGameForumTopic: true },
        numCompatibleHashes: 0, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /supported game files/i })).not.toBeInTheDocument();
  });

  it('given there is at least 1 compatible hash, displays a supported game files link', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGames: true, createGameForumTopic: true },
        numCompatibleHashes: 1, // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /supported game files/i })).toBeVisible();
  });

  it('given there is nothing to display, does not display any section', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame({ forumTopicId: undefined })} />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        can: { manageGames: false, createGameForumTopic: false },
        numCompatibleHashes: 0, // !!
      },
    });

    // ASSERT
    expect(screen.queryByText(/essential resources/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/extras/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/manage/i)).not.toBeInTheDocument();
  });

  it('given the user is a developer, shows the development section', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame({ forumTopicId: undefined })} />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        game: createGame({ gameAchievementSets: [] }),
        can: { manageGames: true, createGameForumTopic: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/development/i)).toBeVisible();
  });

  it('given only the updateGame permission is truthy, shows the Management section', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
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
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
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
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
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

    render(<GameSidebarFullWidthButtons game={game} />, {
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

    render(<GameSidebarFullWidthButtons game={game} />, {
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
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { manageGames: false, updateGame: true },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /game details/i })).not.toBeInTheDocument();
  });

  it('given only the manageGameHashes permission is truthy, shows the Manage Hashes link', () => {
    // ARRANGE
    const game = createGame({ id: 789 });

    render(<GameSidebarFullWidthButtons game={game} />, {
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

    render(<GameSidebarFullWidthButtons game={game} />, {
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
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
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

    render(<GameSidebarFullWidthButtons game={game} />, {
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
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        can: { updateAnyAchievementSetClaim: false }, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /claims/i })).not.toBeInTheDocument();
  });

  it('given the user is a developer, shows the Subscribe section with Achievement Comments and Tickets buttons', () => {
    // ARRANGE
    const backingGame = createGame({ id: 123 });

    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) }, // !!
        backingGame,
        game: createGame({ gameAchievementSets: [] }),
        can: {},
        isSubscribedToAchievementComments: false,
        isSubscribedToTickets: false,
      },
    });

    // ASSERT
    expect(screen.getByText(/subscribe/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /achievement comments/i })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Tickets' })).toBeVisible();
  });

  it('given the user is a junior developer, shows the Subscribe section with Achievement Comments and Tickets buttons', () => {
    // ARRANGE
    const backingGame = createGame({ id: 123 });

    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer-junior'] }) }, // !!
        backingGame,
        game: createGame({ gameAchievementSets: [] }),
        can: {},
        isSubscribedToAchievementComments: false,
        isSubscribedToTickets: false,
      },
    });

    // ASSERT
    expect(screen.getByText(/subscribe/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /achievement comments/i })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Tickets' })).toBeVisible();
  });

  it('given the user is not a developer, does not show the Subscribe section', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: [] }) }, // !!
        backingGame: createGame(),
        can: {},
        isSubscribedToAchievementComments: false,
        isSubscribedToTickets: false,
      },
    });

    // ASSERT
    expect(screen.queryByText(/subscribe/i)).not.toBeInTheDocument();
  });
});
