import { render, screen } from '@/test';
import {
  createAchievement,
  createPlayerGameActivityEvent,
  createPlayerGameActivitySession,
  createPlayerGameActivitySummary,
} from '@/test/factories';

import { UserGameActivityTimeline } from './UserGameActivityTimeline';

describe('Component: UserGameActivityTimeline', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameActivityTimeline isOnlyShowingAchievementSessions={false} />,
      {
        pageProps: {
          activity: {
            sessions: [],
            clientBreakdown: [],
            summarizedActivity: createPlayerGameActivitySummary(),
          },
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no sessions, renders an empty timeline', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameActivityTimeline isOnlyShowingAchievementSessions={false} />,
      {
        pageProps: {
          activity: {
            sessions: [],
            clientBreakdown: [],
            summarizedActivity: createPlayerGameActivitySummary(),
          },
        },
      },
    );

    // ASSERT
    expect(screen.queryByRole('listitem')).not.toBeInTheDocument();
  });

  it('given isOnlyShowingAchievementSessions is true, filters out non-achievement sessions', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameActivityTimeline isOnlyShowingAchievementSessions={true} />,
      {
        pageProps: {
          activity: {
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
            clientBreakdown: [],
            summarizedActivity: createPlayerGameActivitySummary(),
          },
        },
      },
    );

    // ASSERT
    expect(screen.getAllByTestId('session-header')).toHaveLength(2); // 2 for desktop and mobile
  });

  it('given a session has no game hash, displays with muted styling', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameActivityTimeline isOnlyShowingAchievementSessions={false} />,
      {
        pageProps: {
          activity: {
            sessions: [
              createPlayerGameActivitySession({
                type: 'player-session',
                gameHash: undefined,
                events: [createPlayerGameActivityEvent()],
              }),
            ],
            clientBreakdown: [],
            summarizedActivity: createPlayerGameActivitySummary(),
          },
        },
      },
    );

    // ASSERT
    expect(screen.getAllByTestId('rom-info')[0]).toHaveClass('text-neutral-500');
  });

  it('given a session has multiple events, shows borders between them', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameActivityTimeline isOnlyShowingAchievementSessions={false} />,
      {
        pageProps: {
          activity: {
            sessions: [
              createPlayerGameActivitySession({
                type: 'player-session',
                events: [
                  createPlayerGameActivityEvent({ type: 'unlock' }),
                  createPlayerGameActivityEvent({ type: 'unlock' }),
                  createPlayerGameActivityEvent({ type: 'unlock' }),
                ],
              }),
            ],
            clientBreakdown: [],
            summarizedActivity: createPlayerGameActivitySummary(),
          },
        },
      },
    );

    // ASSERT
    const events = screen.getAllByRole('listitem');
    expect(events[0]).toHaveClass('border-b');
    expect(events[1]).toHaveClass('border-b');
    expect(events[2]).not.toHaveClass('border-b'); // !! final event should not have a bottom border.
  });

  it('given a non-player session, does not display hash info', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameActivityTimeline isOnlyShowingAchievementSessions={false} />,
      {
        pageProps: {
          activity: {
            sessions: [
              createPlayerGameActivitySession({
                type: 'manual-unlock', // !!
                events: [createPlayerGameActivityEvent()],
              }),
            ],
            clientBreakdown: [],
            summarizedActivity: createPlayerGameActivitySummary(),
          },
        },
      },
    );

    // ASSERT
    expect(screen.queryByTestId('rom-info')).not.toBeInTheDocument();
  });
});
