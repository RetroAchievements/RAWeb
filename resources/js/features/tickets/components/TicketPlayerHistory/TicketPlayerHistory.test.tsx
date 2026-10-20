import userEvent from '@testing-library/user-event';
import type * as MotionReact from 'motion/react';
import { useReducedMotion } from 'motion/react';

import { render, screen, waitFor } from '@/test';
import {
  createAchievement,
  createGame,
  createPlayerGameActivityEvent,
  createPlayerGameActivitySession,
  createPlayerGameActivitySummary,
  createPlayerGameClientBreakdown,
  createTicketListEntry,
  createUser,
} from '@/test/factories';

import { TicketPlayerHistory } from './TicketPlayerHistory';

vi.mock('motion/react', async (importOriginal) => ({
  ...(await importOriginal<typeof MotionReact>()),
  useReducedMotion: vi.fn(() => false),
}));

describe('Component: TicketPlayerHistory', () => {
  beforeEach(() => {
    vi.mocked(useReducedMotion).mockReturnValue(false);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        hasReporterHistoryAccess: false,
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the viewer cannot see the reporter activity, renders nothing', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        hasReporterHistoryAccess: false,
        ticket: createTicketListEntry({
          game: createGame({ title: 'Streets of Rage 2' }),
          reporter: createUser({ displayName: 'Scott' }),
        }),
      },
    });

    // ASSERT
    expect(screen.queryByText(/scott's activity/i)).not.toBeInTheDocument();
    expect(screen.queryByTestId('player-history-skeleton')).not.toBeInTheDocument();
  });

  it('given the reporter activity has not resolved yet, shows the labeled bar and then a skeleton once expanded', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        hasReporterHistoryAccess: true,
        reporterActivity: undefined,
        ticket: createTicketListEntry({
          game: createGame({ title: 'Streets of Rage 2' }),
          reporter: createUser({ displayName: 'Scott' }),
        }),
      },
    });

    const expandTrigger = screen.getByRole('button', {
      name: "Scott's activity for Streets of Rage 2",
    });
    expect(expandTrigger).toBeVisible();
    expect(screen.queryByTestId('player-history-skeleton')).not.toBeInTheDocument();

    // ACT
    await userEvent.click(expandTrigger);

    // ASSERT
    expect(screen.getByTestId('player-history-skeleton')).toBeVisible();
  });

  it('given the reporter activity has resolved, keeps the client breakdown and timeline collapsed', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        achievement: createAchievement(),
        hasReporterHistoryAccess: true,
        reporterActivity: {
          clientBreakdown: [
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RALibRetro',
              durationPercentage: 100,
            }),
          ],
          sessions: [createPlayerGameActivitySession({ type: 'player-session' })],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
        ticket: createTicketListEntry({
          game: createGame({ title: 'Streets of Rage 2' }),
          reporter: createUser({ displayName: 'Scott' }),
        }),
      },
    });

    // ASSERT
    expect(
      screen.getByRole('button', { name: "Scott's activity for Streets of Rage 2" }),
    ).toBeVisible();
    expect(screen.queryByText(/100.00% - RALibRetro/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/the server records each timestamp/i)).not.toBeInTheDocument();
    expect(screen.queryByTestId('session-header')).not.toBeInTheDocument();
  });

  it('given the user expands the section, reveals the client breakdown, the timestamp note, and the timeline', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        achievement: createAchievement(),
        hasReporterHistoryAccess: true,
        reporterActivity: {
          clientBreakdown: [
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RALibRetro',
              durationPercentage: 100,
            }),
          ],
          sessions: [createPlayerGameActivitySession({ type: 'player-session' })],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
        ticket: createTicketListEntry({
          game: createGame({ title: 'Streets of Rage 2' }),
          reporter: createUser({ displayName: 'Scott' }),
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /scott's activity/i }));

    // ASSERT
    expect(screen.getByText(/100.00% - RALibRetro/i)).toBeVisible();
    expect(screen.getByText(/the server records each timestamp/i)).toBeVisible();
    expect(screen.getAllByTestId('session-header')).toHaveLength(2); // one session, rendered once for desktop and once for mobile
  });

  it('given the timeline is taller than the screen, settles the panel at its natural height once opening finishes', async () => {
    // ARRANGE
    vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(4200);

    render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        achievement: createAchievement(),
        hasReporterHistoryAccess: true,
        reporterActivity: {
          clientBreakdown: [],
          sessions: [createPlayerGameActivitySession({ type: 'player-session' })],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
        ticket: createTicketListEntry({
          game: createGame({ title: 'Streets of Rage 2' }),
          reporter: createUser({ displayName: 'Scott' }),
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /scott's activity/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByTestId('player-history-content')).toHaveStyle({ height: 'auto' });
    });
  });

  it('given the user collapses the section, hides the timeline', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        achievement: createAchievement(),
        hasReporterHistoryAccess: true,
        reporterActivity: {
          clientBreakdown: [],
          sessions: [createPlayerGameActivitySession({ type: 'player-session' })],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
        ticket: createTicketListEntry({
          game: createGame({ title: 'Streets of Rage 2' }),
          reporter: createUser({ displayName: 'Scott' }),
        }),
      },
    });

    await userEvent.click(screen.getByRole('button', { name: /scott's activity/i }));

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /scott's activity/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByTestId('session-header')).not.toBeInTheDocument();
    });
    expect(screen.getByRole('button', { name: /scott's activity/i })).toHaveAttribute(
      'aria-expanded',
      'false',
    );
  });

  it('given the reporter unlocked the reported achievement, marks only that unlock in the timeline', async () => {
    // ARRANGE
    const markedAchievement = createAchievement({ id: 2468 });
    const unmarkedAchievement = createAchievement({ id: 1357 });

    render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        achievement: markedAchievement,
        hasReporterHistoryAccess: true,
        reporterActivity: {
          clientBreakdown: [],
          sessions: [
            createPlayerGameActivitySession({
              type: 'player-session',
              events: [
                createPlayerGameActivityEvent({ type: 'unlock', achievement: markedAchievement }),
                createPlayerGameActivityEvent({ type: 'unlock', achievement: unmarkedAchievement }),
              ],
            }),
          ],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
        ticket: createTicketListEntry({
          game: createGame({ title: 'Streets of Rage 2' }),
          reporter: createUser({ displayName: 'Scott' }),
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /scott's activity/i }));

    // ASSERT
    expect(screen.getAllByText(/reported achievement/i)).toHaveLength(2);
  });

  it('given the user prefers reduced motion, opens the panel at full height instead of sliding it open', async () => {
    // ARRANGE
    vi.mocked(useReducedMotion).mockReturnValue(true);
    vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(860);

    render<App.Platform.Data.TicketShowPageProps>(<TicketPlayerHistory />, {
      pageProps: {
        achievement: createAchievement(),
        hasReporterHistoryAccess: true,
        reporterActivity: {
          clientBreakdown: [],
          sessions: [createPlayerGameActivitySession({ type: 'player-session' })],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
        ticket: createTicketListEntry({
          game: createGame({ title: 'Streets of Rage 2' }),
          reporter: createUser({ displayName: 'Scott' }),
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /scott's activity/i }));

    // ASSERT
    await waitFor(
      () => {
        expect(screen.getByTestId('player-history-content')).toHaveStyle({ height: 'auto' });
      },
      { timeout: 200 },
    );
    expect(screen.getAllByTestId('session-header')).toHaveLength(2); // one session, rendered once for desktop and once for mobile
  });
});
