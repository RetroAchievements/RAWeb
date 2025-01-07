import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createAchievement,
  createGame,
  createPlayerGame,
  createPlayerGameActivityEvent,
  createPlayerGameActivitySession,
  createPlayerGameActivitySummary,
  createUser,
} from '@/test/factories';

import { UserGameActivityMainRoot } from './UserGameActivityMainRoot';

describe('Component: UserGameActivityMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameActivityMainRoot />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          player: createUser(),
          game: createGame(),
          playerGame: createPlayerGame(),
          activity: {
            clientBreakdown: [],
            sessions: [],
            summarizedActivity: createPlayerGameActivitySummary(),
          },
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays user breadcrumbs', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameActivityMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        player: createUser({ displayName: 'Scott' }),
        game: createGame({ title: 'Sonic the Hedgehog' }),
        playerGame: createPlayerGame(),
        activity: {
          clientBreakdown: [],
          sessions: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('listitem', { name: /all users/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: /scott/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: /sonic the hedgehog/i })).toBeVisible();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameActivityMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        player: createUser({ displayName: 'Scott' }),
        game: createGame({ title: 'Sonic the Hedgehog' }),
        playerGame: createPlayerGame(),
        activity: {
          clientBreakdown: [],
          sessions: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /activity/i })).toBeVisible();
  });

  it('given the user has never played the game, shows an empty state', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameActivityMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        player: createUser({ displayName: 'Scott' }),
        game: createGame({ title: 'Sonic the Hedgehog' }),
        playerGame: null, // !!
        activity: {
          clientBreakdown: [],
          sessions: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/scott has not played/i)).toBeVisible();
  });

  it('given the user toggles to see achievement only sessions, shows just achievement only sessions', async () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameActivityMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        player: createUser(),
        game: createGame(),
        playerGame: createPlayerGame(),
        activity: {
          clientBreakdown: [],
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [
                // !! achievement session
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  achievement: createAchievement(),
                }),
              ],
            }),
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [
                // !! non-achievement session
                createPlayerGameActivityEvent({ type: 'rich-presence', achievement: null }),
              ],
            }),
          ],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('switch', { name: /hide all player sessions/i }));

    // ASSERT
    expect(screen.getAllByTestId('session-header')).toHaveLength(2); // 2 for desktop and mobile
  });
});
