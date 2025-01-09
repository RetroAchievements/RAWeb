import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createGame, createPlayerGame, createPlayerGameActivitySummary } from '@/test/factories';

import { UserGameSummarizedActivity } from './UserGameSummarizedActivity';

describe('Component: UserGameSummarizedActivity', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameSummarizedActivity />,
      {
        pageProps: {
          activity: {
            summarizedActivity: createPlayerGameActivitySummary(),
            clientBreakdown: [],
            sessions: [],
          },
          game: createGame(),
          playerGame: createPlayerGame(),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given activity data exists, renders stat cards correctly', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameSummarizedActivity />, {
      pageProps: {
        activity: {
          summarizedActivity: createPlayerGameActivitySummary({
            achievementPlaytime: 3600,
            achievementSessionCount: 2,
            generatedSessionAdjustment: 0,
            totalPlaytime: 7200,
            totalUnlockTime: 3600,
          }),
          clientBreakdown: [],
          sessions: [],
        },
        game: createGame({ achievementsPublished: 10 }),
        playerGame: createPlayerGame({ achievementsUnlocked: 5 }),
      },
    });

    // ASSERT
    expect(screen.getByText(/1h/i)).toBeVisible();
    expect(screen.getByText(/2h/i)).toBeVisible();
    expect(screen.getByText(/2 sessions over 1 hour/i)).toBeVisible();
    expect(screen.getByText(/5 of 10/i)).toBeVisible();
  });

  it('given there are estimated sessions, shows the estimated label', async () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameSummarizedActivity />, {
      pageProps: {
        activity: {
          summarizedActivity: createPlayerGameActivitySummary({
            achievementPlaytime: 3600,
            achievementSessionCount: 1,
            generatedSessionAdjustment: 1, // !!
            totalPlaytime: 7200,
            totalUnlockTime: 3600,
          }),
          clientBreakdown: [],
          sessions: [],
        },
        game: createGame({ achievementsPublished: 10 }),
        playerGame: createPlayerGame({ achievementsUnlocked: 5 }),
      },
    });

    // ACT
    await userEvent.hover(screen.getAllByText(/estimated/i)[0]);

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/not precise/i)[0]).toBeVisible();
    });
  });
});
