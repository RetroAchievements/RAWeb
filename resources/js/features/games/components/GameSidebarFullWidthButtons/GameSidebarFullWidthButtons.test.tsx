import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { GameSidebarFullWidthButtons } from './GameSidebarFullWidthButtons';

describe('Component: GameSidebarFullWidthButtons', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
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
          can: {},
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/extras/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /memory/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /tickets/i })).toBeVisible();
  });

  it('given the user is authenticated and the game has a guide, renders the Guide link', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame({ guideUrl: 'google.com' })} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /guide/i })).toBeVisible();
  });

  it('given the user can manage games, always displays a Game Details link', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { manageGames: true },
      },
    });

    // ASSERT
    expect(screen.getByText(/manage/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /game details/i })).toBeVisible();
  });

  it('given the user can manage games and create game forum topics and the game has no forum topic, shows a create new forum topic button', () => {
    // ARRANGE
    render(<GameSidebarFullWidthButtons game={createGame({ forumTopicId: undefined })} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
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
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        game: createGame(),
        can: { manageGames: true, createGameForumTopic: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/development/i)).toBeVisible();
  });
});
