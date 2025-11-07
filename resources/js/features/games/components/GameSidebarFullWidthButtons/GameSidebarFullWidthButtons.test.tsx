import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

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

  it('given the user is a developer, shows the Contribute collapse button', () => {
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
    expect(screen.getByRole('button', { name: 'Contribute' })).toBeVisible();
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
