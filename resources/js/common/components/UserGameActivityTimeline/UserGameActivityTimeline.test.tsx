import { render, screen } from '@/test';
import {
  createAchievement,
  createPlayerGameActivityEvent,
  createPlayerGameActivitySession,
  createPlayerGameActivitySummary,
  createUser,
} from '@/test/factories';

import { UserGameActivityTimeline } from './UserGameActivityTimeline';

describe('Component: UserGameActivityTimeline', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <UserGameActivityTimeline
        activity={{
          sessions: [],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no sessions, renders an empty timeline', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('listitem')).not.toBeInTheDocument();
  });

  it('given isOnlyShowingAchievementSessions is true, filters out non-achievement sessions', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  achievement: createAchievement(),
                }),
              ],
            }),
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [createPlayerGameActivityEvent({ type: 'rich-presence', achievement: null })],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={true}
      />,
    );

    // ASSERT
    expect(screen.getAllByTestId('session-header')).toHaveLength(2); // one header each for desktop and mobile
  });

  it('given a session has no game hash, displays with muted styling', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              gameHash: undefined,
              events: [createPlayerGameActivityEvent()],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    expect(screen.getAllByTestId('rom-info')[0]).toHaveClass('text-neutral-500');
  });

  it('given a session has multiple events, shows borders between them', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
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
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    const events = screen.getAllByRole('listitem');
    expect(events[0]).toHaveClass('border-b');
    expect(events[1]).toHaveClass('border-b');
    expect(events[2]).not.toHaveClass('border-b');
  });

  it('given a non-player session, does not display hash info', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'manual-unlock', // !!
              events: [createPlayerGameActivityEvent()],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    expect(screen.queryByTestId('rom-info')).not.toBeInTheDocument();
  });

  it('given events occur at the same time, groups them without bottom padding', () => {
    // ARRANGE
    const timestamp = '2024-01-01T12:00:00Z';

    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  when: timestamp, // !!
                }),
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  when: timestamp, // !!
                }),
              ],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    const events = screen.getAllByRole('listitem');

    expect(events[0]).not.toHaveClass('pb-2.5');
    expect(events[0]).toHaveClass('px-3 pt-3');
  });

  it('given events occur at different times, includes bottom padding between them', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  when: '2024-01-01T12:00:00Z',
                }),
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  when: '2024-01-01T12:01:00Z',
                }),
              ],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    const events = screen.getAllByRole('listitem');

    expect(events[0]).toHaveClass('pb-2.5');
    expect(events[0]).toHaveClass('px-3 pt-3');
  });

  it('given a manual unlock session has no events, filters it out', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'manual-unlock',
              events: [], // !!
            }),
            createPlayerGameActivitySession({
              type: 'manual-unlock',
              events: [createPlayerGameActivityEvent()],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    expect(screen.getAllByTestId('session-header')).toHaveLength(2); // one session, rendered once for desktop and once for mobile
  });

  it('given a reported achievement id, marks only the event that matches it', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  achievement: createAchievement({ id: 2468 }),
                }),
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  achievement: createAchievement({ id: 1357 }),
                }),
              ],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
        reportedAchievementId={2468}
      />,
    );

    // ASSERT
    expect(screen.getAllByText(/reported achievement/i)).toHaveLength(2); // one event, rendered once for desktop and once for mobile
  });

  it('given no reported achievement id, marks nothing', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  achievement: createAchievement({ id: 2468 }),
                }),
              ],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/reported achievement/i)).not.toBeInTheDocument();
  });

  it('given a manual unlock session contains unlocks granted by two different users, contains the name of the unlocker not contained in the header', () => {
    // ARRANGE
    const when = '2023-12-22T01:32:31Z';

    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'manual-unlock',
              startTime: when,
              events: [
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  when,
                  achievement: createAchievement({ id: 1, title: "Rock 'n' Roll!" }),
                  unlocker: createUser({ displayName: 'Searo' }),
                }),
                createPlayerGameActivityEvent({
                  type: 'unlock',
                  when,
                  achievement: createAchievement({ id: 2, title: 'Just Throw It' }),
                  unlocker: createUser({ displayName: 'AuburnRDM' }),
                }),
              ],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    expect(screen.getAllByText(/manually unlocked by/i)).toHaveLength(2);
    expect(screen.getAllByRole('img', { name: /auburnrdm/i })).toHaveLength(2);
  });

  it('given a ticket created session, shows the ticket created event and no session header', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'ticket-created',
              events: [
                createPlayerGameActivityEvent({
                  type: 'ticket-created',
                  achievement: null,
                  description: '',
                }),
              ],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
      />,
    );

    // ASSERT
    expect(screen.getAllByText(/this ticket was created/i)).toHaveLength(2); // one event, rendered once for desktop and once for mobile
    expect(screen.queryByTestId('session-header')).not.toBeInTheDocument();
  });

  it('given the ticket was created after the first session, shows the earlier sessions that exist', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-01T12:00:00Z',
            }),
            createPlayerGameActivitySession({
              type: 'ticket-created',
              startTime: '2024-03-02T12:00:00Z',
              events: [
                createPlayerGameActivityEvent({
                  type: 'ticket-created',
                  achievement: null,
                  description: '',
                }),
              ],
            }),
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-03T12:00:00Z',
            }),
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-04T12:00:00Z',
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
        maxSessionsAroundTicket={1}
      />,
    );

    // ASSERT
    expect(screen.getAllByText(/mar 1, 2024/i)).toHaveLength(2);
    expect(screen.getAllByText(/this ticket was created/i)).toHaveLength(2);
    expect(screen.getAllByText(/mar 3, 2024/i)).toHaveLength(2);
    expect(screen.queryAllByText(/mar 4, 2024/i)).toEqual([]);
  });

  it('given an empty manual unlock session next to the ticket, does not count it toward the sessions around the ticket', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-01T12:00:00Z',
            }),
            createPlayerGameActivitySession({
              type: 'manual-unlock',
              startTime: '2024-03-02T12:00:00Z',
              events: [],
            }),
            createPlayerGameActivitySession({
              type: 'ticket-created',
              startTime: '2024-03-03T12:00:00Z',
              events: [
                createPlayerGameActivityEvent({
                  type: 'ticket-created',
                  achievement: null,
                  description: '',
                }),
              ],
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
        maxSessionsAroundTicket={1}
      />,
    );

    // ASSERT
    expect(screen.getAllByText(/mar 1, 2024/i)).toHaveLength(2);
  });

  it('given the ticket was created during a play session, centers the sessions around that play session', () => {
    // ARRANGE
    render(
      <UserGameActivityTimeline
        activity={{
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-01T12:00:00Z',
            }),
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-02T12:00:00Z',
            }),
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-03T12:00:00Z',
              events: [
                createPlayerGameActivityEvent({
                  type: 'ticket-created',
                  achievement: null,
                  description: '',
                }),
              ],
            }),
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-04T12:00:00Z',
            }),
            createPlayerGameActivitySession({
              type: 'player-session',
              startTime: '2024-03-05T12:00:00Z',
            }),
          ],
          clientBreakdown: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        }}
        isOnlyShowingAchievementSessions={false}
        maxSessionsAroundTicket={1}
      />,
    );

    // ASSERT
    expect(screen.getAllByText(/this ticket was created/i)).toHaveLength(2);
    expect(screen.queryAllByText(/mar 1, 2024/i)).toEqual([]);
    expect(screen.getAllByText(/mar 2, 2024/i)).toHaveLength(2);
    expect(screen.getAllByText(/mar 4, 2024/i)).toHaveLength(2);
    expect(screen.queryAllByText(/mar 5, 2024/i)).toEqual([]);
  });
});
