import { render, screen } from '@/test';
import { createPaginatedData, createUser } from '@/test/factories';

import { DeveloperFeedMainRoot } from './DeveloperFeedMainRoot';

describe('Component: DeveloperFeedMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Community.Data.DeveloperFeedPageProps>(
      <DeveloperFeedMainRoot />,
      {
        pageProps: {
          activePlayers: createPaginatedData([]),
          awardsContributed: 0,
          developer: createUser(),
          leaderboardEntriesContributed: 0,
          pointsContributed: 0,
          recentLeaderboardEntries: [],
          recentPlayerBadges: [],
          recentUnlocks: [],
          unlocksContributed: 0,
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given developer data, renders the header section', () => {
    // ARRANGE
    const developer = createUser({ displayName: 'Test Dev' });

    render<App.Community.Data.DeveloperFeedPageProps>(<DeveloperFeedMainRoot />, {
      pageProps: {
        activePlayers: createPaginatedData([]),
        awardsContributed: 0,
        developer,
        leaderboardEntriesContributed: 0,
        pointsContributed: 0,
        recentLeaderboardEntries: [],
        recentPlayerBadges: [],
        recentUnlocks: [],
        unlocksContributed: 0,
      },
    });

    // ASSERT
    expect(screen.getAllByText(/developer feed/i)[0]).toBeVisible();
    expect(screen.getByText(/test dev/i)).toBeVisible();
  });

  it('given contribution stats, displays them with localized formatting', () => {
    // ARRANGE
    render<App.Community.Data.DeveloperFeedPageProps>(<DeveloperFeedMainRoot />, {
      pageProps: {
        activePlayers: createPaginatedData([]),
        awardsContributed: 910,
        developer: createUser(),
        leaderboardEntriesContributed: 1112,
        pointsContributed: 5678,
        recentLeaderboardEntries: [],
        recentPlayerBadges: [],
        recentUnlocks: [],
        unlocksContributed: 1234,
      },
    });

    // ASSERT
    expect(screen.getByText(/1,234/i)).toBeVisible();
    expect(screen.getByText(/5,678/i)).toBeVisible();
    expect(screen.getByText(/910/i)).toBeVisible();
    expect(screen.getByText(/1,112/i)).toBeVisible();
  });
});
