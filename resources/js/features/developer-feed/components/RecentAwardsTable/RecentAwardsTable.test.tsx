import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createRecentPlayerBadge, createUser } from '@/test/factories';

import { RecentAwardsTable } from './RecentAwardsTable';

describe('Component: RecentAwardsTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentAwardsTable recentPlayerBadges={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no recent awards, displays an empty state', () => {
    // ARRANGE
    render(<RecentAwardsTable recentPlayerBadges={[]} />);

    // ASSERT
    expect(screen.getByText(/couldn't find any recent awards/i)).toBeVisible();
  });

  it('given there are recent awards, displays them in a table', () => {
    // ARRANGE
    const recentAward = createRecentPlayerBadge({
      game: createGame({ id: 1, title: 'Test Game' }),
      user: createUser({ displayName: 'Scott' }),
      awardType: 'beaten-softcore',
    });

    render(<RecentAwardsTable recentPlayerBadges={[recentAward]} />);

    // ASSERT
    expect(screen.getByRole('table')).toBeVisible();

    expect(screen.getAllByText(/test game/i)[0]).toBeVisible();
    expect(screen.getByText(/beaten \(softcore\)/i)).toBeVisible();
    expect(screen.getAllByText(/scott/i)[0]).toBeVisible();
  });

  it('given the user prefers absolute dates, shows the timestamp in absolute format', () => {
    // ARRANGE
    const recentAward = createRecentPlayerBadge({
      earnedAt: new Date('2023-05-05').toISOString(),
    });

    render(<RecentAwardsTable recentPlayerBadges={[recentAward]} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: {
              prefersAbsoluteDates: true,
              shouldAlwaysBypassContentWarnings: false,
            },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/may 05, 2023/i)).toBeVisible();
  });

  describe('Award label translation', () => {
    it('given the award type is beaten-softcore, displays "Beaten (softcore)"', () => {
      // ARRANGE
      const recentAward = createRecentPlayerBadge({ awardType: 'beaten-softcore' });

      render(<RecentAwardsTable recentPlayerBadges={[recentAward]} />);

      // ASSERT
      expect(screen.getByText(/beaten \(softcore\)/i)).toBeVisible();
    });

    it('given the award type is beaten-hardcore, displays "Beaten"', () => {
      // ARRANGE
      const recentAward = createRecentPlayerBadge({ awardType: 'beaten-hardcore' });

      render(<RecentAwardsTable recentPlayerBadges={[recentAward]} />);

      // ASSERT
      expect(screen.getByText(/beaten$/i)).toBeVisible();
    });

    it('given the award type is completed, displays "Completed"', () => {
      // ARRANGE
      const recentAward = createRecentPlayerBadge({ awardType: 'completed' });

      render(<RecentAwardsTable recentPlayerBadges={[recentAward]} />);

      // ASSERT
      expect(screen.getByText(/completed/i)).toBeVisible();
    });

    it('given the award type is mastered, displays "Mastered"', () => {
      // ARRANGE
      const recentAward = createRecentPlayerBadge({ awardType: 'mastered' });

      render(<RecentAwardsTable recentPlayerBadges={[recentAward]} />);

      // ASSERT
      expect(screen.getByText(/mastered/i)).toBeVisible();
    });
  });
});
