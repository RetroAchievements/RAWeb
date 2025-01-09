import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createGame,
  createLeaderboard,
  createLeaderboardEntry,
  createRecentLeaderboardEntry,
  createUser,
} from '@/test/factories';

import { RecentLeaderboardEntriesTable } from './RecentLeaderboardEntriesTable';

describe('Component: RecentLeaderboardEntriesTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentLeaderboardEntriesTable recentLeaderboardEntries={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no recent entries, displays an empty state', () => {
    // ARRANGE
    render(<RecentLeaderboardEntriesTable recentLeaderboardEntries={[]} />);

    // ASSERT
    expect(screen.getByText(/couldn't find any recent leaderboard entries/i)).toBeVisible();
  });

  it('given there are recent entries, displays them in a table', () => {
    // ARRANGE
    const recentEntry = createRecentLeaderboardEntry({
      leaderboard: createLeaderboard({ id: 1, title: 'Test Leaderboard' }),
      leaderboardEntry: createLeaderboardEntry({ formattedScore: '1,234' }),
      game: createGame({ id: 1, title: 'Test Game' }),
      user: createUser({ displayName: 'Scott' }),
    });

    render(<RecentLeaderboardEntriesTable recentLeaderboardEntries={[recentEntry]} />);

    // ASSERT
    expect(screen.getByRole('table')).toBeVisible();

    expect(screen.getByText(/test leaderboard/i)).toBeVisible();
    expect(screen.getByText(/1,234/i)).toBeVisible();
    expect(screen.getAllByText(/test game/i)[0]).toBeVisible();
    expect(screen.getAllByText(/scott/i)[0]).toBeVisible();
  });

  it('given the user prefers absolute dates, shows the timestamp in absolute format', () => {
    // ARRANGE
    const recentEntry = createRecentLeaderboardEntry({
      submittedAt: new Date('2023-05-05').toISOString(),
    });

    render(<RecentLeaderboardEntriesTable recentLeaderboardEntries={[recentEntry]} />, {
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

  it('given a leaderboard entry is displayed, includes a link to view the full leaderboard', () => {
    // ARRANGE
    const recentEntry = createRecentLeaderboardEntry({
      leaderboard: createLeaderboard({ id: 123, title: 'Test Leaderboard' }),
    });

    render(<RecentLeaderboardEntriesTable recentLeaderboardEntries={[recentEntry]} />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /test leaderboard/i });

    expect(linkEl).toBeVisible();
    expect(linkEl.getAttribute('href')).toEqual('/leaderboardinfo.php?i=123');
  });
});
