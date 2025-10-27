import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createGame, createZiggyProps } from '@/test/factories';

import { SidebarDevelopmentSection } from './SidebarDevelopmentSection';

const mockAddToGameList = vi.fn();
const mockRemoveFromGameList = vi.fn();

vi.mock('@/common/hooks/useAddOrRemoveFromUserGameList', () => ({
  useAddOrRemoveFromUserGameList: () => ({
    addToGameList: mockAddToGameList,
    removeFromGameList: mockRemoveFromGameList,
    isPending: false,
  }),
}));

vi.mock('@/common/components/InertiaLink', () => ({
  InertiaLink: ({ children, href, ...props }: any) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}));

Object.defineProperty(window, 'location', {
  value: { href: '' },
  writable: true,
});

describe('Component: SidebarDevelopmentSection', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    mockAddToGameList.mockResolvedValue(undefined);
    mockRemoveFromGameList.mockResolvedValue(undefined);
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({ gameAchievementSets: [] });
    const backingGame = game;
    const pageProps = {
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    const { container } = render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game is not on the want to develop list, sets aria-pressed to false', () => {
    // ARRANGE
    const game = createGame({ gameAchievementSets: [] });
    const backingGame = game;
    const pageProps = {
      backingGame,
      game,
      auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to develop/i });
    expect(button).toBeVisible();
    expect(button).toHaveAttribute('aria-pressed', 'false');
  });

  it('given the game is on the want to develop list, sets aria-pressed to true', () => {
    // ARRANGE
    const game = createGame({ gameAchievementSets: [] });
    const backingGame = game;
    const pageProps = {
      backingGame,
      game,
      auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: true,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to develop/i });
    expect(button).toBeVisible();
    expect(button).toHaveAttribute('aria-pressed', 'true');
  });

  it('given the user clicks the button when the game is not in the list, adds it to the develop list', async () => {
    // ARRANGE
    const game = createGame({ id: 123, title: 'Super Mario World', gameAchievementSets: [] });
    const backingGame = game;
    const user = createAuthenticatedUser({ roles: ['developer'] });
    const pageProps = {
      auth: { user },
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to develop/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockAddToGameList).toHaveBeenCalledWith(
        123,
        'Super Mario World',
        expect.objectContaining({
          userGameListType: 'develop',
        }),
      );
    });

    // ... the button should optimistically update to show as pressed ...
    expect(screen.getByRole('button', { name: /want to develop/i })).toHaveAttribute(
      'aria-pressed',
      'true',
    );
  });

  it('given the user clicks the button when the game is in the list, removes it from the develop list', async () => {
    // ARRANGE
    const game = createGame({ id: 456, title: 'Donkey Kong Country', gameAchievementSets: [] });
    const backingGame = game;
    const user = createAuthenticatedUser({ roles: ['developer'] });
    const pageProps = {
      auth: { user },
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: true,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to develop/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockRemoveFromGameList).toHaveBeenCalledWith(
        456,
        'Donkey Kong Country',
        expect.objectContaining({
          userGameListType: 'develop',
        }),
      );
    });

    // ... the button should optimistically update to show as not pressed ...
    expect(screen.getByRole('button', { name: /want to develop/i })).toHaveAttribute(
      'aria-pressed',
      'false',
    );
  });

  it('given the game already has achievements published, changes the button label to mention revisions instead', () => {
    // ARRANGE
    const game = createGame({
      id: 123,
      title: 'Super Mario World',
      achievementsPublished: 80,
      gameAchievementSets: [],
    });
    const backingGame = game;
    const user = createAuthenticatedUser({ roles: ['developer'] });
    const pageProps = {
      auth: { user },
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(screen.queryByRole('button', { name: /want to develop/i })).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: /want to revise/i })).toBeVisible();
  });

  it('given the game and backing game are different, displays the subset indicator', () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({ id: 2 });
    const pageProps = {
      backingGame,
      game,
      auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(screen.getAllByRole('img', { name: /subset/i })[0]).toBeVisible();
  });

  it('given the user is viewing unpublished achievements, shows a link to view published achievements', () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({ id: 2 });
    const pageProps = {
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      isViewingPublishedAchievements: false, // !!
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(screen.getByRole('link', { name: /view published achievements/i })).toBeVisible();
  });

  it('given the user is viewing published achievements and there are unpublished achievements, shows the count of unpublished achievements', () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({
      id: 2,
      achievementsPublished: 50,
      achievementsUnpublished: 12, // !!
    });
    const pageProps = {
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      isViewingPublishedAchievements: true, // !!
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    const link = screen.getByRole('link', { name: /view unpublished achievements/i });
    expect(link).toBeVisible();
    expect(link).toHaveTextContent('12');
  });

  it('given the user is viewing unpublished achievements, shows the count of published achievements', () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({
      id: 2,
      achievementsPublished: 75, // !!
      achievementsUnpublished: 8,
    });
    const pageProps = {
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      isViewingPublishedAchievements: false, // !!
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    const link = screen.getByRole('link', { name: /view published achievements/i });
    expect(link).toBeVisible();
    expect(link).toHaveTextContent('75');
  });

  it('given the user is viewing published achievements and there are no unpublished achievements, hides the link button to view unpublished achievements', () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({
      id: 2,
      achievementsPublished: 50,
      achievementsUnpublished: 0, // !!
    });
    const pageProps = {
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      isViewingPublishedAchievements: true, // !!
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(
      screen.queryByRole('link', { name: /view unpublished achievements/i }),
    ).not.toBeInTheDocument();
  });

  it('given the user is viewing published achievements and there are unpublished achievements, shows the link button to view unpublished achievements', () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({
      id: 2,
      achievementsPublished: 50,
      achievementsUnpublished: 5, // !!
    });
    const pageProps = {
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      isViewingPublishedAchievements: true, // !!
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(screen.getByRole('link', { name: /view unpublished achievements/i })).toBeVisible();
  });

  it('given the user taps the link to view unpublished achievements, scrolls to the top of the screen', async () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({
      id: 2,
      achievementsPublished: 50,
      achievementsUnpublished: 10,
    });
    const pageProps = {
      backingGame,
      game,
      achievementSetClaims: [],
      can: {},
      isOnWantToDevList: false,
      isViewingPublishedAchievements: true,
      ziggy: createZiggyProps(),
    };

    const scrollToSpy = vi.spyOn(window, 'scrollTo');

    render(<SidebarDevelopmentSection />, { pageProps });

    // ACT
    await userEvent.click(screen.getByRole('link', { name: /view unpublished achievements/i }));

    // ASSERT
    expect(scrollToSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        top: 0,
      }),
    );
  });

  it('given manageAchievementSetClaims is true, shows the View Claim History button', () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({ id: 123 });
    const pageProps = {
      backingGame,
      game,
      auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
      achievementSetClaims: [],
      can: { manageAchievementSetClaims: true }, // !!
      isOnWantToDevList: false,
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    const link = screen.getByRole('link', { name: /view claim history/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', expect.stringContaining('game.claims,'));
  });

  it('given manageAchievementSetClaims is false, does not show the View Claim History button', () => {
    // ARRANGE
    const game = createGame({ id: 1, gameAchievementSets: [] });
    const backingGame = createGame({ id: 123 });
    const pageProps = {
      backingGame,
      game,
      auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
      achievementSetClaims: [],
      can: { manageAchievementSetClaims: false }, // !!
      isOnWantToDevList: false,
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(screen.queryByRole('link', { name: /view claim history/i })).not.toBeInTheDocument();
  });
});
