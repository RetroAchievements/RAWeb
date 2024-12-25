import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createAchievement, createGame, createRecentUnlock, createUser } from '@/test/factories';

import { RecentUnlocksTable } from './RecentUnlocksTable';

describe('Component: RecentUnlocksTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentUnlocksTable recentUnlocks={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no recent unlocks, displays an empty state', () => {
    // ARRANGE
    render(<RecentUnlocksTable recentUnlocks={[]} />);

    // ASSERT
    expect(screen.getByText(/couldn't find any recent achievement unlocks/i)).toBeVisible();
  });

  it('given there are recent unlocks, displays them in a table', () => {
    // ARRANGE
    const recentUnlock = createRecentUnlock({
      achievement: createAchievement({ id: 1, title: 'Test Achievement' }),
      game: createGame({ id: 1, title: 'Test Game' }),
      user: createUser({ displayName: 'Scott' }),
      isHardcore: false,
    });

    render(<RecentUnlocksTable recentUnlocks={[recentUnlock]} />);

    // ASSERT
    expect(screen.getByRole('table')).toBeVisible();

    expect(screen.getAllByText(/test achievement/i)[0]).toBeVisible();
    expect(screen.getAllByText(/test game/i)[0]).toBeVisible();
    expect(screen.getAllByText(/scott/i)[0]).toBeVisible();
  });

  it('given an unlock is softcore, displays a softcore indicator', () => {
    // ARRANGE
    const recentUnlock = createRecentUnlock({ isHardcore: false });

    render(<RecentUnlocksTable recentUnlocks={[recentUnlock]} />);

    // ASSERT
    expect(screen.getByText(/\(softcore\)/i)).toBeVisible();
  });

  it('given an unlock is hardcore, does not display a softcore indicator', () => {
    // ARRANGE
    const recentUnlock = createRecentUnlock({ isHardcore: true });

    render(<RecentUnlocksTable recentUnlocks={[recentUnlock]} />);

    // ASSERT
    expect(screen.queryByText(/\(softcore\)/i)).not.toBeInTheDocument();
  });

  it('given the user prefers absolute dates, shows the timestamp in absolute format', () => {
    // ARRANGE
    const recentUnlock = createRecentUnlock({ unlockedAt: new Date('2023-05-05').toISOString() });

    render(<RecentUnlocksTable recentUnlocks={[recentUnlock]} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: {
              prefersAbsoluteDates: true,
            },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/may 05, 2023/i)).toBeVisible();
  });
});
