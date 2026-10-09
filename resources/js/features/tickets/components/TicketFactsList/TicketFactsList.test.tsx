import { render, screen } from '@/test';
import { createLeaderboardEntry, createTicketListEntry, createUser } from '@/test/factories';

import { TicketFactsList } from './TicketFactsList';

describe('Component: TicketFactsList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders the related tickets row', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        isViewerReporter: true,
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'achievement',
          resolvedAt: null,
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(screen.getAllByRole('listitem').length).toEqual(1);
    expect(screen.getByRole('listitem')).toHaveTextContent(
      'This achievement has no other tickets.',
    );
  });

  it('given a request ticket and a viewer who is not the reporter, names who the developer is waiting on', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        isViewerReporter: false,
        ticket: createTicketListEntry({
          state: 'request',
          ticketableType: 'achievement',
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent('Waiting for Reporter to reply.');
    expect(container).not.toHaveTextContent('We need more information from you.');
  });

  it('given a request ticket and a viewer who is the reporter, asks for more information', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        isViewerReporter: true,
        ticket: createTicketListEntry({
          state: 'request',
          ticketableType: 'achievement',
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent(
      'We need more information from you. Reply in the comments below.',
    );
    expect(container).not.toHaveTextContent('Waiting for');
  });

  it('given a quarantined ticket, shows an explanation', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        ticket: createTicketListEntry({
          state: 'quarantined',
          ticketableType: 'achievement',
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent(
      'We did not send this quarantined ticket to the author because we do not fully support this emulator or core.',
    );
  });

  it('given a finished ticket, shows the outcome', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        isViewerReporter: false,
        ticket: createTicketListEntry({
          state: 'closed',
          resolution: 'wrong_rom',
          ticketableType: 'achievement',
          createdAt: '2024-05-01T12:00:00Z',
          resolvedAt: '2024-05-03T12:00:00Z',
          resolver: createUser({ displayName: 'Scott' }),
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott closed this ticket 2 days after the report because the reporter used the wrong ROM.',
    );
  });

  it('given a resolved ticket and a viewer who is the reporter, says they can still open a new ticket', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        isViewerReporter: true,
        ticket: createTicketListEntry({
          state: 'resolved',
          ticketableType: 'achievement',
          resolvedAt: '2024-05-03T12:00:00Z',
          resolver: createUser({ displayName: 'Scott' }),
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent(
      'If the achievement still does not work for you, you can open a new ticket.',
    );
  });

  it.each([
    ['resolved', false],
    ['closed', true],
  ] as const)(
    'given a %s ticket and isViewerReporter %s, does not show the new ticket note',
    (state, isViewerReporter) => {
      // ARRANGE
      const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
        pageProps: {
          can: {},
          relatedTickets: [],
          isViewerReporter,
          ticket: createTicketListEntry({
            state,
            ticketableType: 'achievement',
            resolvedAt: '2024-05-03T12:00:00Z',
            resolver: createUser({ displayName: 'Scott' }),
            reporter: createUser({ displayName: 'Reporter' }),
          }),
        },
      });

      // ASSERT
      expect(container).not.toHaveTextContent('you can open a new ticket');
    },
  );

  it('given a reporter unlock, shows the reporter unlock row', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        reporterUnlock: {
          unlockedAt: '2024-05-03T12:00:00Z',
          isHardcore: false,
          unlocker: null,
        },
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'achievement',
          reporter: createUser({ displayName: 'Reporter' }),
          createdAt: '2024-05-01T12:00:00Z',
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent(
      'Reporter unlocked this achievement in casual mode 2 days after the report.',
    );
  });

  it('given an unlock count of zero, still shows the unlock count row', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        unlocksSinceReported: 0,
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'achievement',
          reporter: createUser({ displayName: 'Reporter' }),
          createdAt: '2024-05-01T12:00:00Z',
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent('No one has unlocked this achievement in the');
  });

  it('given an open ticket whose logic changed, shows the logic changed row', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        reportedTriggerVersion: 3,
        currentTriggerVersion: 5,
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'achievement',
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent('The logic changed from v3 to v5 after the report.');
  });

  it.each([
    ['resolved', 3, 5],
    ['open', 3, 3],
  ] as const)(
    'given a %s ticket with logic v%s and v%s, does not show the logic changed row',
    (state, reportedTriggerVersion, currentTriggerVersion) => {
      // ARRANGE
      const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
        pageProps: {
          can: {},
          relatedTickets: [],
          reportedTriggerVersion,
          currentTriggerVersion,
          ticket: createTicketListEntry({
            state,
            ticketableType: 'achievement',
            resolvedAt: '2024-05-03T12:00:00Z',
            resolver: createUser({ displayName: 'Scott' }),
            reporter: createUser({ displayName: 'Reporter' }),
          }),
        },
      });

      // ASSERT
      expect(container).not.toHaveTextContent('The logic changed');
    },
  );

  it('given a leaderboard ticket with a reporter entry, shows the leaderboard entry row', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        leaderboardEntryCount: 20,
        reporterLeaderboardEntry: createLeaderboardEntry({ rank: 2, formattedScore: '1:13.01' }),
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'leaderboard',
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent(
      'Reporter has an entry on this leaderboard: #2 of 20, 1:13.01.',
    );
  });

  it('given a leaderboard ticket without a reporter entry, shows the no entry row', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'leaderboard',
          reporter: createUser({ displayName: 'Reporter' }),
        }),
      },
    });

    // ASSERT
    expect(container).toHaveTextContent('Reporter has no entry yet on this leaderboard.');
  });

  it('given a leaderboard ticket with a deleted reporter, does not show the leaderboard entry row', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketFactsList />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'leaderboard',
          reporter: null,
        }),
      },
    });

    // ASSERT
    expect(container).not.toHaveTextContent('on this leaderboard');
  });
});
