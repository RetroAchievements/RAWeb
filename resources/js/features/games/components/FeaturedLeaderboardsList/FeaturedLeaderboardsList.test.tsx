// eslint-disable-next-line no-restricted-imports -- fine in a test
import * as InertiajsReact from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { currentListViewAtom } from '@/features/games/state/games.atoms';
import { act, render, screen } from '@/test';
import { createLeaderboard, createLeaderboardEntry, createUser } from '@/test/factories';

import { FeaturedLeaderboardsList } from './FeaturedLeaderboardsList';

describe('Component: FeaturedLeaderboardsList', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    // Mock router.reload to prevent actual HTTP requests in tests.
    vi.spyOn(InertiajsReact.router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<FeaturedLeaderboardsList featuredLeaderboards={[]} />, {
      pageProps: { numLeaderboards: 0 },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no featured leaderboards, renders nothing', () => {
    // ARRANGE
    render(<FeaturedLeaderboardsList featuredLeaderboards={[]} />, {
      pageProps: { numLeaderboards: 0 },
    });

    // ASSERT
    expect(screen.queryByTestId('featured-leaderboards-list')).not.toBeInTheDocument();
  });

  it('given there are featured leaderboards, displays them', () => {
    // ARRANGE
    const leaderboard = createLeaderboard({
      id: 123,
      title: 'High Score Challenge',
      description: 'Get the highest score',
    });

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 1 },
    });

    // ASSERT
    expect(screen.getByText(/high score challenge/i)).toBeVisible();
    expect(screen.getByText(/get the highest score/i)).toBeVisible();
  });

  it('given a leaderboard has a top entry, displays the top entry information', () => {
    // ARRANGE
    const topUser = createUser({ displayName: 'TopPlayer' });
    const topEntry = createLeaderboardEntry({
      user: topUser,
      formattedScore: '999,999',
    });
    const leaderboard = createLeaderboard({ topEntry });

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 1 },
    });

    // ASSERT
    expect(screen.getByText(/topplayer/i)).toBeVisible();
    expect(screen.getByText(/999,999/i)).toBeVisible();
  });

  it('given a leaderboard has no top entry user, does not crash', () => {
    // ARRANGE
    const leaderboard = createLeaderboard({ topEntry: undefined });

    const { container } = render(
      <FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />,
      {
        pageProps: { numLeaderboards: 1 },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are 5 or fewer total leaderboards, does not show the view all button', () => {
    // ARRANGE
    const leaderboard = createLeaderboard();

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 5 }, // !!
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /view all/i })).not.toBeInTheDocument();
  });

  it('given there are more than 5 total leaderboards, shows the view all button with the correct count', () => {
    // ARRANGE
    const leaderboard = createLeaderboard();

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 10 }, // !!
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /view all 10 leaderboards/i })).toBeVisible();
  });

  it('given the user clicks the view all button, scrolls to the game achievement sets container', async () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    window.scrollTo = mockScrollTo;

    const mockElement = document.createElement('div');
    mockElement.id = 'game-achievement-sets-container';
    vi.spyOn(mockElement, 'getBoundingClientRect').mockReturnValue({
      top: 500,
      bottom: 0,
      left: 0,
      right: 0,
      width: 0,
      height: 0,
      x: 0,
      y: 0,
      toJSON: vi.fn(),
    });
    vi.spyOn(document, 'getElementById').mockReturnValue(mockElement);

    const leaderboard = createLeaderboard();

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 10 },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /view all/i }));

    // ASSERT
    expect(mockScrollTo).toHaveBeenCalledWith({
      top: 500 + window.scrollY - 48,
      behavior: 'smooth',
    });
  });

  it('given the user clicks the view all button, switches to the leaderboards view after a delay', async () => {
    // ARRANGE
    vi.useFakeTimers();

    const mockScrollTo = vi.fn();
    window.scrollTo = mockScrollTo;

    const mockElement = document.createElement('div');
    mockElement.id = 'game-achievement-sets-container';
    vi.spyOn(mockElement, 'getBoundingClientRect').mockReturnValue({
      top: 500,
      bottom: 0,
      left: 0,
      right: 0,
      width: 0,
      height: 0,
      x: 0,
      y: 0,
      toJSON: vi.fn(),
    });
    vi.spyOn(document, 'getElementById').mockReturnValue(mockElement);

    const leaderboard = createLeaderboard();

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 10 },
      jotaiAtoms: [
        [currentListViewAtom, 'achievements'],
        //
      ],
    });

    // ACT
    await screen.getByRole('button', { name: /view all/i }).click();

    // ASSERT
    // ... wait for the timeout ...
    act(() => {
      vi.runAllTimers();
    });

    // ... URL should be updated with leaderboards view ...
    expect(window.location.search).toContain('view=leaderboards');

    vi.useRealTimers();
  });

  it('given the target element does not exist, does not crash when clicking view all', async () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    window.scrollTo = mockScrollTo;

    vi.spyOn(document, 'getElementById').mockReturnValue(null); // !!

    const leaderboard = createLeaderboard();

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 10 },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /view all/i }));

    // ASSERT
    expect(mockScrollTo).toHaveBeenCalledWith({
      top: 0,
      behavior: 'smooth',
    });
  });
});
