import userEvent from '@testing-library/user-event';

import { useCurrentListView } from '@/features/games/hooks/useCurrentListView';
import { render, screen } from '@/test';
import { createLeaderboard, createLeaderboardEntry, createUser } from '@/test/factories';

import { FeaturedLeaderboardsList } from './FeaturedLeaderboardsList';

vi.mock('@/features/games/hooks/useCurrentListView');

describe('Component: FeaturedLeaderboardsList', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView: vi.fn(),
    });

    const { container } = render(<FeaturedLeaderboardsList featuredLeaderboards={[]} />, {
      pageProps: { numLeaderboards: 0 },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no featured leaderboards, renders nothing', () => {
    // ARRANGE
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView: vi.fn(),
    });

    render(<FeaturedLeaderboardsList featuredLeaderboards={[]} />, {
      pageProps: { numLeaderboards: 0 },
    });

    // ASSERT
    expect(screen.queryByTestId('featured-leaderboards-list')).not.toBeInTheDocument();
  });

  it('given there are featured leaderboards, displays them', () => {
    // ARRANGE
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView: vi.fn(),
    });

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
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView: vi.fn(),
    });

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
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView: vi.fn(),
    });

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
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView: vi.fn(),
    });

    const leaderboard = createLeaderboard();

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 5 }, // !!
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /view all/i })).not.toBeInTheDocument();
  });

  it('given there are more than 5 total leaderboards, shows the view all button with the correct count', () => {
    // ARRANGE
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView: vi.fn(),
    });

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

    const setCurrentListView = vi.fn();
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView,
    });

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

    const setCurrentListView = vi.fn();
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView,
    });

    const leaderboard = createLeaderboard();

    render(<FeaturedLeaderboardsList featuredLeaderboards={[leaderboard]} />, {
      pageProps: { numLeaderboards: 10 },
    });

    // ACT
    await screen.getByRole('button', { name: /view all/i }).click();

    // ... initially should not be called ...
    expect(setCurrentListView).not.toHaveBeenCalled();

    // ASSERT
    vi.runAllTimers();
    expect(setCurrentListView).toHaveBeenCalledWith('leaderboards');

    vi.useRealTimers();
  });

  it('given the target element does not exist, does not crash when clicking view all', async () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    window.scrollTo = mockScrollTo;

    vi.spyOn(document, 'getElementById').mockReturnValue(null); // !!

    const setCurrentListView = vi.fn();
    vi.mocked(useCurrentListView).mockReturnValue({
      currentListView: 'achievements',
      setCurrentListView,
    });

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
